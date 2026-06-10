/**
 * Service Worker — solo assets estáticos. Network-first para todo lo demás.
 */
var CACHE = 'lab-static-v2';
var ASSETS = [
    'css/vendor/bootstrap.min.css',
    'css/dom.css',
    'css/dashboard.css',
    'assets/css/ynex.css',
    'assets/css/app.css',
    'css/modern-ui.css',
    'js/jquery-3.7.1.min.js',
    'js/vendor/bootstrap.bundle.min.js',
    'js/common.js',
    'js/modern/command-palette.js',
    'js/modern/data-grid-enhance.js',
    'js/modern/offline-pwa.js'
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE).then(function (cache) {
            return cache.addAll(ASSETS.map(function (p) { return new URL(p, self.registration.scope).href; }))
                .catch(function () { /* parcial OK */ });
        }).then(function () { return self.skipWaiting(); })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(keys.filter(function (k) { return k !== CACHE; }).map(function (k) { return caches.delete(k); }));
        }).then(function () { return self.clients.claim(); })
    );
});

self.addEventListener('fetch', function (event) {
    var req = event.request;
    if (req.method !== 'GET') return;
    var url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    var isStatic = /\.(css|js|woff2?|png|jpg|svg|ico)(\?|$)/i.test(url.pathname);
    if (!isStatic) return;

    event.respondWith(
        fetch(req).then(function (res) {
            if (res && res.ok) {
                var copy = res.clone();
                caches.open(CACHE).then(function (c) { c.put(req, copy); });
            }
            return res;
        }).catch(function () {
            return caches.match(req);
        })
    );
});
