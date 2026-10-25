<x-default-layout>
    @section('title') Stock Transfers @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('inventory.transfers.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-4">
                <div>
                    <h3 class="fw-bolder text-gray-900 mb-1">Stock Transfers</h3>
                    <div class="text-muted fs-7">Move stock between branches. It leaves the sending branch when sent and arrives when the other branch confirms receipt.</div>
                </div>
                <div class="d-flex gap-2">
                    <form method="GET">
                        <select name="status" class="form-select form-select-solid form-select-sm w-150px" onchange="this.form.submit()">
                            <option value="">All statuses</option>
                            @foreach (\App\Models\StockTransfer::STATUSES as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                            @endforeach
                        </select>
                    </form>
                    @can('create', \App\Models\StockTransfer::class)
                        <a href="{{ route('inventory.transfers.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-arrow-left-right me-1"></i>New Transfer</a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @if ($transfers->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-4 fs-7">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th>Transfer</th>
                                    <th>From → To</th>
                                    <th class="text-center">Products</th>
                                    <th>Sent</th>
                                    <th>Received</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transfers as $transfer)
                                    <tr>
                                        <td><a href="{{ route('inventory.transfers.show', $transfer) }}" class="fw-bold text-gray-900 text-hover-primary">{{ $transfer->transfer_number }}</a><div class="text-muted fs-8">{{ $transfer->created_at?->format('M d, Y') }} · {{ $transfer->createdBy?->name }}</div></td>
                                        <td>{{ $transfer->fromBranch?->name }} <i class="bi bi-arrow-right mx-1"></i> {{ $transfer->toBranch?->name }}</td>
                                        <td class="text-center">{{ $transfer->items_count }}</td>
                                        <td>{{ $transfer->sent_at?->format('M d, H:i') ?? '—' }}</td>
                                        <td>{{ $transfer->received_at?->format('M d, H:i') ?? '—' }}</td>
                                        <td class="text-center"><span class="badge badge-light-{{ $transfer->status_color }}">{{ $transfer->status_label }}</span></td>
                                        <td class="text-end"><a href="{{ route('inventory.transfers.show', $transfer) }}" class="btn btn-sm btn-light-primary">View</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-5">{{ $transfers->links() }}</div>
                @else
                    <div class="text-center text-muted py-15">No stock transfers yet.</div>
                @endif
            </div>
        </div>
    </div>
</x-default-layout>
