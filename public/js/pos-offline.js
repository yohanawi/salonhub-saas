/*
 * SaloonHub offline POS: registers the service worker (/pos-sw.js), shows an offline badge, and
 * syncs Quick Sales that were saved on this device while the internet was down.
 *
 * Loaded on every page for users who can sell. Configured by data attributes on its <script> tag:
 * data-user, data-store-url, data-token-url.
 *
 * window.posOffline exposes list(), sync(), retry(id) and discard(id); the "pos-offline:changed"
 * event fires on document whenever the queue changes.
 */
(function () {
    const script = document.currentScript;
    const config = {
        user: script?.dataset.user || '',
        storeUrl: script?.dataset.storeUrl || '/billing/quick-sale',
        tokenUrl: script?.dataset.tokenUrl || '/billing/quick-sale/token',
    };
    const DB_NAME = 'saloonhub-pos';
    const STORE = 'offline-sales';
    let syncing = false;

    if ('serviceWorker' in navigator && window.isSecureContext) {
        navigator.serviceWorker.register('/pos-sw.js').catch(() => {});
    }

    // Logging out clears the cached POS page, so the next user cannot open it.
    document.addEventListener('submit', function (event) {
        if (event.target.action && /\/logout$/.test(new URL(event.target.action, location.href).pathname)) {
            navigator.serviceWorker?.controller?.postMessage('clear-pos-cache');
        }
    }, true);

    function openDb() {
        return new Promise((resolve, reject) => {
            const open = indexedDB.open(DB_NAME, 1);
            open.onupgradeneeded = () => open.result.createObjectStore(STORE, {keyPath: 'id'});
            open.onsuccess = () => resolve(open.result);
            open.onerror = () => reject(open.error);
        });
    }

    async function run(mode, action) {
        const db = await openDb();

        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, mode);
            const result = action(tx.objectStore(STORE));
            tx.oncomplete = () => resolve(result?.result ?? result);
            tx.onerror = () => reject(tx.error);
        });
    }

    async function list() {
        if (! window.indexedDB) {
            return [];
        }

        const all = await run('readonly', (store) => store.getAll());

        return (all || []).sort((a, b) => a.createdAt.localeCompare(b.createdAt));
    }

    const save = (sale) => run('readwrite', (store) => store.put(sale));
    const remove = (id) => run('readwrite', (store) => store.delete(id));
    const changed = () => document.dispatchEvent(new CustomEvent('pos-offline:changed'));

    function firstError(errors) {
        const messages = Object.values(errors || {}).flat();

        return messages.length ? messages.join(' ') : 'The sale could not be saved.';
    }

    async function send(sale, token) {
        const body = new FormData();
        sale.entries.forEach(([key, value]) => body.append(key, key === '_token' ? token : value));

        return fetch(config.storeUrl, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token},
        });
    }

    /**
     * Posts this user's pending sales in the order they were taken. Stops at the first network or
     * login problem so the order is kept; a rejected sale is marked failed for the cashier to fix.
     */
    async function sync() {
        if (syncing || ! navigator.onLine) {
            return;
        }

        syncing = true;

        try {
            const pending = (await list()).filter((sale) => sale.status === 'pending' && String(sale.userId) === String(config.user));

            if (! pending.length) {
                return;
            }

            const tokenResponse = await fetch(config.tokenUrl, {credentials: 'same-origin', headers: {'Accept': 'application/json'}});

            if (! tokenResponse.ok) {
                return;
            }

            const {token} = await tokenResponse.json();

            for (const sale of pending) {
                let response;

                try {
                    response = await send(sale, token);
                } catch (error) {
                    break;
                }

                if (response.ok) {
                    const invoice = await response.json();
                    await remove(sale.id);
                    document.dispatchEvent(new CustomEvent('pos-offline:synced', {detail: {sale, invoice}}));
                    window.toastr?.success('Offline sale ' + sale.reference + ' saved as ' + invoice.invoice_number + '.');
                } else if (response.status === 422) {
                    const payload = await response.json().catch(() => ({}));
                    await save({...sale, status: 'failed', error: firstError(payload.errors)});
                } else {
                    // 401/419: signed out or session expired. Keep the queue for after login.
                    break;
                }
            }
        } finally {
            syncing = false;
            changed();
        }
    }

    async function retry(id) {
        const sale = (await list()).find((item) => item.id === id);

        if (sale) {
            await save({...sale, status: 'pending', error: null});
            changed();
            await sync();
        }
    }

    async function discard(id) {
        await remove(id);
        changed();
    }

    function badge() {
        let element = document.getElementById('pos-offline-badge');

        if (! element) {
            element = document.createElement('div');
            element.id = 'pos-offline-badge';
            element.setAttribute('role', 'status');
            element.style.cssText = 'position:fixed;left:50%;bottom:16px;transform:translateX(-50%);z-index:1090;padding:8px 16px;border-radius:999px;background:#f1416c;color:#fff;font-weight:600;font-size:13px;box-shadow:0 4px 16px rgba(0,0,0,.2);display:none;';
            element.textContent = 'Offline - Quick Sale keeps working; sales sync when the internet is back.';
            document.body.appendChild(element);
        }

        element.style.display = navigator.onLine ? 'none' : 'block';
    }

    window.posOffline = {list, sync, retry, discard, user: config.user};

    window.addEventListener('online', () => {
        badge();
        sync();
    });
    window.addEventListener('offline', badge);
    document.addEventListener('DOMContentLoaded', () => {
        badge();
        sync();
        changed();
    });
    setInterval(sync, 60000);
})();
