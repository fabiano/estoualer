const cacheName = 'v3';

self.addEventListener('install', event => {
  const response = caches
    .open(cacheName)
    .then(cache => cache.addAll([
      '/',
      '/site.css',
      '/newsreader.woff2',
      '/site.js',
      '/icon-16.png',
      '/icon-20.png',
      '/icon-24.png',
      '/icon-32.png',
      '/icon-48.png',
      '/icon-64.png',
      '/icon-128.png',
      '/icon-256.png',
      '/icon-512.png',
      '/icon-1024.png',
      '/icon-2048.png',
      '/icon-4096.png',
      '/icon-maskable-512.png',
      '/apple-touch-icon.png',
      '/icon.ico',
      '/icon.svg',
    ]));

  event.waitUntil(response);
});

self.addEventListener('activate', event => {
  const response = caches
    .keys()
    .then(keys => Promise.all(keys
      .filter(key => key !== cacheName)
      .map(key => caches.delete(key))));

  event.waitUntil(response);
});

self.addEventListener('fetch', event => {
  const response = fetch(event.request)
    .catch(err => caches
      .open(cacheName)
      .then(cache => cache.match(event.request)));

  event.respondWith(response);
});
