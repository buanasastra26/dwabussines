<?php
function product_text(array $data,string $key,int $max,bool $required=false): string {$v=$data[$key]??'';if(!is_string($v))throw new RuntimeException('Isian produk tidak valid.');$v=trim($v);if(($required&&$v==='')||strlen($v)>$max)throw new RuntimeException('Periksa isian '.$key.' (maksimal '.$max.' byte).');return $v;}
function product_lines(string $value): array {$lines=array_values(array_filter(array_map('trim',preg_split('/\R/u',$value)),fn($v)=>$v!==''));if(count($lines)>30)throw new RuntimeException('Maksimal 30 baris kelengkapan atau bonus.');return $lines;}
function validate_product(array $data): array {
 $category=product_text($data,'category',30,true);if(!in_array($category,['Legalitas','UMKM','Digital'],true))throw new RuntimeException('Kategori tidak valid.');
 $price=money_input(product_text($data,'price',20),true);$old=money_input(product_text($data,'old_price',20),true);if($old!==null&&($price===null||$old<=$price))throw new RuntimeException('Harga normal harus lebih tinggi daripada harga jual.');
 return ['name'=>product_text($data,'name',120,true),'category'=>$category,'icon'=>'bag','label'=>product_text($data,'label',180,true),'description'=>product_text($data,'description',10000,true),'price'=>$price,'old_price'=>$old,'includes'=>product_lines(product_text($data,'includes',6000)),'bonuses'=>product_lines(product_text($data,'bonuses',6000))];
}
function validate_product_photo(string $path,int $size): string {
 if($size<1||$size>2*1024*1024)throw new RuntimeException('Foto maksimal 2 MB.');
 $info=@getimagesize($path);if(!$info||!in_array($info['mime'],['image/jpeg','image/png','image/webp'],true))throw new RuntimeException('Gunakan foto JPG, PNG, atau WebP yang valid.');
 if($info[0]>6000||$info[1]>6000||$info[0]*$info[1]>20000000)throw new RuntimeException('Dimensi foto terlalu besar (maksimal 6000 piksel per sisi dan 20 megapiksel).');
 return $info['mime'];
}
