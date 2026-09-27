<?php
function catalog(): array {
    return [
        'pt-perorangan'=>['name'=>'PT Perorangan','category'=>'Legalitas','icon'=>'building','price'=>1000000,'old_price'=>2500000,'label'=>'Paket lengkap untuk UMKM','description'=>'Layanan pendampingan pendirian PT Perorangan untuk pelaku UMKM yang ingin menyiapkan legalitas dan identitas usahanya. Kesesuaian usaha dan dokumen dikonfirmasi bersama tim DWA.','includes'=>['NPWP','NIB KBLI Standar','10 KBLI Usaha','SKT','SK Kementerian','Surat Permohonan Buka Rekening'],'bonuses'=>['Company Profile (Compro)','Landing Page','Stempel PT','Kartu Nama Direktur']],
        'pt-umum'=>['name'=>'PT Umum','category'=>'Legalitas','icon'=>'building','price'=>null,'label'=>'Pendirian badan usaha','description'=>'Pendampingan persiapan pendirian PT untuk kebutuhan usaha Anda. Tim DWA membantu membahas dokumen, ruang lingkup, dan penawaran sebelum pemesanan.'],
        'cv'=>['name'=>'CV','category'=>'Legalitas','icon'=>'document','price'=>null,'label'=>'Pendirian badan usaha','description'=>'Layanan pendampingan pendirian CV. Konsultasikan rencana usaha dan kebutuhan dokumen untuk memperoleh rincian layanan serta penawaran.'],
        'basic'=>['name'=>'Paket Basic','category'=>'UMKM','icon'=>'spark','price'=>null,'label'=>'Mulai langkah usaha','description'=>'Diskusikan kebutuhan awal pengembangan usaha Anda bersama DWA. Rincian hasil layanan dan harga disusun setelah konsultasi.'],
        'umkm-siap'=>['name'=>'Paket UMKM Siap','category'=>'UMKM','icon'=>'bag','price'=>null,'label'=>'Siapkan usaha berkembang','description'=>'Pendampingan pengembangan UMKM sesuai tahap dan kebutuhan usaha. Ruang lingkup dan hasil yang diterima dikonfirmasi sebelum pemesanan.'],
        'pro'=>['name'=>'Paket Pro','category'=>'UMKM','icon'=>'spark','price'=>null,'label'=>'Kembangkan potensi bisnis','description'=>'Konsultasi pengembangan usaha untuk menyusun kebutuhan bisnis yang lebih menyeluruh. Hubungi DWA untuk rincian paket dan harga.'],
        'website'=>['name'=>'Website','category'=>'Digital','icon'=>'screen','price'=>null,'label'=>'Hadir profesional secara online','description'=>'Pembuatan website sesuai kebutuhan usaha. Halaman, fitur, konten, dan harga ditentukan melalui pembahasan bersama tim DWA.'],
        'aplikasi'=>['name'=>'Aplikasi','category'=>'Digital','icon'=>'phone','price'=>null,'label'=>'Layanan dalam genggaman','description'=>'Pengembangan aplikasi untuk kebutuhan usaha Anda. Platform, fitur, hasil pekerjaan, dan penawaran disepakati setelah konsultasi.'],
        'software'=>['name'=>'Software Manajemen','category'=>'Digital','icon'=>'grid','price'=>null,'label'=>'Kelola usaha lebih terarah','description'=>'Solusi perangkat lunak sesuai alur operasional usaha. Modul, integrasi, dan ruang lingkup dibahas sebelum pemesanan.'],
    ];
}
function shop_icon(string $name): string {
    $paths=[
      'building'=>'M4 21V7l8-4 8 4v14M2 21h20M9 21v-5h6v5M8 9h1m6 0h1M8 12h1m6 0h1',
      'document'=>'M6 3h9l4 4v14H6zM14 3v5h5M9 12h7M9 16h7',
      'screen'=>'M3 4h18v13H3zM8 21h8M12 17v4',
      'phone'=>'M7 2h10v20H7zM10 18h4',
      'grid'=>'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
      'spark'=>'m12 2 3 7 7 3-7 3-3 7-3-7-7-3 7-3z',
      'bag'=>'M4 7h16l1 14H3zM8 8V6a4 4 0 0 1 8 0v2',
      'stamp'=>'M5 18h14v3H5zM8 15V9a4 4 0 0 1 8 0v6l3 3H5z',
      'card'=>'M3 5h18v14H3zM7 10h3v4H7zM14 10h4M14 14h4',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="'.($paths[$name]??$paths['bag']).'"/></svg>';
}
function shop_cart(): array {
    $products=catalog();$rows=[];
    foreach (($_SESSION['shop_cart']??[]) as $id=>$qty) if(isset($products[$id]) && is_int($products[$id]['price']) && is_int($qty) && $qty>0 && $qty<=10) $rows[$id]=['product'=>$products[$id],'qty'=>$qty];
    return $rows;
}
function rupiah(int $amount): string {return 'Rp'.number_format($amount,0,',','.');}
