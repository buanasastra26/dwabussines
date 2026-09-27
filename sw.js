'use strict';
const CACHE='dwa-public-v1';const FALLBACK=new URL('offline.html',self.registration.scope).href;
const PUBLIC=['offline.html','icons/app-192.png','icons/app-512.png'];
self.addEventListener('install',event=>{event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(PUBLIC)));self.skipWaiting();});
self.addEventListener('activate',event=>{event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k.startsWith('dwa-public-')&&k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim()));});
self.addEventListener('fetch',event=>{
 const url=new URL(event.request.url);if(event.request.method!=='GET'||url.origin!==self.location.origin)return;
 // Account pages and API responses are NEVER written to Cache Storage.
 if(event.request.mode==='navigate'){event.respondWith(fetch(event.request,{cache:'no-store'}).catch(()=>caches.match(FALLBACK)));return;}
 if(PUBLIC.some(p=>new URL(p,self.registration.scope).href===url.href))event.respondWith(caches.match(event.request).then(cached=>cached||fetch(event.request)));
});
