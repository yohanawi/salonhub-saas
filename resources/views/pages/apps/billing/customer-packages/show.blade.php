<x-default-layout>
    @section('title')
        Package {{ $customerPackage->package_number }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.customer-packages.show', $customerPackage) }}
    @endsection

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 d-flex flex-column flex-md-row justify-content-between gap-6">
                <div>
                    <div class="text-muted fs-8 text-uppercase fw-bold mb-1">{{ $customerPackage->package_number }}</div>
                    <h1 class="fw-bolder text-gray-900 mb-2">{{ $customerPackage->name }}</h1>
                    <div class="text-muted">
                        {{ $customerPackage->customer?->full_name }}
                        &middot; Bought {{ $customerPackage->purchased_at?->format('M d, Y') }}
                        &middot; {{ $customerPackage->expires_at ? 'Expires ' . $customerPackage->expires_at->format('M d, Y') : 'No expiry' }}
                    </div>
                </div>
                <div class="text-md-end">
                    <span class="badge badge-light-primary fs-7 mb-2">{{ $customerPackage->status_label }}</span>
                    <div class="fw-bold text-gray-900">LKR {{ number_format((float) $customerPackage->price_paid, 2) }}</div>
                    @if ($customerPackage->invoice)
                        <a href="{{ route('billing.invoices.show', $customerPackage->invoice) }}" class="fs-8">{{ $customerPackage->invoice->invoice_number }}</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-8">
            @foreach ($customerPackage->items as $item)
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-8">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="fw-bold text-gray-900 mb-0">{{ $item->service_name }}</h3>
                                <span class="fw-bolder fs-3 text-primary">{{ $item->remainingQuantity() }} <span class="fs-7 text-muted">/ {{ $item->total_quantity }} left</span></span>
                            </div>
                            <div class="progress h-6px mb-6">
                                <div class="progress-bar bg-primary" style="width: {{ $item->total_quantity ? round($item->used_quantity / $item->total_quantity * 100) : 0 }}%"></div>
                            </div>
                            @forelse ($item->usages as $usage)
                                <div class="d-flex justify-content-between fs-7 mb-2 {{ $usage->reversed_at ? 'text-decoration-line-through text-muted' : '' }}">
                                    <span>
                                        {{ $usage->created_at?->format('M d, Y') }}
                                        @if ($usage->invoice)
                                            &middot; <a href="{{ route('billing.invoices.show', $usage->invoice) }}">{{ $usage->invoice->invoice_number }}</a>
                                        @endif
                                    </span>
                                    <span>{{ $usage->quantity }} used</span>
                                </div>
                            @empty
                                <div class="text-muted fs-8">Not used yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @include('pages.apps.billing._sweet-alerts')
</x-default-layout>
