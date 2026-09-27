'use strict';
const form=document.querySelector('#account-form'),otpForm=document.querySelector('#otp-form'),statusBox=document.querySelector('#auth-status');
const csrf=document.querySelector('meta[name="csrf-token"]').content;
let mode='register',challenge='',lastPayload=null,waiting=false,timer=null,availableAt=0;
function status(message){statusBox.textContent=message;}
async function request(payload){
 const controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),25000);
 try{const response=await fetch('api/auth.php',{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify(payload),signal:controller.signal});const body=await response.json();if(!response.ok)throw new Error(body.error||'Permintaan tidak dapat diproses.');return body;}
 catch(error){if(error.name==='AbortError')throw new Error('Koneksi terlalu lama. Tunggu sebentar sebelum meminta kode kembali.');if(error instanceof SyntaxError)throw new Error('Layanan akun belum tersedia. Coba kembali nanti.');throw error;}finally{clearTimeout(timeout);}
}
function cooldown(){clearInterval(timer);availableAt=Date.now()+60000;const update=()=>{const left=Math.max(0,Math.ceil((availableAt-Date.now())/1000));const b=document.querySelector('#resend');b.disabled=left>0||waiting;b.textContent=left?`Kirim ulang (${left} dtk)`:'Kirim ulang kode';if(!left)clearInterval(timer);};update();timer=setInterval(update,1000);}
function busy(value){waiting=value;document.querySelectorAll('.auth-submit,[data-mode],#change-data').forEach(b=>b.disabled=value);document.querySelector('#resend').disabled=value||Date.now()<availableAt;}
document.querySelectorAll('[data-mode]').forEach(button=>button.addEventListener('click',()=>{
 if(waiting)return;mode=button.dataset.mode;challenge='';lastPayload=null;otpForm.hidden=true;form.hidden=false;status('');
 document.querySelectorAll('[data-mode]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));
 const register=document.querySelector('[data-register]'),login=document.querySelector('[data-login]');register.hidden=mode!=='register';login.hidden=mode!=='login';
 register.querySelectorAll('input').forEach(i=>i.disabled=mode!=='register');login.querySelectorAll('input').forEach(i=>i.disabled=mode!=='login');
 document.querySelector('#account-title').textContent=mode==='register'?'Selamat datang di DWA.':'Selamat datang kembali.';
}));
async function send(payload){busy(true);status('Menyiapkan kode verifikasi…');try{const body=await request(payload);challenge=body.challenge;lastPayload=payload;form.hidden=true;otpForm.hidden=false;document.querySelector('#otp').value='';status(body.message);cooldown();document.querySelector('#otp').focus();}catch(error){status(error.message||'Tidak dapat terhubung. Periksa koneksi Anda.');}finally{busy(false);}}
form.addEventListener('submit',event=>{event.preventDefault();if(waiting||form.dataset.ready!=='true')return;
 const payload={action:'request',mode};if(mode==='register'){payload.name=form.elements.name.value.trim();payload.email=form.elements.email.value.trim();payload.whatsapp=form.elements.whatsapp.value.trim();payload.consent=document.querySelector('#consent').checked;}else payload.identifier=form.elements.identifier.value.trim();send(payload);
});
otpForm.addEventListener('submit',async event=>{event.preventDefault();if(waiting)return;busy(true);status('Memverifikasi kode…');try{await request({action:'verify',challenge,code:document.querySelector('#otp').value.trim()});location.replace('dashboard.php');}catch(error){status(error.message||'Tidak dapat terhubung. Coba kembali.');}finally{busy(false);}});
document.querySelector('#resend').addEventListener('click',()=>{if(!waiting&&Date.now()>=availableAt&&lastPayload)send(lastPayload);});
document.querySelector('#change-data').addEventListener('click',()=>{otpForm.hidden=true;form.hidden=false;status('Perbarui data Anda. Permintaan kode baru dibatasi 60 detik.');});
