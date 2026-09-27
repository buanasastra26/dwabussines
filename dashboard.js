'use strict';
const installButton=document.querySelector('#install-app'),installStatus=document.querySelector('#install-status');let installPrompt;
if('serviceWorker' in navigator)navigator.serviceWorker.register('sw.js').catch(()=>{installStatus.textContent='Pemasangan belum tersedia. Dashboard tetap dapat digunakan melalui browser.';});
function installed(){return matchMedia('(display-mode: standalone)').matches||navigator.standalone===true;}
if(installed()){installButton.hidden=true;installStatus.textContent='Web app sedang dibuka dari layar utama.';}
window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();installPrompt=event;});
window.addEventListener('appinstalled',()=>{installPrompt=null;installButton.hidden=true;installStatus.textContent='Web app DWA berhasil dipasang.';});
installButton.addEventListener('click',async()=>{if(installPrompt){try{await installPrompt.prompt();const result=await installPrompt.userChoice;installStatus.textContent=result.outcome==='accepted'?'Pemasangan dimulai.':'Pemasangan dibatalkan. Anda tetap bisa memakai dashboard.';}catch{document.querySelector('#install-help').hidden=false;}finally{installPrompt=null;}}else{document.querySelector('#install-help').hidden=false;installStatus.textContent='Pasang melalui menu browser Anda.';}});
document.querySelector('#logout').addEventListener('click',async event=>{event.currentTarget.disabled=true;try{const r=await fetch('api/auth.php',{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({action:'logout'})});if(!r.ok)throw new Error();location.replace('portal.php');}catch{document.querySelector('#dashboard-status').textContent='Belum dapat keluar. Periksa koneksi lalu coba lagi.';event.target.disabled=false;}});
window.addEventListener('pageshow',event=>{if(event.persisted)location.reload();});
