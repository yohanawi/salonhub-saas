<x-default-layout>
    @section('title') Expense Dashboard @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('expense-management.dashboard') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.expense-management.partials._alerts')

        <div class="row g-6 mb-8">
            @foreach ([
                ['Today', $todayTotal, 'primary'],
                ['This Month', $monthTotal, 'info'],
                ['Pending Payments', $pendingPayments, 'warning'],
                ['Pending Approvals', $pendingApprovals, 'danger'],
            ] as [$label, $value, $color])
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fs-7 mb-2">{{ $label }}</div>
                            <div class="fw-bolder fs-2 text-{{ $color }}">
                                {{ is_numeric($value) && $label !== 'Pending Approvals' ? 'LKR ' . number_format((float) $value, 2) : $value }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-8">
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Recent Expenses</h3></div>
                    <div class="card-body pt-3">
                        @forelse ($recentExpenses as $expense)
                            <div class="d-flex justify-content-between border-bottom py-3">
                                <div>
                                    <a href="{{ route('expense-management.expenses.show', $expense) }}" class="fw-bold text-gray-900 text-hover-primary">{{ $expense->expense_number }}</a>
                                    <div class="text-muted fs-8">{{ $expense->branch?->name }} - {{ $expense->category?->full_name }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-gray-900">LKR {{ number_format((float) $expense->total_amount, 2) }}</div>
                                    <span class="badge badge-light-{{ $expense->payment_status === 'paid' ? 'success' : 'warning' }}">{{ $expense->payment_status_label }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted py-6">No expenses recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">Top Categories</h3></div>
                    <div class="card-body pt-3">
                        @forelse ($categoryTotals as $row)
                            <div class="d-flex justify-content-between border-bottom py-3">
                                <span class="fw-semibold text-gray-800">{{ $row->category?->full_name ?? 'Uncategorized' }}</span>
                                <span class="fw-bold text-gray-900">LKR {{ number_format((float) $row->total, 2) }}</span>
                            </div>
                        @empty
                            <div class="text-muted py-6">No category spending found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
