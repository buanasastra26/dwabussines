<?php
function password_error(string $password): ?string {
    return strlen($password)<10 || strlen($password)>72 ? 'Gunakan kata sandi 10–72 byte (huruf, angka, atau simbol).' : null;
}
function ensure_credentials(): void {
    db()->exec('CREATE TABLE IF NOT EXISTS dwa_credentials (user_id BIGINT UNSIGNED PRIMARY KEY, password_hash VARCHAR(255) NOT NULL, session_version CHAR(64) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}
function start_password_session(array $user,string $version): void {
    session_regenerate_id(true);
    $_SESSION=['user'=>['id'=>(int)$user['id'],'name'=>$user['name'],'email'=>$user['email'],'whatsapp'=>$user['whatsapp']], 'auth_version'=>$version,'expires'=>time()+43200,'csrf'=>bin2hex(random_bytes(32))];
}
function find_account(string $identifier): array|false {
    $q=db()->prepare('SELECT u.id,u.name,u.email,u.whatsapp,c.password_hash,c.session_version FROM dwa_users u LEFT JOIN dwa_credentials c ON c.user_id=u.id WHERE u.email=? OR u.whatsapp=? LIMIT 1');
    $q->execute([$identifier,phone($identifier)??'']);return $q->fetch();
}
