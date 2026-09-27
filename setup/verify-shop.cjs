const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),crypto=require('node:crypto');
const {spawn,spawnSync}=require('node:child_process');
const root=path.resolve(__dirname,'..'),php=path.resolve(process.argv[2]),temp=fs.mkdtempSync(path.join(os.tmpdir(),'dwa-shop-'));
let server,checks=0;function assert(ok,message){if(!ok)throw Error(message);checks++;}
(async()=>{try{
for(const file of ['dashboard.php','product.php','cart.php','checkout.php','api/cart.php','server/catalog.php','server/shop-layout.php']){const r=spawnSync(php,['-l',path.join(root,file)],{encoding:'utf8'});assert(r.status===0,'PHP syntax: '+file+' '+r.stdout+r.stderr);}
assert(spawnSync(process.execPath,['--check',path.join(root,'shop.js')]).status===0,'JS syntax');
const sid=crypto.randomBytes(20).toString('hex'),csrf=crypto.randomBytes(32).toString('hex');
const seed=path.join(temp,'seed.php');fs.writeFileSync(seed,`<?php session_name('DWA_SESSION');session_id('${sid}');session_start();$_SESSION=['user'=>['id'=>99999,'name'=>'Local QA','email'=>'qa@example.test','whatsapp'=>null],'expires'=>time()+3600,'csrf'=>'${csrf}'];session_write_close();`);
assert(spawnSync(php,['-d','session.save_path='+temp,seed]).status===0,'Local test session');
server=spawn(php,['-d','session.save_path='+temp,'-S','127.0.0.1:4187','-t',root],{stdio:['ignore','ignore','pipe']});
await new Promise((resolve,reject)=>{const timeout=setTimeout(()=>reject(Error('Server start timeout')),8000);server.stderr.on('data',chunk=>{if(chunk.toString().includes('Development Server')){clearTimeout(timeout);resolve();}});server.on('error',reject);});
const base='http://127.0.0.1:4187/';async function req(url,options={}){return fetch(base+url,{...options,redirect:'manual',headers:{Cookie:'DWA_SESSION='+sid,...options.headers}});}
async function cart(data,token=csrf){return req('api/cart.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':token},body:JSON.stringify(data)});}
let r=await req('dashboard.php'),html=await r.text();assert(r.status===200&&html.includes('4 bonus')&&html.includes('shop.css'),'Dashboard renders');assert(html.includes('Rp1.000.000')&&html.includes('Rp2.500.000'),'Promo prices');
r=await req('product.php?id=pt-perorangan');html=await r.text();for(const label of ['NPWP','NIB KBLI Standar','10 KBLI Usaha','SKT','SK Kementerian','Surat Permohonan Buka Rekening','Stempel PT','Kartu Nama Direktur'])assert(html.includes(label),'Product inclusion '+label);
assert((await req('product.php?id=unknown')).status===404,'Unknown product');assert((await req('checkout.php')).headers.get('location')==='cart.php','Empty checkout redirects');
assert((await cart({action:'add',id:'pt-perorangan'},'wrong')).status===403,'CSRF protected');assert((await cart({action:'add',id:'website'})).status===400,'Unpriced service cannot be bought');assert((await cart({action:'set',id:'pt-perorangan',qty:-1})).status===400,'Negative quantity denied');assert((await cart({action:'set',id:'pt-perorangan',qty:11})).status===400,'Maximum quantity enforced');
r=await cart({action:'add',id:'pt-perorangan',price:1});assert((await r.json()).count===1,'Add product');r=await cart({action:'set',id:'pt-perorangan',qty:2});assert((await r.json()).count===2,'Update quantity');html=await(await req('cart.php')).text();assert(html.includes('Rp2.000.000'),'Server price ignores client tampering');
let form=new URLSearchParams({csrf,recipient:'Local QA',phone:'081234567890',address:'Jalan Contoh 1',village:'Contoh',district:'Contoh',city:'Bandung',province:'Jawa Barat',postal:'40111',note:'<script>alert(1)</script>',payment:'bank'});
const invalid=new URLSearchParams(form);invalid.set('postal','abc');html=await(await req('checkout.php',{method:'POST',body:invalid})).text();assert(html.includes('Kode pos harus 5 angka.'),'Checkout validation');
r=await req('checkout.php',{method:'POST',body:form});assert(r.status===302&&r.headers.get('location')==='checkout.php?review=1','Checkout review redirect');html=await(await req('checkout.php?review=1')).text();assert(html.includes('Belum ada pembayaran')&&html.includes('wa.me/62882000119208'),'Manual confirmation');assert(html.includes('&lt;script&gt;alert(1)&lt;/script&gt;')&&!html.includes('<script>alert(1)</script>'),'Address notes escaped');assert(html.includes('Rp2.000.000'),'Review total');
await cart({action:'set',id:'pt-perorangan',qty:0});assert((await req('checkout.php')).headers.get('location')==='cart.php','Remove product and block empty checkout');
for(const page of ['dashboard.php','product.php','cart.php','checkout.php'])assert((await fetch(base+page,{redirect:'manual'})).headers.get('location')==='portal.php','Auth gate '+page);
assert((await fetch(base+'api/cart.php',{method:'POST'})).status===401,'Cart API auth gate');
assert(fs.statSync(path.join(root,'assets/dashboard-background.png')).size>0,'Reference background available');
console.log(`PASS: ${checks} final checks. No production OTP, payment, or WhatsApp message sent.`);
}finally{if(server){server.kill();await new Promise(resolve=>{server.once('exit',resolve);setTimeout(resolve,1000);});}fs.rmSync(temp,{recursive:true,force:true});}})().catch(error=>{console.error(error.message);process.exitCode=1;});
