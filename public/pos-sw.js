/*
 * SaloonHub POS service worker.
 *
 * Keeps the Quick Sale page usable when the internet drops:
 *  - the Quick Sale page is network-first, so it is always fresh online and served from cache offline;
 *  - static assets (/assets, /js) are cached as they load;
 *  - a Quick Sale form submitted while offline is saved to IndexedDB and synced later by
 *    /js/pos-offline.js, which posts it again with the same client_uuid so it is never saved twice.
 *
 * Nothing else is cached or intercepted.
 */
const CACHE = 'saloonhub-pos-v1';
const QUICK_SALE_PATH = '/billing/quick-sale';
const DB_NAME = 'saloonhub-pos';
const STORE = 'offline-sales';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter((key) => key.startsWith('saloonhub-pos-') && key !== CACHE).map((key) => caches.delete(key)));
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    // Sent on logout, so the next person at the counter cannot open the cached page.
    if (event.data === 'clear-pos-cache') {
        event.waitUntil(caches.delete(CACHE));
    }
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.method === 'GET' && request.mode === 'navigate' && url.pathname === QUICK_SALE_PATH) {
        event.respondWith(networkFirst(request));
        return;
    }

    if (request.method === 'GET' && (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/js/'))) {
        event.respondWith(staleWhileRevalidate(request));
        return;
    }

    if (request.method === 'POST' && request.mode === 'navigate' && url.pathname === QUICK_SALE_PATH) {
        event.respondWith(submitOrQueue(request));
    }
});

async function networkFirst(request) {
    const cache = await caches.open(CACHE);

    try {
        const response = await fetch(request);

        // Only a real page is kept; a redirect to the login page is not.
        if (response.ok && ! response.redirected) {
            await cache.put(QUICK_SALE_PATH, response.clone());
        }

        return response;
    } catch (error) {
        const cached = await cache.match(QUICK_SALE_PATH);

        return cached || new Response('<h1>Offline</h1><p>Open Quick Sale once while online so it can work offline.</p>', {
            status: 503,
            headers: {'Content-Type': 'text/html; charset=utf-8'},
        });
    }
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(CACHE);
    const cached = await cache.match(request);
    const network = fetch(request).then((response) => {
        if (response.ok) {
            cache.put(request, response.clone());
        }

        return response;
    }).catch(() => cached);

    return cached || network;
}

async function submitOrQueue(request) {
    const copy = request.clone();

    try {
        return await fetch(request);
    } catch (error) {
        const form = await copy.formData();
        const entries = [];
        form.forEach((value, key) => entries.push([key, String(value)]));

        const get = (name) => (entries.find(([key]) => key === name) || [])[1] || '';
        const id = get('client_uuid') || (self.crypto.randomUUID ? self.crypto.randomUUID() : String(Date.now()));
        const createdAt = new Date();
        const reference = 'OFF-' + createdAt.toISOString().slice(2, 10).replace(/-/g, '') + '-' + id.slice(0, 4).toUpperCase();

        entries.push(['offline', '1'], ['offline_reference', reference], ['offline_created_at', createdAt.toISOString()]);

        await saveSale({
            id,
            reference,
            userId: get('_pos_user'),
            createdAt: createdAt.toISOString(),
            estimatedTotal: Number(get('offline_estimated_total') || 0),
            status: 'pending',
            error: null,
            entries,
        });

        return Response.redirect(QUICK_SALE_PATH + '?offline_saved=' + encodeURIComponent(id), 303);
    }
}

function openDb() {
    return new Promise((resolve, reject) => {
        const open = indexedDB.open(DB_NAME, 1);
        open.onupgradeneeded = () => open.result.createObjectStore(STORE, {keyPath: 'id'});
        open.onsuccess = () => resolve(open.result);
        open.onerror = () => reject(open.error);
    });
}

async function saveSale(sale) {
    const db = await openDb();

    await new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, 'readwrite');
        tx.objectStore(STORE).put(sale);
        tx.oncomplete = resolve;
        tx.onerror = () => reject(tx.error);
    });
}
