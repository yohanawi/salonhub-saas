<x-default-layout>

    @section('title')
        Stock Takes
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.stock-takes.index') }}
    @endsection

    <div id="kt_app_content_container">

        @include('pages/apps.inventory.partials._alerts')

        <div class="row g-5">
            {{-- Start a count --}}
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title flex-column">
                            <h3 class="fw-bold text-gray-900 mb-1"><i class="bi bi-clipboard-check text-primary me-2"></i>Start a Stock Take</h3>
                            <div class="text-muted fs-8">Snapshots current quantities, then you enter what is physically on the shelf.</div>
                        </div>
                    </div>
                    @can('create', \App\Models\StockTake::class)
                        <form method="POST" action="{{ route('inventory.stock-takes.store') }}" class="card-body pt-2">
                            @csrf
                            <div class="mb-5">
                                <label class="form-label required">Branch</label>
                                <select name="branch_id" class="form-select form-select-solid" required>
                                    <option value="">Select branch</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select form-select-solid">
                                    <option value="">All stock-tracked products</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div class="text-muted fs-8 mt-1">Count one shelf or category at a time if the full list is long.</div>
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" value="{{ old('notes') }}" class="form-control form-control-solid" placeholder="e.g. Month-end count">
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-play-circle me-1"></i>Start Count</button>
                        </form>
                    @endcan
                </div>
            </div>

            {{-- History --}}
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title"><h3 class="fw-bold text-gray-900 mb-0">Stock Takes</h3></div>
                        <div class="card-toolbar">
                            <form method="GET" class="d-flex gap-2">
                                <select name="status" class="form-select form-select-solid form-select-sm w-150px" onchange="this.form.submit()">
                                    <option value="">All statuses</option>
                                    @foreach ([\App\Models\StockTake::STATUS_IN_PROGRESS, \App\Models\StockTake::STATUS_COMPLETED, \App\Models\StockTake::STATUS_CANCELLED] as $status)
                                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        @if ($stockTakes->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-4">
                                    <thead>
                                        <tr class="text-muted fw-bold fs-8 text-uppercase">
                                            <th>Number</th>
                                            <th>Branch</th>
                                            <th>Scope</th>
                                            <th class="text-center">Counted</th>
                                            <th class="text-center">Differences</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-end"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-7">
                                        @foreach ($stockTakes as $stockTake)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('inventory.stock-takes.show', $stockTake) }}" class="fw-bold text-gray-900 text-hover-primary">{{ $stockTake->stock_take_number }}</a>
                                                    <div class="text-muted fs-8">{{ $stockTake->started_at?->format('M d, Y H:i') }} · {{ $stockTake->createdBy?->name }}</div>
                                                </td>
                                                <td>{{ $stockTake->branch?->name }}</td>
                                                <td>{{ $stockTake->category?->name ?? 'All products' }}</td>
                                                <td class="text-center">{{ $stockTake->counted_count }} / {{ $stockTake->items_count }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $stockTake->variance_count ? 'warning' : 'secondary' }}">{{ $stockTake->variance_count }}</span>
                                                </td>
                                                <td class="text-center"><span class="badge badge-light-{{ $stockTake->status_color }}">{{ $stockTake->status_label }}</span></td>
                                                <td class="text-end">
                                                    <a href="{{ route('inventory.stock-takes.show', $stockTake) }}" class="btn btn-sm btn-light-primary">{{ $stockTake->isOpen() ? 'Continue' : 'View' }}</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-5">{{ $stockTakes->links() }}</div>
                        @else
                            <div class="text-center py-15 text-muted">No stock takes yet.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-default-layout>
