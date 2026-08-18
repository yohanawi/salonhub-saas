<x-default-layout>
    @section('title')
        Stock Adjustment
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.adjustments.show', $adjustment) }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-6">
                    <div>
                        <h1 class="fw-bolder text-gray-900 mb-2">{{ $adjustment->adjustment_number }}</h1>
                        <div class="text-muted">{{ $adjustment->branch?->name }} - {{ $adjustment->reason_label }} - {{ $adjustment->created_at?->format('M d, Y h:i A') }}</div>
                    </div>
                    <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-light align-self-start">Back</a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Adjusted Products</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Product</th><th class="text-end">System</th><th class="text-end">Actual</th><th class="text-end">Difference</th><th>Reason</th></tr></thead>
                        <tbody>
                            @foreach ($adjustment->items as $item)
                                <tr>
                                    <td class="fw-bold text-gray-900">{{ $item->product?->name }}</td>
                                    <td class="text-end">{{ $item->system_quantity }}</td>
                                    <td class="text-end">{{ $item->actual_quantity }}</td>
                                    <td class="text-end fw-bold {{ $item->difference >= 0 ? 'text-success' : 'text-danger' }}">{{ $item->difference }}</td>
                                    <td>{{ $item->reason ?: $adjustment->reason_label }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
