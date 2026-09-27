const menu = document.querySelector('.menu');
const nav = document.querySelector('#navigation');
menu.addEventListener('click', () => {
  const expanded = menu.getAttribute('aria-expanded') !== 'true';
  menu.setAttribute('aria-expanded', String(expanded));
  menu.setAttribute('aria-label', expanded ? 'Tutup navigasi' : 'Buka navigasi');
  nav.classList.toggle('open', expanded);
});
nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
  nav.classList.remove('open'); menu.setAttribute('aria-expanded', 'false'); menu.setAttribute('aria-label', 'Buka navigasi');
}));
document.addEventListener('keydown', event => { if (event.key === 'Escape' && nav.classList.contains('open')) { menu.click(); menu.focus(); } });
document.querySelectorAll('[data-service]').forEach(button => button.addEventListener('click', () => {
  document.querySelector('#service').value = button.dataset.service;
  document.querySelector('#konsultasi').scrollIntoView({behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'});
  document.querySelector('#name').focus({preventScroll:true});
}));
document.querySelector('#consultation-form')?.addEventListener('submit', event => {
  event.preventDefault();
  const name = document.querySelector('#name');
  if (!name.value.trim()) { name.setCustomValidity('Silakan isi nama Anda.'); name.reportValidity(); return; }
  const company = document.querySelector('#company').value.trim();
  const service = document.querySelector('#service').value;
  const message = document.querySelector('#message').value.trim();
  const text = ['Halo DWA Legalitas, saya ingin konsultasi.', 'Nama: ' + name.value.trim(), company ? 'Nama usaha: ' + company : '', 'Layanan: ' + service, message ? 'Kebutuhan: ' + message : ''].filter(Boolean).join('\n');
  const url = 'https://wa.me/62882000119208?text=' + encodeURIComponent(text);
  const status = document.querySelector('#form-status');
  status.textContent = 'Pesan siap. ';
  const link = document.createElement('a'); link.href = url; link.target = '_blank'; link.rel = 'noopener'; link.textContent = 'Buka WhatsApp untuk mengirim →'; link.style.textDecoration = 'underline'; status.append(link);
  window.open(url, '_blank', 'noopener,noreferrer');
});
document.querySelector('#name')?.addEventListener('input', event => event.target.setCustomValidity(''));
document.querySelector('#year').textContent = new Date().getFullYear();
