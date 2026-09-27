<?php
declare(strict_types=1);
require __DIR__.'/../server/bootstrap.php';
try {
    if ($_SERVER['REQUEST_METHOD']!=='POST') json_out(['error'=>'Metode tidak diizinkan.'],405);
    if ((int)($_SERVER['CONTENT_LENGTH']??0)>8192) json_out(['error'=>'Permintaan terlalu besar.'],413);
    $data=json_decode(file_get_contents('php://input'),true);
    if (!is_array($data)) json_out(['error'=>'Permintaan tidak valid.'],400);
    if (!hash_equals($_SESSION['csrf'],(string)($_SERVER['HTTP_X_CSRF_TOKEN']??''))) json_out(['error'=>'Sesi formulir berakhir. Muat ulang halaman.'],403);
    $action=$data['action']??'';
    if ($action==='logout') {
        $_SESSION=[];session_destroy();setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
        json_out(['ok'=>true]);
    }
    if (!configured()) json_out(['error'=>'Pendaftaran belum aktif. Silakan hubungi DWA untuk bantuan.'],503);
    $ip=$_SERVER['REMOTE_ADDR']??'unknown';
    if ($action==='request') {
        $mode=$data['mode']??'';
        if (!in_array($mode,['register','login'],true)) json_out(['error'=>'Pilih daftar atau masuk.'],422);
        $name=trim((string)($data['name']??''));$wa=null;
        if ($mode==='register') {
            $email=strtolower(trim((string)($data['email']??'')));
            if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>254 || preg_match('/[^\x20-\x7E]/',$email)) json_out(['error'=>'Masukkan alamat email yang valid.'],422);
            if (strlen($name)<2 || strlen($name)>100) json_out(['error'=>'Nama wajib diisi, maksimal 100 karakter.'],422);
            if (($data['consent']??false)!==true) json_out(['error'=>'Setujui penggunaan data untuk akun Anda.'],422);
            $raw=trim((string)($data['whatsapp']??''));$wa=$raw===''?null:phone($raw);
            if ($raw!=='' && !$wa) json_out(['error'=>'Nomor WhatsApp tidak valid. Gunakan 08… atau +62….'],422);
            $identifier=$email;
        } else {
            $identifier=strtolower(trim((string)($data['identifier']??'')));
            if (strlen($identifier)>254 || $identifier==='') json_out(['error'=>'Isi email atau nomor WhatsApp terdaftar.'],422);
            $email=$identifier;$name='';
        }
        if (!limit_request('request-ip:'.$ip,12,3600) || !limit_request('identifier:'.$identifier,5,3600)) json_out(['error'=>'Terlalu banyak permintaan. Coba lagi dalam satu jam.'],429);
        if (time()<($_SESSION['send_after']??0)) json_out(['error'=>'Tunggu 60 detik sebelum meminta kode baru.'],429);
        $_SESSION['send_after']=time()+60;
        $p=db();$existing=null;
        if ($mode==='login') {
            $q=$p->prepare('SELECT id,name,email,whatsapp FROM dwa_users WHERE email=? OR whatsapp=? LIMIT 1');$q->execute([$identifier,phone($identifier)??'']);$existing=$q->fetch();
            $eligible=(bool)$existing;$email=$existing['email']??'unknown@invalid.local';
        } else {
            $q=$p->prepare('SELECT id,name,email,whatsapp FROM dwa_users WHERE email=?');$q->execute([$email]);$existing=$q->fetch();$eligible=true;
            if (!$existing && $wa) {$q=$p->prepare('SELECT id FROM dwa_users WHERE whatsapp=?');$q->execute([$wa]);if($q->fetch())$eligible=false;}
        }
        if ($existing) {$email=$existing['email'];$name=$existing['name'];$wa=$existing['whatsapp'];}
        if (!limit_request('recipient:'.$email,5,3600)) json_out(['error'=>'Batas permintaan kode tercapai. Coba lagi dalam satu jam.'],429);
        $id=bin2hex(random_bytes(32));$code=str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);$sessionHash=digest(session_id());
        // A resend invalidates all previous codes in this browser session.
        $q=$p->prepare('UPDATE dwa_otp SET consumed=1 WHERE session_hash=? AND consumed=0');$q->execute([$sessionHash]);
        $q=$p->prepare('INSERT INTO dwa_otp (id,session_hash,email,name,whatsapp,otp_hash,eligible,expires_at) VALUES (?,?,?,?,?,?,?,?)');
        $q->execute([$id,$sessionHash,$email,$name,$wa,digest($id.':'.$code),$eligible?1:0,time()+600]);
        if ($eligible) {
            try {send_code($email,$code);} catch(Throwable $error) {
                $q=$p->prepare('UPDATE dwa_otp SET consumed=1 WHERE id=?');$q->execute([$id]);
                // Same response for all identifiers; never expose SMTP details or OTP.
                error_log('DWA: OTP delivery unavailable.');
            }
        }
        // Best-effort bounded retention; expired codes are never accepted even before cleanup.
        $p->exec('DELETE FROM dwa_otp WHERE expires_at < '.(time()-86400).' LIMIT 500');
        $p->exec('DELETE FROM dwa_limits WHERE expires_at < '.(time()-86400).' LIMIT 500');
        json_out(['challenge'=>$id,'retry_after'=>60,'message'=>'Jika data memenuhi syarat, kode dikirim ke email akun Anda. Periksa inbox atau spam.']);
    }
    if ($action==='verify') {
        if (!limit_request('verify-ip:'.$ip,40,600)) json_out(['error'=>'Terlalu banyak percobaan. Tunggu 10 menit.'],429);
        $id=(string)($data['challenge']??'');$code=(string)($data['code']??'');
        if (!preg_match('/^[a-f0-9]{64}$/D',$id) || !preg_match('/^[0-9]{6}$/D',$code)) json_out(['error'=>'Masukkan 6 digit OTP.'],422);
        $p=db();$p->beginTransaction();
        $q=$p->prepare('SELECT * FROM dwa_otp WHERE id=? FOR UPDATE');$q->execute([$id]);$otp=$q->fetch();
        if (!$otp || !hash_equals($otp['session_hash'],digest(session_id())) || $otp['consumed'] || (int)$otp['expires_at']<=time() || (int)$otp['attempts']>=5) {
            $p->rollBack();json_out(['error'=>'Kode tidak berlaku. Minta kode baru.'],400);
        }
        if (!$otp['eligible'] || !hash_equals($otp['otp_hash'],digest($id.':'.$code))) {
            $q=$p->prepare('UPDATE dwa_otp SET attempts=attempts+1 WHERE id=?');$q->execute([$id]);$p->commit();json_out(['error'=>'Kode salah atau tidak berlaku. Maksimal 5 percobaan.'],400);
        }
        $q=$p->prepare('SELECT id,name,email,whatsapp FROM dwa_users WHERE email=?');$q->execute([$otp['email']]);$user=$q->fetch();
        if (!$user) {
            $q=$p->prepare('INSERT INTO dwa_users (name,email,whatsapp) VALUES (?,?,?)');$q->execute([$otp['name'],$otp['email'],$otp['whatsapp']]);
            $user=['id'=>(int)$p->lastInsertId(),'name'=>$otp['name'],'email'=>$otp['email'],'whatsapp'=>$otp['whatsapp']];
        }
        $q=$p->prepare('UPDATE dwa_otp SET consumed=1 WHERE email=? OR session_hash=?');$q->execute([$otp['email'],$otp['session_hash']]);$p->commit();
        session_regenerate_id(true);$_SESSION['user']=$user;$_SESSION['expires']=time()+43200;$_SESSION['csrf']=bin2hex(random_bytes(32));
        json_out(['ok'=>true,'redirect'=>'dashboard.php']);
    }
    json_out(['error'=>'Permintaan tidak dikenal.'],400);
} catch(Throwable $error) {
    if (isset($p) && $p->inTransaction()) $p->rollBack();
    error_log('DWA: authentication service error.');
    json_out(['error'=>'Layanan akun belum tersedia. Coba lagi nanti atau hubungi DWA.'],503);
}
