<?php
function ensure_commerce(): void {
 static $done=false;if($done)return;
 foreach([
 'CREATE TABLE IF NOT EXISTS dwa_products (product_id VARCHAR(60) PRIMARY KEY, data_json LONGTEXT NOT NULL, published TINYINT NOT NULL DEFAULT 0, photo MEDIUMBLOB NULL, photo_mime VARCHAR(30) NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
 'CREATE TABLE IF NOT EXISTS dwa_prices (product_id VARCHAR(60) PRIMARY KEY, price BIGINT NULL, old_price BIGINT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
 'CREATE TABLE IF NOT EXISTS dwa_orders (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_no VARCHAR(40) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL, checkout_key CHAR(64) NOT NULL UNIQUE, customer_json LONGTEXT NOT NULL, items_json LONGTEXT NOT NULL, subtotal BIGINT NOT NULL, shipping BIGINT NULL, payment_method VARCHAR(30) NOT NULL, payment_status VARCHAR(20) NOT NULL DEFAULT \'pending\', status VARCHAR(20) NOT NULL DEFAULT \'pending\', payment_reference VARCHAR(200) NULL, verified_by BIGINT UNSIGNED NULL, verified_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX user_orders(user_id,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
 'CREATE TABLE IF NOT EXISTS dwa_audit (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id BIGINT UNSIGNED NOT NULL, action VARCHAR(50) NOT NULL, detail TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
 ] as $sql)db()->exec($sql);$done=true;
}
function is_admin(array $user): bool {return strtolower($user['email'])==='ciptaniagateramini@gmail.com';}
function require_admin(): array {$u=current_user();if(!$u){header('Location: portal.php?next=admin');exit;}if(!is_admin($u)){http_response_code(403);exit('Akses khusus administrator.');}ensure_commerce();return $u;}
function audit_admin(int $id,string $action,string $detail): void {$q=db()->prepare('INSERT INTO dwa_audit(admin_id,action,detail) VALUES (?,?,?)');$q->execute([$id,$action,$detail]);}
function csrf_form(): void {echo '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">';}
function check_admin_post(): void {if($_SERVER['REQUEST_METHOD']!=='POST'||!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf'],$_POST['csrf'])){http_response_code(403);exit('Sesi formulir tidak valid.');}}
function order_status(string $s): string {return ['pending'=>'Menunggu verifikasi','processing'=>'Diproses','completed'=>'Selesai'][$s]??$s;}
function money_input(string $value,bool $nullable=false): ?int {if($nullable&&$value==='')return null;if(!preg_match('/^[0-9]{1,10}$/D',$value)||((int)$value)>1000000000)throw new RuntimeException('Nominal harus angka 0–1.000.000.000.');return (int)$value;}
function order_transition(array $order,string $action): string {
 if($action==='verify'){if($order['payment_status']!=='pending')throw new RuntimeException('Pembayaran sudah diverifikasi.');return 'processing';}
 if($order['payment_status']!=='verified')throw new RuntimeException('Verifikasi pembayaran terlebih dahulu.');
 if($action==='complete'&&$order['status']==='processing')return 'completed';
 if($action==='process'&&$order['status']==='completed')return 'processing';
 throw new RuntimeException('Perubahan status tidak berlaku.');
}
function document_brand(): string {return '<header class="doc-brand"><img width="70" height="70" alt="DWA Bussines" src="data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/../icons/app-192.png')).'"><div><h2>DWA BUSSINES</h2><strong>PT DRAJAT WIGUNA ADIDAYA</strong><p>Legalitas Usaha · Pengembangan UMKM · Website, Aplikasi & Software</p><p>Email: customerservice@dwabussines.com<br>WhatsApp: 0882000119208 · Instagram: @dwalegalitas</p></div></header>';}
function document_start(string $title): void {header('Content-Type: text/html; charset=utf-8');echo '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>'.e($title).'</title><style>body{font:14px Arial;line-height:1.6;color:#23191a;max-width:1000px;margin:35px auto;padding:20px}h1,h2{color:#8b0d18}table{width:100%;border-collapse:collapse;margin:25px 0}th,td{border:1px solid #ddd;padding:10px;text-align:left;overflow-wrap:anywhere}.doc-brand{display:flex;gap:22px;align-items:center;border-bottom:3px solid #a81020;padding-bottom:20px}.doc-brand p{margin:5px 0;font-size:12px}.doc-brand h2{margin:0}small{color:#666}@media print{body{margin:0;padding:0;font-size:11px}thead{display:table-header-group}tr{break-inside:avoid}@page{size:A4;margin:15mm}}</style><body>'.document_brand().'<h1>'.e($title).'</h1>';}
