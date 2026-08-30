<div class="mb-2" data-salon-search-main>
    <div class="d-flex flex-stack fw-semibold mb-4">
        <span class="text-muted fs-6 me-2">Quick Search</span>
        <span class="badge badge-light-primary">Live</span>
    </div>

    <div class="d-grid gap-3">
        @foreach ([
            ['label' => 'Today bookings', 'icon' => 'calendar-tick', 'route' => route('appointment-management.appointments.calendar'), 'permission' => 'appointments.view'],
            ['label' => 'Customers', 'icon' => 'profile-circle', 'route' => route('customer-management.customers.index'), 'permission' => 'customer.view'],
            ['label' => 'Invoices', 'icon' => 'bill', 'route' => route('billing.invoices.index'), 'permission' => 'billing.view'],
            ['label' => 'Products', 'icon' => 'parcel', 'route' => route('inventory.products.index'), 'permission' => 'product.view'],
        ] as $item)
            @can($item['permission'])
                <a href="{{ $item['route'] }}"
                    class="d-flex align-items-center rounded border border-gray-200 border-dashed p-3 text-gray-800 text-hover-primary">
                    <span class="symbol symbol-35px me-3">
                        <span class="symbol-label bg-light-primary">
                            {!! getIcon($item['icon'], 'fs-3 text-primary') !!}
                        </span>
                    </span>
                    <span class="fw-bold">{{ $item['label'] }}</span>
                </a>
            @endcan
        @endforeach
    </div>

    <div class="text-muted fs-7 mt-5">
        Type at least 2 letters, then press Enter to open the top result.
    </div>
</div>
