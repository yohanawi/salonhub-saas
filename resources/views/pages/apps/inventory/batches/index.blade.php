<x-default-layout>

    @section('title')
        Batches & Expiry
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.batches.index') }}
    @endsection

    <div id="kt_app_content_container">

        @include('pages/apps.inventory.partials._alerts')

        <div class="row g-5 mb-5">
            @foreach ([
                ['key' => 'expired', 'label' => 'Expired (in stock)', 'color' => 'danger', 'icon' => 'bi-calendar-x'],
                ['key' => 'expiring', 'label' => "Expiring in {$days} days", 'color' => 'warning', 'icon' => 'bi-hourglass-split'],
                ['key' => 'in_stock', 'label' => 'Batches in stock', 'color' => 'primary', 'icon' => 'bi-box-seam'],
            ] as $tile)
                <div class="col-md-4">
                    <a href="{{ route('inventory.batches.index', array_merge(request()->except('page'), ['filter' => $tile['key']])) }}"
                        class="card border-0 shadow-sm h-100 {{ $filter === $tile['key'] ? 'border border-2 border-' . $tile['color'] : '' }}">
                        <div class="card-body d-flex align-items-center p-5">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-{{ $tile['color'] }}"><i class="bi {{ $tile['icon'] }} text-{{ $tile['color'] }} fs-2"></i></div>
                            </div>
                            <div>
                                <div class="fs-2 fw-bolder text-gray-900">{{ number_format($summary[$tile['key']]) }}</div>
                                <div class="text-muted fs-7">{{ $tile['label'] }}</div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-6">
                <form method="GET" class="d-flex flex-wrap gap-3 w-100">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-solid form-control-sm w-200px" placeholder="Product or batch no.">
                    <select name="branch_id" class="form-select form-select-solid form-select-sm w-175px">
                        <option value="">All branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <select name="days" class="form-select form-select-solid form-select-sm w-150px">
                        @foreach ([7, 14, 30, 60, 90] as $option)
                            <option value="{{ $option }}" @selected($days === $option)>Within {{ $option }} days</option>
                        @endforeach
                    </select>
                    <a href="{{ route('inventory.batches.index', ['filter' => 'all']) }}" class="btn btn-sm btn-light {{ $filter === 'all' ? 'active' : '' }}">All batches</a>
                    <button class="btn btn-sm btn-light-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                </form>
            </div>

            <div class="card-body pt-4">
                @if ($batches->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-200px">Product</th>
                                    <th>Batch</th>
                                    <th>Branch</th>
                                    <th>Expiry</th>
                                    <th class="text-center">Remaining</th>
                                    <th class="text-end">Unit Cost</th>
                                    <th class="text-end min-w-150px"></th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                @foreach ($batches as $batch)
                                    @php
                                        $daysLeft = $batch->daysToExpiry();
                                        $expiryClass = $daysLeft === null ? 'secondary' : ($daysLeft < 0 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'success'));
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-gray-900">{{ $batch->product?->name }}</div>
                                            @if ($isSuperAdmin)
                                                <div class="text-muted fs-8">{{ $batch->tenant?->name }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $batch->batch_number }}</td>
                                        <td>{{ $batch->branch?->name }}</td>
                                        <td>
                                            @if ($batch->expiry_date)
                                                <span class="badge badge-light-{{ $expiryClass }}">{{ $batch->expiry_date->format('M d, Y') }}</span>
                                                <div class="text-muted fs-8">
                                                    {{ $daysLeft < 0 ? abs($daysLeft) . ' days ago' : ($daysLeft === 0 ? 'Today' : "in {$daysLeft} days") }}
                                                </div>
                                            @else
                                                <span class="text-muted">No expiry</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $batch->quantity_remaining }} / {{ $batch->quantity_received }} {{ $batch->product?->unit?->symbol }}</td>
                                        <td class="text-end">{{ number_format((float) $batch->unit_cost, 2) }}</td>
                                        <td class="text-end">
                                            @can('writeOff', $batch)
                                                <form method="POST" action="{{ route('inventory.batches.write-off', $batch) }}" class="d-inline-flex gap-2 align-items-center"
                                                    onsubmit="return confirm('Remove these units from stock?');">
                                                    @csrf
                                                    <input type="number" name="quantity" min="1" max="{{ $batch->quantity_remaining }}" value="{{ $batch->quantity_remaining }}"
                                                        class="form-control form-control-solid form-control-sm w-75px">
                                                    <input type="hidden" name="reason" value="{{ $batch->isExpired() ? 'expired' : 'damaged' }}">
                                                    <button class="btn btn-sm btn-light-danger" title="{{ $batch->isExpired() ? 'Write off as expired' : 'Write off as damaged' }}">
                                                        <i class="bi bi-trash3 me-1"></i>{{ $batch->isExpired() ? 'Expire' : 'Write off' }}
                                                    </button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-5">{{ $batches->links() }}</div>
                @else
                    <div class="text-center py-15">
                        <i class="bi bi-calendar-check fs-3x text-gray-300 mb-4 d-block"></i>
                        <h3 class="fw-bold text-gray-900 mb-2">Nothing to show</h3>
                        <div class="text-muted fs-6">
                            Batches are recorded when stock of a batch-tracked product is received. Turn on
                            "Track batches & expiry" on a product to start.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-default-layout>
