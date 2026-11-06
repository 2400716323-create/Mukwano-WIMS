const CACHE_NAME = 'mukwano-wims-shell-v1';
const SHELL_FILES = [
    '/offline.html',
    '/manifest.webmanifest',
    '/icons/wims-192.png',
    '/icons/wims-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_FILES)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key.startsWith('mukwano-wims-shell-') && key !== CACHE_NAME)
                .map((key) => caches.delete(key)),
        )),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(async () => (
            (await caches.match('/offline.html')) ?? Response.error()
        )));
        return;
    }

    if (SHELL_FILES.includes(url.pathname)) {
        event.respondWith(
            caches.match(request).then((cached) => cached ?? fetch(request)),
        );
    }
});
