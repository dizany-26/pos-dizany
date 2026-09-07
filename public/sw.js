const CACHE_VERSION = 'dizany-pwa-v2';
const OFFLINE_URL = '/offline.html';
const APP_SHELL = [
    OFFLINE_URL,
    '/images/pwa/icon-192.png?v=2',
    '/images/pwa/icon-512.png?v=2'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then(cache => cache.addAll(APP_SHELL))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys
                    .filter(key => key.startsWith('dizany-pwa-') && key !== CACHE_VERSION)
                    .map(key => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Las páginas autenticadas y respuestas del sistema nunca se guardan.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    const esRecursoEstatico = [
        '/build/',
        '/css/',
        '/js/',
        '/images/',
        '/uploads/logos/',
        '/uploads/config/'
    ].some(prefix => url.pathname.startsWith(prefix));

    if (!esRecursoEstatico) return;

    event.respondWith(
        caches.match(request).then(cached => {
            const update = fetch(request).then(response => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(CACHE_VERSION).then(cache => cache.put(request, copy));
                }
                return response;
            });

            return cached || update;
        })
    );
});
