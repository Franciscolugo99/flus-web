'use strict';

const VERSION = 'flus-portal-1';

self.addEventListener('install', function() {
  self.skipWaiting();
});

self.addEventListener('activate', function(event) {
  event.waitUntil((async function() {
    const cacheNames = await caches.keys();
    await Promise.all(cacheNames
      .filter(function(name) { return name.startsWith('flus-portal-') && name !== VERSION; })
      .map(function(name) { return caches.delete(name); }));
    await self.clients.claim();
  })());
});

// Private portal pages and commercial data are always requested from the server.
self.addEventListener('fetch', function(event) {
  if (event.request.method !== 'GET' || new URL(event.request.url).origin !== self.location.origin) {
    return;
  }
  event.respondWith(fetch(event.request));
});
