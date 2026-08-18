<x-default-layout>
    @section('title') Point Transactions @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.transactions.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')
        @can('create', \App\Models\LoyaltyPointTransaction::class)
            <form method="POST" action="{{ route('loyalty-management.transactions.adjust') }}" class="card border-0 shadow-sm mb-8">
                @csrf
                <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Manual Point Adjustment</h3></div>
                <div class="card-body pt-3">
                    <div class="row g-5">
                        @if ($isSuperAdmin)
                            <div class="col-md-3"><select name="tenant_id" class="form-select form-select-solid"><option value="">Tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach</select></div>
                        @endif
                        <div class="col-md-4"><select name="customer_id" class="form-select form-select-solid" required><option value="">Customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->full_name }} - {{ $customer->phone }}</option>@endforeach</select></div>
                        <div class="col-md-2"><input type="number" name="points" class="form-control form-control-solid" placeholder="+/- points" required></div>
                        <div class="col-md-4"><input name="reason" class="form-control form-control-solid" placeholder="Reason" required></div>
                        <div class="col-md-2"><button class="btn btn-primary w-100">Adjust</button></div>
                    </div>
                </div>
            </form>
        @endcan

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Points Ledger</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Date</th><th>Customer</th><th>Type</th><th>Description</th><th class="text-end">Points</th><th class="text-end">Balance</th></tr></thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at?->format('M d, Y') }}</td>
                                    <td class="fw-bold">{{ $transaction->customer?->full_name }}</td>
                                    <td><span class="badge badge-light-primary">{{ $transaction->type_label }}</span></td>
                                    <td>{{ $transaction->description }}</td>
                                    <td class="text-end fw-bold {{ $transaction->points >= 0 ? 'text-success' : 'text-danger' }}">{{ $transaction->points >= 0 ? '+' : '' }}{{ number_format($transaction->points) }}</td>
                                    <td class="text-end">{{ number_format($transaction->balance_after) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-10">No point transactions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
