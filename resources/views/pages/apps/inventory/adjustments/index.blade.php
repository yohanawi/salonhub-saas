<x-default-layout>
    @section('title')
        Stock Adjustments
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.adjustments.index') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')
        <div class="d-flex justify-content-end mb-8">
            @can('create', \App\Models\StockAdjustment::class)
                <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>New Adjustment</a>
            @endcan
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Stock Adjustments</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Number</th><th>Branch</th><th>Reason</th><th>Items</th><th>Created By</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($adjustments as $adjustment)
                                <tr>
                                    <td class="fw-bold text-gray-900">{{ $adjustment->adjustment_number }}</td>
                                    <td>{{ $adjustment->branch?->name }}</td>
                                    <td>{{ $adjustment->reason_label }}</td>
                                    <td>{{ number_format($adjustment->items_count) }}</td>
                                    <td>{{ $adjustment->createdBy?->name ?? '-' }}</td>
                                    <td>{{ $adjustment->created_at?->format('M d, Y h:i A') }}</td>
                                    <td class="text-end"><a href="{{ route('inventory.adjustments.show', $adjustment) }}" class="btn btn-sm btn-icon btn-light-primary"><i class="bi bi-eye"></i></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-10">No stock adjustments found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $adjustments->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
