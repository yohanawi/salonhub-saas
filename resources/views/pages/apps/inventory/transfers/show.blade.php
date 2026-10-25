<x-default-layout>
    @section('title') {{ $transfer->transfer_number }} @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('inventory.transfers.show', $transfer) }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-wrap justify-content-between gap-4">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <h3 class="fw-bolder text-gray-900 mb-0">{{ $transfer->transfer_number }}</h3>
                        <span class="badge badge-light-{{ $transfer->status_color }} px-3 py-2">{{ $transfer->status_label }}</span>
                    </div>
                    <div class="fs-6">{{ $transfer->fromBranch?->name }} <i class="bi bi-arrow-right mx-2"></i> {{ $transfer->toBranch?->name }}</div>
                    <div class="text-muted fs-8 mt-1">
                        Created {{ $transfer->created_at?->format('M d, Y H:i') }} by {{ $transfer->createdBy?->name }}
                        @if ($transfer->sent_at) · Sent {{ $transfer->sent_at->format('M d H:i') }} by {{ $transfer->sentBy?->name }} @endif
                        @if ($transfer->received_at) · Received {{ $transfer->received_at->format('M d H:i') }} by {{ $transfer->receivedBy?->name }} @endif
                    </div>
                    @if ($transfer->notes)
                        <div class="text-gray-700 fs-7 mt-2" style="white-space: pre-line;">{{ $transfer->notes }}</div>
                    @endif
                </div>
                <div class="d-flex gap-2 align-items-start">
                    @can('send', $transfer)
                        <form method="POST" action="{{ route('inventory.transfers.send', $transfer) }}">@csrf<button class="btn btn-sm btn-primary"><i class="bi bi-truck me-1"></i>Send Stock</button></form>
                    @endcan
                    @can('cancel', $transfer)
                        <form method="POST" action="{{ route('inventory.transfers.cancel', $transfer) }}" onsubmit="return confirm('Cancel this transfer?{{ $transfer->status === 'in_transit' ? ' Stock goes back to ' . $transfer->fromBranch?->name . '.' : '' }}');">@csrf<button class="btn btn-sm btn-light-danger">Cancel</button></form>
                    @endcan
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('inventory.transfers.receive', $transfer) }}" class="card border-0 shadow-sm">
            @csrf
            @php $canReceive = auth()->user()->can('receive', $transfer); @endphp
            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Products</h3>
                    @if ($canReceive)
                        <div class="text-muted fs-8">Check what arrived. Enter a smaller number if something is missing or damaged.</div>
                    @endif
                </div>
            </div>
            <div class="card-body pt-2">
                <table class="table align-middle table-row-dashed gy-3 fs-7">
                    <thead>
                        <tr class="text-muted fw-bold fs-8 text-uppercase">
                            <th>Product</th>
                            <th class="text-center">Sent</th>
                            <th class="text-center">{{ $canReceive ? 'Received' : 'Received' }}</th>
                            <th class="text-end">Unit Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transfer->items as $item)
                            <tr>
                                <td><div class="fw-semibold text-gray-900">{{ $item->product?->name }}</div><div class="text-muted fs-8">{{ $item->product?->sku }}</div></td>
                                <td class="text-center">{{ $item->quantity }} {{ $item->product?->unit?->symbol }}</td>
                                <td class="text-center">
                                    @if ($canReceive)
                                        <input type="number" min="0" max="{{ $item->quantity }}" name="received[{{ $item->id }}]" value="{{ $item->quantity }}" class="form-control form-control-solid form-control-sm w-100px mx-auto text-center">
                                    @else
                                        @if ($item->quantity_received === null)
                                            —
                                        @else
                                            <span class="badge badge-light-{{ $item->quantity_received < $item->quantity ? 'warning' : 'success' }}">{{ $item->quantity_received }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-end">{{ (float) $item->unit_cost > 0 ? number_format((float) $item->unit_cost, 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($canReceive)
                <div class="card-footer d-flex justify-content-end">
                    <button class="btn btn-success"><i class="bi bi-box-arrow-in-down me-1"></i>Confirm Receipt</button>
                </div>
            @endif
        </form>
    </div>
</x-default-layout>
