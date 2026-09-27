const fs=require('node:fs');
const home=fs.readFileSync('index.html','utf8').replace(/<a\b[^>]*>/g,tag=>{
 if(tag.includes('class="skip"'))return tag;
 return tag.replace(/href="[^"]*"/,'href="portal.php"').replace(/ target="[^"]*"/g,'').replace(/ aria-label="[^"]*"/g,'');
}).replace('Jelajahi Layanan Kami','Buat akun DWA').replace('Mulai konsultasi <span>','Daftar / Masuk <span>').replace('<span>◉</span> Chat WhatsApp','<span>◉</span> Portal Klien');
fs.writeFileSync('index.html',home);
let services=fs.readFileSync('layanan.html','utf8');
services=services.replaceAll('href="layanan.html','href="layanan.php').replace('href="index.html" aria-label="DWA Legalitas beranda"','href="dashboard.php" aria-label="Dashboard klien DWA"').replace('<a href="index.html">Beranda</a>','<a href="dashboard.php">Dashboard</a>').replace('placeholder="Nama lengkap" required','placeholder="Nama lengkap" value="<?=e($user[\'name\'])?>" required');
services=services.replace('</head>','<link rel="manifest" href="manifest.webmanifest"></head>');
fs.writeFileSync('layanan.php',"<?php require __DIR__.'/server/bootstrap.php'; $user=require_user(); ?>\n"+services);
fs.writeFileSync('layanan.html','<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="refresh" content="0;url=portal.php"><title>Layanan DWA — Portal Klien</title></head><body><p>Untuk melihat layanan DWA, <a href="portal.php">daftar atau masuk ke portal klien</a>.</p></body></html>');
let testimony=fs.readFileSync('testimoni.html','utf8').replace(/href="(?:layanan|index)\.html#konsultasi"/g,'href="portal.php"').replaceAll('href="layanan.html"','href="portal.php"');fs.writeFileSync('testimoni.html',testimony);
fs.appendFileSync('app.js',"\nwindow.addEventListener('pageshow', event => { if (event.persisted && location.pathname.endsWith('/layanan.php')) location.reload(); });\n");
