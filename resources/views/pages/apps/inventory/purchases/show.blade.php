<x-default-layout>

    @section('title')
        {{ $purchase->purchase_number }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.purchases.show', $purchase) }}
    @endsection

    @php
        $currency = $purchase->tenant?->currency ?: 'LKR';
        $orderedQty = $purchase->items->sum('quantity');
        $receivedQty = $purchase->items->sum('quantity_received');
        $progress = $orderedQty > 0 ? (int) round($receivedQty / $orderedQty * 100) : 0;
    @endphp

    <div id="kt_app_content_container">

        @include('pages/apps.inventory.partials._alerts')

        {{-- Header --}}
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-5">
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                            <h3 class="fw-bolder text-gray-900 mb-0">{{ $purchase->purchase_number }}</h3>
                            <span class="badge badge-light-{{ $purchase->status_color }} px-3 py-2">{{ $purchase->status_label }}</span>
                        </div>
                        <div class="text-muted fs-7">
                            {{ $purchase->supplier?->name ?? 'No supplier' }} → {{ $purchase->branch?->name }}
                            · Ordered {{ $purchase->purchase_date?->format('M d, Y') }}
                            @if ($purchase->expected_date)
                                · Expected {{ $purchase->expected_date->format('M d, Y') }}
                            @endif
                        </div>
                        @if ($purchase->isReceivable() || $purchase->status === \App\Models\Purchase::STATUS_RECEIVED)
                            <div class="d-flex align-items-center gap-3 mt-4 w-300px">
                                <div class="progress h-6px flex-grow-1">
                                    <div class="progress-bar bg-success" style="width: {{ $progress }}%"></div>
                                </div>
                                <span class="fs-8 text-muted">{{ $receivedQty }} / {{ $orderedQty }} received</span>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-start">
                        <a href="{{ route('inventory.purchases.print', $purchase) }}" target="_blank" class="btn btn-sm btn-light">
                            <i class="bi bi-printer me-1"></i>Print PO
                        </a>
                        @can('update', $purchase)
                            <a href="{{ route('inventory.purchases.edit', $purchase) }}" class="btn btn-sm btn-light-primary">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        @can('order', $purchase)
                            <form method="POST" action="{{ route('inventory.purchases.order', $purchase) }}">
                                @csrf
                                <button class="btn btn-sm btn-primary"><i class="bi bi-send me-1"></i>Mark as Ordered</button>
                            </form>
                        @endcan
                        @can('cancel', $purchase)
                            <form method="POST" action="{{ route('inventory.purchases.cancel', $purchase) }}"
                                onsubmit="return confirm('Cancel this purchase order? This cannot be undone.');">
                                @csrf
                                <button class="btn btn-sm btn-light-danger"><i class="bi bi-x-circle me-1"></i>Cancel Order</button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-xl-8">

                {{-- Receive goods (GRN) --}}
                @can('receive', $purchase)
                    <form method="POST" action="{{ route('inventory.purchases.receive', $purchase) }}" class="card border-0 shadow-sm mb-5">
                        @csrf
                        <div class="card-header border-0 pt-6">
                            <div class="card-title flex-column">
                                <h3 class="fw-bold text-gray-900 mb-1"><i class="bi bi-box-arrow-in-down text-success me-2"></i>Receive Goods</h3>
                                <div class="text-muted fs-8">Enter what arrived in this delivery. Stock at {{ $purchase->branch?->name }} updates immediately.</div>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            <div class="table-responsive">
                                <table class="table align-middle gy-3">
                                    <thead>
                                        <tr class="text-muted fw-bold fs-8 text-uppercase">
                                            <th class="min-w-200px">Product</th>
                                            <th class="text-center">Pending</th>
                                            <th class="w-110px">Receive Now</th>
                                            <th class="w-150px">Batch No.</th>
                                            <th class="w-150px">Expiry</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-7">
                                        @foreach ($purchase->items as $item)
                                            @continue($item->quantity_pending === 0)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-gray-900">{{ $item->product?->name }}</div>
                                                    @if ($item->product?->track_batches)
                                                        <span class="badge badge-light-info fs-9">Batch tracked</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $item->quantity_pending }}</td>
                                                <td>
                                                    <input type="number" min="0" max="{{ $item->quantity_pending }}" name="items[{{ $item->id }}][quantity]"
                                                        value="{{ old("items.{$item->id}.quantity", $item->quantity_pending) }}" class="form-control form-control-solid form-control-sm">
                                                </td>
                                                <td>
                                                    @if ($item->product?->track_batches)
                                                        <input type="text" name="items[{{ $item->id }}][batch_number]" value="{{ old("items.{$item->id}.batch_number") }}"
                                                            class="form-control form-control-solid form-control-sm" placeholder="Auto if blank">
                                                    @else
                                                        <span class="text-muted fs-8">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($item->product?->track_batches)
                                                        <input type="date" name="items[{{ $item->id }}][expiry_date]" value="{{ old("items.{$item->id}.expiry_date") }}"
                                                            class="form-control form-control-solid form-control-sm">
                                                    @else
                                                        <span class="text-muted fs-8">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="row g-4 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label">Received On</label>
                                    <input type="date" name="received_at" value="{{ old('received_at', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control form-control-solid form-control-sm">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Delivery Note / Invoice No.</label>
                                    <input type="text" name="supplier_reference" value="{{ old('supplier_reference') }}" class="form-control form-control-solid form-control-sm">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Notes</label>
                                    <input type="text" name="notes" value="{{ old('notes') }}" class="form-control form-control-solid form-control-sm">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Post Goods Received Note</button>
                        </div>
                    </form>
                @endcan

                {{-- Ordered items --}}
                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title"><h3 class="fw-bold text-gray-900 mb-0">Order Lines</h3></div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead>
                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                        <th class="min-w-200px">Product</th>
                                        <th class="text-center">Ordered</th>
                                        <th class="text-center">Received</th>
                                        <th class="text-end">Unit Cost</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="fs-7">
                                    @foreach ($purchase->items as $item)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-gray-900">{{ $item->product?->name }}</div>
                                                <div class="text-muted fs-8">{{ $item->product?->sku }}</div>
                                            </td>
                                            <td class="text-center">{{ $item->quantity }} {{ $item->product?->unit?->symbol }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-light-{{ $item->quantity_pending === 0 ? 'success' : ($item->quantity_received > 0 ? 'warning' : 'secondary') }}">{{ $item->quantity_received }}</span>
                                            </td>
                                            <td class="text-end">{{ number_format((float) $item->unit_cost, 2) }}</td>
                                            <td class="text-end fw-semibold">{{ number_format((float) $item->total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Receipts --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title"><h3 class="fw-bold text-gray-900 mb-0">Goods Received Notes</h3></div>
                    </div>
                    <div class="card-body pt-2">
                        @forelse ($purchase->receipts->sortByDesc('received_at') as $receipt)
                            <div class="border border-gray-200 rounded-3 p-4 mb-3">
                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                    <div class="fw-bold text-gray-900">{{ $receipt->receipt_number }}</div>
                                    <div class="text-muted fs-8">
                                        {{ $receipt->received_at?->format('M d, Y') }} · {{ $receipt->receivedBy?->name }}
                                        @if ($receipt->supplier_reference)
                                            · Ref {{ $receipt->supplier_reference }}
                                        @endif
                                    </div>
                                </div>
                                @foreach ($receipt->items as $line)
                                    <div class="d-flex justify-content-between fs-7 py-1">
                                        <span>{{ $line->product?->name }}
                                            @if ($line->batch_number || $line->expiry_date)
                                                <span class="text-muted fs-8">
                                                    ({{ collect([$line->batch_number ? "Batch {$line->batch_number}" : null, $line->expiry_date ? 'Exp ' . $line->expiry_date->format('M d, Y') : null])->filter()->implode(', ') }})
                                                </span>
                                            @endif
                                        </span>
                                        <span class="fw-semibold">+{{ $line->quantity }}</span>
                                    </div>
                                @endforeach
                                @if ($receipt->notes)
                                    <div class="text-muted fs-8 mt-2">{{ $receipt->notes }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="text-muted fs-7">Nothing received yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title"><h3 class="fw-bold text-gray-900 mb-0">Summary</h3></div>
                    </div>
                    <div class="card-body pt-2 fs-7">
                        <div class="d-flex justify-content-between mb-3"><span class="text-muted">Supplier</span><span class="fw-semibold text-end">{{ $purchase->supplier?->name ?? '—' }}</span></div>
                        @if ($purchase->supplier?->phone)
                            <div class="d-flex justify-content-between mb-3"><span class="text-muted">Phone</span><span>{{ $purchase->supplier->phone }}</span></div>
                        @endif
                        <div class="d-flex justify-content-between mb-3"><span class="text-muted">Supplier Invoice</span><span>{{ $purchase->invoice_number ?: '—' }}</span></div>
                        <div class="d-flex justify-content-between mb-3"><span class="text-muted">Created by</span><span>{{ $purchase->creator?->name ?? '—' }}</span></div>
                        <div class="separator my-4"></div>
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><span>{{ $currency }} {{ number_format((float) $purchase->subtotal, 2) }}</span></div>
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Discount</span><span>- {{ number_format((float) $purchase->discount, 2) }}</span></div>
                        <div class="d-flex justify-content-between mb-3"><span class="text-muted">Tax</span><span>{{ number_format((float) $purchase->tax, 2) }}</span></div>
                        <div class="d-flex justify-content-between border-top pt-3"><span class="fw-bold">Total</span><span class="fw-bolder fs-4 text-primary">{{ $currency }} {{ number_format((float) $purchase->total, 2) }}</span></div>
                        @if ($purchase->notes)
                            <div class="separator my-4"></div>
                            <div class="text-muted fs-8 mb-1">Notes</div>
                            <div style="white-space: pre-line;">{{ $purchase->notes }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-default-layout>
