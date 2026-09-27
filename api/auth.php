<?php
require __DIR__.'/../server/bootstrap.php';require __DIR__.'/../server/passwords.php';
try {
 if($_SERVER['REQUEST_METHOD']!=='POST')json_out(['error'=>'Metode tidak diizinkan.'],405);
 $raw=file_get_contents('php://input',false,null,0,8193);if(strlen($raw)>8192)json_out(['error'=>'Permintaan terlalu besar.'],413);
 $data=json_decode($raw,true);if(!is_array($data))json_out(['error'=>'Permintaan tidak valid.'],400);
 if(!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??''))json_out(['error'=>'Sesi berakhir. Muat ulang halaman.'],403);
 foreach($data as $key=>$value)if($key==='consent'?!is_bool($value):!is_string($value))json_out(['error'=>'Data tidak valid.'],422);
 $action=$data['action']??'';
 if($action==='logout'){$_SESSION=[];session_destroy();setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);json_out(['ok'=>true]);}
 ensure_credentials();$ip=$_SERVER['REMOTE_ADDR']??'unknown';
 if($action==='login'){
  $identifier=strtolower(trim($data['identifier']??''));$password=$data['password']??'';
  if(!$identifier||strlen($identifier)>254||strlen($password)>72)json_out(['error'=>'Isi email/WhatsApp dan kata sandi yang valid.'],422);
  if(!limit_request('password-ip:'.$ip,30,900)||!limit_request('password-account:'.$identifier,10,900))json_out(['error'=>'Terlalu banyak percobaan masuk. Tunggu 15 menit.'],429);
  $user=find_account($identifier);
  $dummy='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
  $valid=password_verify($password,$user['password_hash']??$dummy);
  if(!$user||!$user['password_hash']||!$valid)json_out(['error'=>'Email/WhatsApp atau kata sandi salah. Akun lama: gunakan Lupa kata sandi untuk membuat sandi.'],400);
  if(password_needs_rehash($user['password_hash'],PASSWORD_DEFAULT)){$q=db()->prepare('UPDATE dwa_credentials SET password_hash=? WHERE user_id=? AND password_hash=?');$q->execute([password_hash($password,PASSWORD_DEFAULT),$user['id'],$user['password_hash']]);}
  start_password_session($user,$user['session_version']);json_out(['ok'=>true,'redirect'=>($data['destination']??'')==='admin' && strtolower($user['email'])==='ciptaniagateramini@gmail.com'?'kelola-dwa.php':'dashboard.php']);
 }
 if($action==='request'){
  if(!configured())json_out(['error'=>'Email verifikasi belum tersedia. Hubungi DWA.'],503);
  $mode=$data['mode']??'';if(!in_array($mode,['register','reset'],true))json_out(['error'=>'OTP hanya untuk pendaftaran atau reset sandi.'],422);
  $name=trim($data['name']??'');$wa=null;$password=$data['password']??'';
  if($mode==='register'){
   if($error=password_error($password))json_out(['error'=>$error],422);
   $email=strtolower(trim($data['email']??''));$identifier=$email;
   if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254||preg_match('/[^\x20-\x7E]/',$email))json_out(['error'=>'Email tidak valid.'],422);
   if(strlen($name)<2||strlen($name)>100||($data['consent']??false)!==true)json_out(['error'=>'Isi nama dan setujui penggunaan data akun.'],422);
   $rawWa=trim($data['whatsapp']??'');$wa=$rawWa===''?null:phone($rawWa);if($rawWa!==''&&!$wa)json_out(['error'=>'Nomor WhatsApp tidak valid.'],422);
  }else{$identifier=strtolower(trim($data['identifier']??''));if(!$identifier||strlen($identifier)>254)json_out(['error'=>'Isi email atau WhatsApp terdaftar.'],422);}
  if(!limit_request('request-ip:'.$ip,12,3600)||!limit_request('identifier:'.$identifier,5,3600))json_out(['error'=>'Batas permintaan tercapai. Tunggu satu jam.'],429);
  if(time()<($_SESSION['send_after']??0))json_out(['error'=>'Tunggu 60 detik sebelum meminta kode baru.'],429);
  $_SESSION['send_after']=time()+60;$p=db();$existing=find_account($identifier);
  if($mode==='reset'){$eligible=(bool)$existing;$email=$existing['email']??'unknown@invalid.local';$name=$existing['name']??'';$wa=$existing['whatsapp']??null;}
  else{$eligible=!$existing;if($wa){$q=$p->prepare('SELECT id FROM dwa_users WHERE whatsapp=?');$q->execute([$wa]);if($q->fetch())$eligible=false;}}
  if(!limit_request('recipient:'.$email,5,3600))json_out(['error'=>'Batas kode tercapai. Tunggu satu jam.'],429);
  $id=bin2hex(random_bytes(32));$code=str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);$sessionHash=digest(session_id());
  $q=$p->prepare('UPDATE dwa_otp SET consumed=1 WHERE session_hash=? AND consumed=0');$q->execute([$sessionHash]);
  $q=$p->prepare('INSERT INTO dwa_otp (id,session_hash,email,name,whatsapp,otp_hash,eligible,expires_at) VALUES (?,?,?,?,?,?,?,?)');$q->execute([$id,$sessionHash,$email,$name,$wa,digest($id.':'.$code),$eligible?1:0,time()+600]);
  $_SESSION['password_challenge']=['id'=>$id,'mode'=>$mode,'hash'=>$mode==='register'?password_hash($password,PASSWORD_DEFAULT):null];
  if($eligible)try{send_code($email,$code);}catch(Throwable $error){$q=$p->prepare('UPDATE dwa_otp SET consumed=1 WHERE id=?');$q->execute([$id]);error_log('DWA: OTP delivery unavailable.');}
  $p->exec('DELETE FROM dwa_otp WHERE expires_at < '.(time()-86400).' LIMIT 500');$p->exec('DELETE FROM dwa_limits WHERE expires_at < '.(time()-86400).' LIMIT 500');
  json_out(['challenge'=>$id,'message'=>'Jika data memenuhi syarat, OTP dikirim ke email akun. Periksa inbox/spam. Akun yang sudah terdaftar harus menggunakan Masuk atau Lupa kata sandi.']);
 }
 if($action==='verify'){
  if(!limit_request('verify-ip:'.$ip,40,600))json_out(['error'=>'Tunggu 10 menit sebelum mencoba kembali.'],429);
  $id=$data['challenge']??'';$code=$data['code']??'';$pending=$_SESSION['password_challenge']??[];
  if(!preg_match('/^[a-f0-9]{64}$/D',$id)||!preg_match('/^[0-9]{6}$/D',$code)||($pending['id']??'')!==$id)json_out(['error'=>'Kode tidak berlaku. Minta kode baru.'],400);
  $mode=$pending['mode'];$hash=$pending['hash'];
  if($mode==='reset'){if($error=password_error($data['password']??''))json_out(['error'=>$error],422);$hash=password_hash($data['password'],PASSWORD_DEFAULT);}
  $p=db();$p->beginTransaction();$q=$p->prepare('SELECT * FROM dwa_otp WHERE id=? FOR UPDATE');$q->execute([$id]);$otp=$q->fetch();
  if(!$otp||!hash_equals($otp['session_hash'],digest(session_id()))||$otp['consumed']||(int)$otp['expires_at']<=time()||(int)$otp['attempts']>=5){$p->rollBack();json_out(['error'=>'Kode sudah dipakai, kedaluwarsa, atau tidak berlaku.'],400);}
  if(!$otp['eligible']||!hash_equals($otp['otp_hash'],digest($id.':'.$code))){$q=$p->prepare('UPDATE dwa_otp SET attempts=attempts+1 WHERE id=?');$q->execute([$id]);$p->commit();json_out(['error'=>'Kode salah atau tidak berlaku. Maksimal 5 percobaan.'],400);}
  $q=$p->prepare('SELECT id,name,email,whatsapp FROM dwa_users WHERE email=? FOR UPDATE');$q->execute([$otp['email']]);$user=$q->fetch();
  if(($mode==='register'&&$user)||($mode==='reset'&&!$user)){$p->rollBack();json_out(['error'=>'Permintaan tidak berlaku. Gunakan Masuk atau Lupa kata sandi.'],400);}
  if($mode==='register'){$q=$p->prepare('INSERT INTO dwa_users (name,email,whatsapp) VALUES (?,?,?)');$q->execute([$otp['name'],$otp['email'],$otp['whatsapp']]);$user=['id'=>(int)$p->lastInsertId(),'name'=>$otp['name'],'email'=>$otp['email'],'whatsapp'=>$otp['whatsapp']];}
  $version=bin2hex(random_bytes(32));$q=$p->prepare('INSERT INTO dwa_credentials (user_id,password_hash,session_version) VALUES (?,?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),session_version=VALUES(session_version)');$q->execute([$user['id'],$hash,$version]);
  $q=$p->prepare('UPDATE dwa_otp SET consumed=1 WHERE email=? OR session_hash=?');$q->execute([$otp['email'],$otp['session_hash']]);$p->commit();unset($_SESSION['password_challenge']);
  if($mode==='reset'){$_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));json_out(['ok'=>true,'redirect'=>'portal.php?reset=success']);}
  start_password_session($user,$version);json_out(['ok'=>true,'redirect'=>'dashboard.php']);
 }
 json_out(['error'=>'Permintaan tidak dikenal.'],400);
}catch(Throwable $error){if(isset($p)&&$p->inTransaction())$p->rollBack();error_log('DWA: authentication service error.');json_out(['error'=>'Layanan akun belum tersedia. Hubungi DWA.'],503);}
