const cacheName = 'v1';

self.addEventListener('install', event => {
  const response = caches
    .open(cacheName)
    .then(cache => cache.addAll([
      '/',
      '/site.css',
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
      '/icon.icns',
      '/icon.ico',
      '/icon.svg',
    ]));

  event.waitUntil(response);
});

self.addEventListener('fetch', event => {
  const response = fetch(event.request)
    .catch(err => caches
      .open(cacheName)
      .then(cache => cache.match(event.request)));

  event.respondWith(response);
});
