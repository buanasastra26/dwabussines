<?php
require __DIR__.'/server/bootstrap.php';$user=require_user();require __DIR__.'/server/catalog.php';require_once __DIR__.'/server/commerce.php';
$id=$_GET['id']??'';if(!is_string($id)||strlen($id)>60){http_response_code(404);exit;}$products=catalog(is_admin($user));if(!isset($products[$id])){http_response_code(404);exit;}
$q=db()->prepare('SELECT photo,photo_mime FROM dwa_products WHERE product_id=?');$q->execute([$id]);$row=$q->fetch();
if(!$row||!$row['photo']||!in_array($row['photo_mime'],['image/jpeg','image/png','image/webp'],true)){http_response_code(404);exit;}
header('Content-Type: '.$row['photo_mime']);header('Content-Disposition: inline');header('X-Content-Type-Options: nosniff');header('Content-Security-Policy: default-src \'none\'');echo $row['photo'];
