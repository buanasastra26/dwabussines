const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm'),assert=require('node:assert/strict'),{spawn,spawnSync}=require('node:child_process');
const root=path.resolve(__dirname,'..'),php=process.argv[2];let total=0;
function check(ok,label){assert.ok(ok,label);total++;}
function files(dir){return fs.readdirSync(dir,{withFileTypes:true}).flatMap(e=>e.name==='.git'?[]:e.isDirectory()?files(path.join(dir,e.name)):[path.join(dir,e.name)]);}
(async()=>{
 for(const f of files(root).filter(f=>f.endsWith('.php'))){const r=spawnSync(php,['-l',f],{encoding:'utf8'});check(r.status===0,r.stdout+r.stderr);}
 for(const f of ['app.js','portal.js','dashboard.js','sw.js']){new vm.Script(fs.readFileSync(path.join(root,f),'utf8'));total++;}
 const home=fs.readFileSync(path.join(root,'index.html'),'utf8');for(const a of home.matchAll(/<a\b[^>]*>/g)){if(!a[0].includes('class="skip"'))check(a[0].includes('href="portal.php"'),'Homepage link must lead to portal');}
 const manifest=JSON.parse(fs.readFileSync(path.join(root,'manifest.webmanifest'),'utf8'));check(manifest.start_url==='./dashboard.php','PWA starts with authenticated route');
 for(const icon of manifest.icons)check(fs.existsSync(path.join(root,icon.src)),'PWA icon');
 for(const name of ['portal.php','dashboard.php','layanan.php','privasi.html']){const html=fs.readFileSync(path.join(root,name),'utf8');for(const m of html.matchAll(/(?:src|href)="([^"<>]+)"/g)){if(/^(https?:|mailto:|tel:|#|data:)/.test(m[1]))continue;const ref=m[1].split('#')[0];check(fs.existsSync(path.join(root,ref)),'Missing '+ref);}}
 check(!fs.existsSync(path.join(root,'config.local.php')),'No local secret config in project');
 const server=spawn(php,['-S','127.0.0.1:4186','-t',root],{stdio:['ignore','pipe','pipe']});let output='';
 try {
  await new Promise((resolve,reject)=>{const timeout=setTimeout(()=>reject(new Error('PHP server timeout: '+output)),12000);server.stderr.on('data',b=>{output+=b;if(output.includes('Development Server')){clearTimeout(timeout);resolve();}});server.on('error',reject);server.on('exit',code=>{if(code)reject(new Error(output));});});
  const base='http://127.0.0.1:4186/';const page=await fetch(base+'portal.php');check(page.status===200,'Portal renders');const html=await page.text();check(html.includes('Portal sedang disiapkan.'),'Fail closed until SMTP configured');const csrf=html.match(/name="csrf-token" content="([^"]+)"/)[1];const cookie=page.headers.get('set-cookie');check(cookie.includes('secure')&&cookie.includes('HttpOnly')&&cookie.includes('SameSite=Lax'),'Secure session cookie');
  for(const route of ['dashboard.php','layanan.php']){const r=await fetch(base+route,{redirect:'manual'});check(r.status===302&&r.headers.get('location')==='portal.php','Protected route '+route);check(r.headers.get('cache-control').includes('no-store'),'Private cache policy');}
  const headers={'Content-Type':'application/json','Cookie':cookie.split(';')[0]};const noCSRF=await fetch(base+'api/auth.php',{method:'POST',headers,body:JSON.stringify({action:'request'})});check(noCSRF.status===403,'CSRF is required');headers['X-CSRF-Token']=csrf;
  const unavailable=await fetch(base+'api/auth.php',{method:'POST',headers,body:JSON.stringify({action:'request',mode:'register'})});check(unavailable.status===503,'Auth blocked while unconfigured');
  const logout=await fetch(base+'api/auth.php',{method:'POST',headers,body:JSON.stringify({action:'logout'})});check(logout.status===200,'Logout clears session');
  check((await fetch(base+'manifest.webmanifest')).status===200,'Manifest available');check((await fetch(base+'offline.html')).status===200,'Offline fallback available');
  console.log(`PASS: ${total} assertions in one final suite (PHP syntax, JS syntax, routes, CSRF, secure cookie, disabled configuration, PWA assets).`);
  console.log('NOT RUN: production MySQL + SMTP OTP delivery and actual device installation, pending hosting/email configuration.');
 } finally {server.kill();}
})().catch(error=>{console.error(error);process.exitCode=1;});
