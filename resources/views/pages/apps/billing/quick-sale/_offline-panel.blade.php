{{--
    Sales saved on this device while offline (see public/js/pos-offline.js). Hidden until there is
    something to show. After an offline save the page opens with ?offline_saved=<id>, which shows the
    confirmation and a temporary receipt to print.
--}}
<div class="card border-0 shadow-sm mb-8" id="offline-sales-panel" style="display: none;">
    <div class="card-body p-6">
        <div class="alert alert-warning border-0 d-flex flex-column flex-md-row justify-content-between gap-3 mb-5" id="offline-saved-alert" style="display: none !important;">
            <div>
                <div class="fw-bold text-gray-900">Saved offline as <span id="offline-saved-reference"></span></div>
                <div class="fs-7">It becomes a normal invoice automatically when the internet is back. Do not enter it again.</div>
            </div>
            <button type="button" class="btn btn-sm btn-warning align-self-md-center" id="offline-print-receipt">
                <i class="bi bi-printer me-1"></i>Print Temporary Receipt
            </button>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold text-gray-900 mb-0">Offline Sales on This Device</h3>
            <button type="button" class="btn btn-sm btn-light-primary" id="offline-sync-now">
                <i class="bi bi-arrow-repeat me-1"></i>Sync Now
            </button>
        </div>
        <div id="offline-sales-list"></div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const panel = document.getElementById('offline-sales-panel');
            const listEl = document.getElementById('offline-sales-list');
            const savedId = new URLSearchParams(location.search).get('offline_saved');
            const salonName = @json(auth()->user()->tenant?->name ?? 'Salon');
            const branchName = @json($branch->name);
            const money = (value) => 'LKR ' + Number(value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            const escape = (text) => String(text ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

            if (! window.posOffline) {
                return;
            }

            // Item names for the receipt come from the select options on this page.
            function receiptLines(sale) {
                const value = (key) => (sale.entries.find(([name]) => name === key) || [])[1];
                const lines = [];

                sale.entries.forEach(function([key, id]) {
                    const match = key.match(/^(service|product)_items\[(\w+)\]\[(service|product)_id\]$/);

                    if (match && id) {
                        const quantity = value(match[1] + '_items[' + match[2] + '][quantity]') || 1;
                        const template = match[1] === 'service' ? '#quick-sale-service-template' : '#quick-sale-product-template';
                        const option = document.querySelector(template)?.content.querySelector('option[value="' + CSS.escape(id) + '"]');
                        lines.push({name: option ? option.textContent.trim().split(' - ')[0] : 'Item', quantity});
                    }
                });

                return lines;
            }

            function printReceipt(sale) {
                const frame = document.createElement('iframe');
                frame.style.cssText = 'position:fixed;width:0;height:0;border:0;';
                document.body.appendChild(frame);

                const items = receiptLines(sale).map((line) => '<div>' + escape(line.name) + ' x ' + escape(line.quantity) + '</div>').join('');
                frame.contentDocument.write(`<!doctype html><html><head><title>${escape(sale.reference)}</title>
                    <style>@page{size:80mm auto;margin:0}body{font-family:"Courier New",monospace;font-size:12px;width:72mm;margin:0 auto;padding:4mm 0}
                    .c{text-align:center}.b{font-weight:700}hr{border:0;border-top:1px dashed #000;margin:2mm 0}</style></head><body>
                    <div class="c b" style="font-size:15px">${escape(salonName)}</div><div class="c">${escape(branchName)}</div><hr>
                    <div class="c b">TEMPORARY RECEIPT</div><div class="c">${escape(sale.reference)}</div>
                    <div class="c">${escape(new Date(sale.createdAt).toLocaleString())}</div><hr>${items}<hr>
                    <div class="b">Estimated total: ${escape(money(sale.estimatedTotal))}</div><hr>
                    <div class="c" style="font-size:10px">Taken while offline. The final invoice number is issued when the system is back online.</div>
                    </body></html>`);
                frame.contentDocument.close();
                setTimeout(() => {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                    setTimeout(() => frame.remove(), 1000);
                }, 250);
            }

            async function render() {
                const sales = (await window.posOffline.list()).filter((sale) => String(sale.userId) === String(window.posOffline.user));
                const saved = savedId ? sales.find((sale) => sale.id === savedId) : null;

                panel.style.display = sales.length || saved ? '' : 'none';

                const alert = document.getElementById('offline-saved-alert');
                alert.style.setProperty('display', saved ? 'flex' : 'none', 'important');

                if (saved) {
                    document.getElementById('offline-saved-reference').textContent = saved.reference;
                    document.getElementById('offline-print-receipt').onclick = () => printReceipt(saved);
                }

                listEl.innerHTML = sales.length ? sales.map((sale) => `
                    <div class="d-flex justify-content-between align-items-center border-bottom border-dashed py-3" data-sale="${escape(sale.id)}">
                        <div>
                            <div class="fw-bold text-gray-900">${escape(sale.reference)} &middot; ${escape(money(sale.estimatedTotal))}</div>
                            <div class="text-muted fs-8">${escape(new Date(sale.createdAt).toLocaleString())}</div>
                            ${sale.status === 'failed' ? `<div class="text-danger fs-8 mt-1">${escape(sale.error)}</div>` : ''}
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge badge-light-${sale.status === 'failed' ? 'danger' : 'warning'}">${sale.status === 'failed' ? 'Needs attention' : 'Waiting to sync'}</span>
                            <button type="button" class="btn btn-sm btn-icon btn-light" data-action="print" title="Print temporary receipt"><i class="bi bi-printer"></i></button>
                            ${sale.status === 'failed' ? '<button type="button" class="btn btn-sm btn-light-primary" data-action="retry">Retry</button>' : ''}
                            ${sale.status === 'failed' ? '<button type="button" class="btn btn-sm btn-light-danger" data-action="discard">Discard</button>' : ''}
                        </div>
                    </div>`).join('') : '<div class="text-muted fs-7">All offline sales have been synced.</div>';
            }

            listEl.addEventListener('click', async function(event) {
                const button = event.target.closest('[data-action]');
                const id = button?.closest('[data-sale]')?.dataset.sale;

                if (! id) {
                    return;
                }

                if (button.dataset.action === 'print') {
                    const sale = (await window.posOffline.list()).find((item) => item.id === id);
                    sale && printReceipt(sale);
                } else if (button.dataset.action === 'retry') {
                    window.posOffline.retry(id);
                } else if (button.dataset.action === 'discard' && confirm('Discard this offline sale? It will not be recorded.')) {
                    window.posOffline.discard(id);
                }
            });

            document.getElementById('offline-sync-now').addEventListener('click', () => window.posOffline.sync());
            document.addEventListener('pos-offline:changed', render);
            render();
        });
    </script>
@endpush
