@push('styles')
    <style>
        [data-receipt-modal] .modal-body {
            max-height: 72vh;
        }

        [data-receipt-modal] .receipt-card {
            border-radius: 18px;
        }

        [data-receipt-modal] .receipt-divider {
            border-top: 1px dashed #d8d8e5;
        }

        [data-receipt-modal] .receipt-info-box {
            background: #f9f9fb;
            border-radius: 12px;
            padding: 18px;
        }

        [data-receipt-modal] .receipt-info-row,
        [data-receipt-modal] .receipt-total-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
        }

        [data-receipt-modal] .receipt-info-row {
            margin-bottom: 10px;
        }

        [data-receipt-modal] .receipt-info-row:last-child,
        [data-receipt-modal] .receipt-total-row:last-child {
            margin-bottom: 0;
        }

        [data-receipt-modal] .receipt-label {
            color: #99a1b7;
            font-size: 12px;
            font-weight: 600;
            flex-shrink: 0;
        }

        [data-receipt-modal] .receipt-value {
            color: #181c32;
            font-size: 12px;
            font-weight: 600;
            text-align: right;
        }

        [data-receipt-modal] .receipt-total-row {
            align-items: center;
            margin-bottom: 11px;
            font-size: 13px;
        }

        [data-receipt-modal] .letter-spacing {
            letter-spacing: 1.5px;
        }

        @media (max-width: 576px) {
            [data-receipt-modal] .modal-dialog {
                margin: 0.75rem;
            }

            [data-receipt-modal] .receipt-card {
                border-radius: 12px;
            }

            [data-receipt-modal] .receipt-card .card-body {
                padding: 24px !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalElement = document.querySelector('[data-receipt-modal]');

            if (!modalElement || typeof bootstrap === 'undefined') {
                return;
            }

            const receiptModal = new bootstrap.Modal(modalElement);
            const modalBody = modalElement.querySelector('[data-receipt-modal-body]');
            const modalSubtitle = modalElement.querySelector('[data-receipt-modal-subtitle]');
            const printButton = modalElement.querySelector('[data-receipt-modal-print]');
            const printFrame = document.querySelector('[data-receipt-print-frame]');

            document.querySelectorAll('[data-receipt-template]').forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();

                    const template = document.getElementById(trigger.dataset.receiptTemplate);

                    if (!template) {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger border-0 mb-0">
                                <div class="fw-bold mb-1">Receipt preview failed</div>
                                <div class="fs-7">Receipt content was not found on this page.</div>
                            </div>
                        `;
                        receiptModal.show();
                        return;
                    }

                    modalSubtitle.textContent = trigger.dataset.receiptInvoice || 'Receipt';
                    modalBody.innerHTML = '';
                    modalBody.appendChild(template.content.cloneNode(true));
                    receiptModal.show();
                });
            });

            printButton?.addEventListener('click', () => {
                const receiptCard = modalBody.querySelector('.receipt-card');

                if (!receiptCard || !printFrame) {
                    return;
                }

                const printDocument = printFrame.contentWindow?.document;

                if (!printDocument) {
                    return;
                }

                printDocument.open();
                printDocument.write(`
                    <!doctype html>
                    <html>
                        <head>
                            <title>Receipt</title>
                            <link rel="stylesheet" href="/assets/plugins/global/plugins.bundle.css">
                            <link rel="stylesheet" href="/assets/css/style.bundle.css">
                            <style>
                                body { background: #ffffff; padding: 16px; }
                                .receipt-card { max-width: 520px; margin: 0 auto; box-shadow: none !important; border: 0 !important; }
                                .receipt-divider { border-top: 1px dashed #b5b5c3; }
                                .receipt-info-box { background: transparent; border: 1px solid #eeeeee; border-radius: 12px; padding: 18px; }
                                .receipt-info-row, .receipt-total-row { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 10px; }
                                .receipt-label { color: #99a1b7; font-size: 12px; font-weight: 600; }
                                .receipt-value { color: #181c32; font-size: 12px; font-weight: 600; text-align: right; }
                                .letter-spacing { letter-spacing: 1.5px; }
                            </style>
                        </head>
                        <body>${receiptCard.outerHTML}</body>
                    </html>
                `);
                printDocument.close();

                setTimeout(() => {
                    printFrame.contentWindow?.focus();
                    printFrame.contentWindow?.print();
                }, 250);
            });
        });
    </script>
@endpush
