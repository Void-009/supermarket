const CACHE_NAME = 'mdelight-v6';

// Base path derived from where the service worker is served
const BASE_PATH = self.registration.scope;

function joinBase(path) {
    if (path.indexOf(BASE_PATH) === 0) return path;
    return BASE_PATH.replace(/\/$/, '') + path;
}

function relativePath(url) {
    const path = url.pathname;
    if (path.indexOf(BASE_PATH) === 0) {
        return path.slice(BASE_PATH.length - 1); // Ensures path starts with '/'
    }
    return path;
}

const STATIC_ASSETS = [
    joinBase('/'),
    joinBase('/manifest.json'),
    joinBase('/android-chrome-192x192.png'),
    joinBase('/android-chrome-512x512.png'),
    joinBase('/apple-touch-icon.png'),
    joinBase('/favicon.ico'),
    joinBase('/favicon-16x16.png'),
    joinBase('/favicon-32x32.png'),
    joinBase('/favicon.svg'),
];

// FIX #1: Cache assets individually so missing files don't break installation
self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return Promise.allSettled(
                STATIC_ASSETS.map(function (assetUrl) {
                    return cache.add(assetUrl).catch(function (err) {
                        console.warn('[PWA SW] Failed to cache non-critical asset:', assetUrl);
                    });
                })
            );
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (names) {
            return Promise.all(
                names
                    .filter(function (name) {
                        return name !== CACHE_NAME;
                    })
                    .map(function (name) {
                        return caches.delete(name);
                    })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', function (event) {
    var request = event.request;

    if (request.method !== 'GET') return;

    var url = new URL(request.url);

    if (url.origin !== self.location.origin) return;

    // Skip excluded dynamic routes unless it's a static file or navigate request
    if (isExcluded(url, request)) return;

    var isNavigate = request.mode === 'navigate';

    // Network-first everywhere so freshly deployed bundles are always fetched;
    // cache is used only as an offline fallback. Prevents stale code after updates.
    if (isNavigate) {
        event.respondWith(networkFirst(request, joinBase('/')));
    } else {
        event.respondWith(networkFirst(request));
    }
});

// FIX #2: Explicitly bypass exclusion for manifest.json
function isExcluded(url, request) {
    var path = relativePath(url);

    if (path === '/manifest.json') return false;

    return (
        path.startsWith('/api/') ||
        path.startsWith('/admin/') ||
        path.startsWith('/broadcasting/') ||
        path.startsWith('/push/') ||
        path.startsWith('/_debugbar/') ||
        path === '/push' ||
        path === '/cart' ||
        path.startsWith('/cart/') ||
        path === '/checkout' ||
        path.startsWith('/checkout/') ||
        /\.json$/i.test(path) ||
        (request.headers.get('Accept') || '').indexOf('application/json') !== -1
    );
}

function networkFirst(request, offlineFallback) {
    return fetch(request)
        .then(function (response) {
            if (response.ok) {
                var cloned = response.clone();
                caches.open(CACHE_NAME).then(function (cache) {
                    cache.put(request, cloned);
                });
            }
            return response;
        })
        .catch(function () {
            return caches.match(request).then(function (cached) {
                if (cached) return cached;
                if (offlineFallback) {
                    return caches.match(offlineFallback).then(function (fallback) {
                        return fallback || new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/plain' } });
                    });
                }
                return new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/plain' } });
            });
        });
}

self.addEventListener('push', function (event) {
    var data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { title: 'M-DELIGHT SINGER STORES', body: event.data ? event.data.text() : '' };
    }

    var title = data.title || 'M-DELIGHT SINGER STORES';
    var options = {
        body: data.body || '',
        icon: joinBase('/android-chrome-192x192.png'),
        badge: joinBase('/favicon-32x32.png'),
        vibrate: [200, 100, 200],
        data: data.data || {},
        actions: data.actions || [],
        tag: data.tag || 'default',
        renotify: data.renotify || false,
        requireInteraction: true,
    };

    if (data.image) {
        options.image = data.image;
    }

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    var data = event.notification.data || {};
    var url = data.url || joinBase('/');

    if (event.action) {
        var actionUrl = (data.actions && data.actions[event.action]) || (data.actionUrls && data.actionUrls[event.action]);
        if (actionUrl) {
            url = actionUrl;
        }
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url.indexOf(url) !== -1 && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});