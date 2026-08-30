<x-default-layout>
    @section('title')
        Point Transactions
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.transactions.index') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex align-items-start gap-5">
                    <div class="symbol symbol-55px flex-shrink-0">
                        <div class="symbol-label bg-light-primary rounded-4">
                            <i class="bi bi-arrow-left-right text-primary fs-1"></i>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                            <h3 class="fw-bolder text-gray-900 mb-0">
                                Point Transactions
                            </h3>
                            <span class="badge badge-light-info px-3 py-2">
                                Ledger
                            </span>
                        </div>
                        <div class="text-muted fs-6">
                            Review every point earning, redemption, expiry and manual adjustment.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @can('create', \App\Models\LoyaltyPointTransaction::class)
            <form method="POST" action="{{ route('loyalty-management.transactions.adjust') }}" class="card border-0 shadow-sm mb-8">
                @csrf
                <div class="card-header border-0 pt-7">
                    <div class="card-title">
                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-warning">
                                <i class="bi bi-pencil-square text-warning fs-4"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-1">
                                Manual Point Adjustment
                            </h3>
                            <div class="text-muted fs-8">
                                Add or deduct points with an auditable reason.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-5">
                        @if ($isSuperAdmin)
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    Tenant
                                </label>
                                <select name="tenant_id" class="form-select form-select-solid">
                                    <option value="">
                                        Tenant
                                    </option>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}">
                                            {{ $tenant->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label required fw-semibold">
                                Customer
                            </label>
                            <select name="customer_id" class="form-select form-select-solid" required>
                                <option value="">
                                    Customer
                                </option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">
                                        {{ $customer->full_name }} - {{ $customer->phone }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label required fw-semibold">
                                Points
                            </label>
                            <input type="number" name="points" class="form-control form-control-solid"
                                placeholder="+/- points" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required fw-semibold">
                                Reason
                            </label>
                            <input name="reason" class="form-control form-control-solid" placeholder="Reason" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Adjust
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @endcan

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-journal-text text-primary fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Points Ledger
                        </h3>
                        <div class="text-muted fs-8">
                            Chronological point balance movement.
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <span class="badge badge-light-info px-3 py-2">
                        {{ number_format($transactions->total()) }}
                        Records
                    </span>
                </div>
            </div>

            <div class="card-body pt-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-6">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th class="min-w-150px">Date</th>
                                <th class="min-w-210px">Customer</th>
                                <th class="min-w-160px">Type</th>
                                <th class="min-w-240px">Description</th>
                                <th class="text-end min-w-120px">Points</th>
                                <th class="text-end min-w-120px">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-gray-900">
                                            {{ $transaction->created_at?->format('M d, Y') }}
                                        </div>
                                        <div class="text-muted fs-8">
                                            {{ $transaction->created_at?->format('h:i A') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-40px me-4">
                                                <div class="symbol-label bg-light-primary">
                                                    <i class="bi bi-person-fill text-primary"></i>
                                                </div>
                                            </div>
                                            <span class="fw-bold text-gray-900">
                                                {{ $transaction->customer?->full_name ?? 'Unknown Customer' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-primary px-3 py-2">
                                            {{ $transaction->type_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-gray-700">
                                            {{ $transaction->description ?: 'No description' }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold {{ $transaction->points >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $transaction->points >= 0 ? '+' : '' }}{{ number_format($transaction->points) }}
                                    </td>
                                    <td class="text-end">
                                        <span class="badge badge-light-info px-3 py-2">
                                            {{ number_format($transaction->balance_after) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="text-center py-15">
                                            <div class="symbol symbol-80px mb-5">
                                                <div class="symbol-label bg-light-primary rounded-circle">
                                                    <i class="bi bi-stars text-primary fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="fw-bold text-gray-900 mb-2">
                                                No point transactions yet
                                            </div>
                                            <div class="text-muted">
                                                Point activity will appear here after customers earn or redeem rewards.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div class="border-top border-gray-200 pt-6 mt-6">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-default-layout>
