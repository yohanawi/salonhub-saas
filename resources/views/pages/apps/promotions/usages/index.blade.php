<x-default-layout>
    @section('title') Promotion Usage @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.usages.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Usage History</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Promotion</th><th>Coupon</th><th>Customer</th><th>Branch</th><th>Invoice</th><th class="text-end">Discount</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($usages as $usage)
                                <tr>
                                    <td class="fw-bold">{{ $usage->promotion?->name }}</td>
                                    <td>{{ $usage->coupon?->code ?? '-' }}</td>
                                    <td>{{ $usage->customer?->full_name ?? '-' }}</td>
                                    <td>{{ $usage->branch?->name ?? '-' }}</td>
                                    <td>{{ $usage->invoice?->invoice_number ?? '-' }}</td>
                                    <td class="text-end">LKR {{ number_format((float) $usage->discount_amount, 2) }}</td>
                                    <td><span class="badge badge-light-{{ $usage->status === 'used' ? 'success' : 'warning' }}">{{ str($usage->status)->headline() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-10">No promotion usage recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $usages->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
