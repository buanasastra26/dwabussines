<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
session_name('DWA_SESSION');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
session_start();
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function cfg(): array {
    static $config;
    if ($config !== null) return $config;
    $path = dirname(__DIR__, 2) . '/dwa-private/config.php';
    $config = is_file($path) ? require $path : [];
    return is_array($config) ? $config : [];
}
function configured(): bool {
    $c=cfg();$s=$c['smtp']??[];$d=$c['db']??[];
    return ($c['enabled']??false)===true && strlen($c['app_key']??'')>=64
        && !empty($d['host']) && !empty($d['name']) && !empty($d['user'])
        && !empty($s['host']) && !empty($s['username']) && !empty($s['password'])
        && (bool)filter_var($s['from_email']??'', FILTER_VALIDATE_EMAIL)
        && in_array($s['encryption']??'', ['ssl','tls'], true);
}
function db(): PDO {
    static $pdo;
    if ($pdo) return $pdo;
    $d=cfg()['db'];
    $pdo=new PDO('mysql:host='.$d['host'].';dbname='.$d['name'].';charset=utf8mb4',$d['user'],$d['password'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    return $pdo;
}
function e(string $text): string {return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');}
function digest(string $value): string {return hash_hmac('sha256',$value,cfg()['app_key']);}
function phone(string $value): ?string {
    $value=preg_replace('/[\s()+-]/','',$value);
    if (str_starts_with($value,'0')) $value='62'.substr($value,1);
    return preg_match('/^62[1-9][0-9]{7,12}$/D',$value) ? $value : null;
}
function current_user(): ?array {
    if (!isset($_SESSION['user']) || time() > ($_SESSION['expires']??0)) {
        unset($_SESSION['user'],$_SESSION['expires']);return null;
    }
    return $_SESSION['user'];
}
function require_user(): array {
    $user=current_user();
    if (!$user) {header('Location: portal.php');exit;}
    return $user;
}
function json_out(array $body, int $code=200): never {
    http_response_code($code);header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body,JSON_UNESCAPED_UNICODE);exit;
}
function limit_request(string $key,int $limit,int $seconds): bool {
    $p=db();$bucket=digest('rate:'.$key);$now=time();
    $p->beginTransaction();
    try {
        $q=$p->prepare('INSERT IGNORE INTO dwa_limits (bucket,hits,expires_at) VALUES (?,0,?)');$q->execute([$bucket,$now+$seconds]);
        $q=$p->prepare('SELECT hits,expires_at FROM dwa_limits WHERE bucket=? FOR UPDATE');$q->execute([$bucket]);$r=$q->fetch();
        $hits=(int)$r['hits'];$expires=(int)$r['expires_at'];
        if ($expires<=$now) {$hits=0;$expires=$now+$seconds;}
        if ($hits>=$limit) {$p->commit();return false;}
        $q=$p->prepare('UPDATE dwa_limits SET hits=?,expires_at=? WHERE bucket=?');$q->execute([$hits+1,$expires,$bucket]);$p->commit();return true;
    } catch(Throwable $error) {if($p->inTransaction())$p->rollBack();throw $error;}
}
function send_code(string $email,string $code): void {
    foreach (['Exception','PHPMailer','SMTP'] as $file) require_once __DIR__.'/../vendor/phpmailer/src/'.$file.'.php';
    $s=cfg()['smtp'];$mail=new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();$mail->Host=$s['host'];$mail->Port=(int)$s['port'];$mail->SMTPAuth=true;
    $mail->Username=$s['username'];$mail->Password=$s['password'];
    $mail->SMTPSecure=$s['encryption'];$mail->Timeout=15;$mail->CharSet='UTF-8';
    $mail->setFrom($s['from_email'],$s['from_name']??'DWA Bussines');$mail->addAddress($email);
    $mail->Subject='Kode verifikasi akun DWA Bussines';
    $mail->Body="Kode OTP DWA Bussines Anda: $code\n\nBerlaku selama 10 menit, hanya untuk satu kali penggunaan. Jangan bagikan kode ini kepada siapa pun. Tim DWA tidak akan meminta kode OTP Anda.\n\nJika Anda tidak meminta kode ini, abaikan email ini.";
    $mail->send();
}
