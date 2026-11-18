/**
 * Webxpress service worker.
 *
 * The cache name is stamped from the `v` query parameter on the registration
 * URL (see resources/js/app.js), which the app derives from the newest build
 * artefact. Any rebuild or edit to this file therefore rolls the cache, and
 * `activate` purges every cache that is not the current one.
 *
 * Strategies:
 *   - Navigations: network-first, falling back to the cached copy, then to
 *     the cached home page. Never a cross-content-type substitution.
 *   - Static assets: stale-while-revalidate, with a bounded runtime cache so
 *     a heavy image-browsing session cannot grow Cache Storage without limit.
 */

const VERSION = new URL(self.location.href).searchParams.get('v') || 'dev';
const CACHE = `webxpress-shell-v${VERSION}`;
const RUNTIME_CACHE = `webxpress-runtime-v${VERSION}`;
const CACHE_PREFIX = 'webxpress-';
const MAX_RUNTIME_ENTRIES = 120;

const OFFLINE_URL = '/offline.html';
const SHELL = ['/', '/work', '/services', '/about', '/insights', '/contact', OFFLINE_URL];

/** Trim the runtime cache down to the most recent MAX_RUNTIME_ENTRIES. */
async function trimCache(cacheName, maxEntries) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();

    if (keys.length <= maxEntries) return;

    // Cache Storage preserves insertion order, so the head is the oldest.
    await Promise.all(keys.slice(0, keys.length - maxEntries).map((key) => cache.delete(key)));
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE)
            // Individually, so one failing route cannot abort the whole install.
            .then((cache) => Promise.allSettled(SHELL.map((url) => cache.add(url))))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key.startsWith(CACHE_PREFIX) && key !== CACHE && key !== RUNTIME_CACHE)
                        .map((key) => caches.delete(key))
                )
            )
            .then(() => self.clients.claim())
    );
});

async function networkFirst(request) {
    const cache = await caches.open(CACHE);

    try {
        const response = await fetch(request);

        if (response.ok) {
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        return (
            (await cache.match(request)) ??
            (await cache.match(OFFLINE_URL)) ??
            (await cache.match('/')) ??
            new Response('<h1>Offline</h1>', {
                status: 503,
                headers: { 'Content-Type': 'text/html; charset=utf-8' },
            })
        );
    }
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    const cached = await cache.match(request);

    const network = fetch(request)
        .then((response) => {
            if (response.ok) {
                cache.put(request, response.clone()).then(() => trimCache(RUNTIME_CACHE, MAX_RUNTIME_ENTRIES));
            }

            return response;
        })
        .catch(() => null);

    return cached ?? (await network) ?? Response.error();
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') return;
    if (new URL(request.url).origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request));
        return;
    }

    // Hashed build output and Vite's dev client must never be served stale.
    if (request.url.includes('/build/') || request.url.includes('@vite') || request.url.includes('@react')) {
        return;
    }

    event.respondWith(staleWhileRevalidate(request));
});
