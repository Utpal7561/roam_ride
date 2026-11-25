const CACHE_NAME = 'rome-and-ride-v1.0';
const STATIC_ASSETS = [
  './',
  'offline.html',
  'assets/css/custom.css',
  'assets/images/logo.jpg',
  'manifest.json'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // NEVER cache API requests, private documents, auth tokens, or administrative data
  if (url.pathname.includes('/api/') || 
      url.pathname.includes('/download') || 
      url.pathname.includes('/storage/private_documents') ||
      event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }
      return fetch(event.request).catch(() => {
        // If navigating to an HTML page and offline, return offline.html
        if (event.request.headers.get('accept')?.includes('text/html')) {
          return caches.match('offline.html');
        }
      });
    })
  );
});
