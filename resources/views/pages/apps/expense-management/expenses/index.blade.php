<x-default-layout>
    @section('title') Expenses @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('expense-management.expenses.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.expense-management.partials._alerts')

        <div class="d-flex justify-content-end mb-8">
            @can('create', \App\Models\Expense::class)
                <a href="{{ route('expense-management.expenses.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add Expense</a>
            @endcan
        </div>

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body py-6">
                <form method="GET" class="row g-4 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label fw-semibold">Search</label>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-solid" placeholder="Number, vendor, reference">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category_id" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label fw-semibold">Payment</label>
                        <select name="payment_status" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach (\App\Models\Expense::PAYMENT_STATUSES as $status)
                                <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ str($status)->headline() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label fw-semibold">Approval</label>
                        <select name="approval_status" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach (\App\Models\Expense::APPROVAL_STATUSES as $status)
                                <option value="{{ $status }}" @selected(request('approval_status') === $status)>{{ str($status)->headline() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-1 col-md-6">
                        <button class="btn btn-light-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Expense List</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Expense</th>
                                @if($isSuperAdmin)<th>Salon</th>@endif
                                <th>Date</th>
                                <th>Branch</th>
                                <th>Category</th>
                                <th>Vendor</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expenses as $expense)
                                <tr>
                                    <td>
                                        <a href="{{ route('expense-management.expenses.show', $expense) }}" class="fw-bold text-gray-900 text-hover-primary">{{ $expense->expense_number }}</a>
                                        <div class="text-muted fs-8">{{ str($expense->description)->limit(45) }}</div>
                                    </td>
                                    @if($isSuperAdmin)<td>{{ $expense->tenant?->name }}</td>@endif
                                    <td>{{ $expense->expense_date?->format('M d, Y') }}</td>
                                    <td>{{ $expense->branch?->name }}</td>
                                    <td>{{ $expense->category?->full_name }}</td>
                                    <td>{{ $expense->vendor?->name ?? '-' }}</td>
                                    <td class="text-end">LKR {{ number_format((float) $expense->total_amount, 2) }}</td>
                                    <td class="text-end">LKR {{ number_format((float) $expense->balance_amount, 2) }}</td>
                                    <td>
                                        <span class="badge badge-light-{{ $expense->approval_status === 'approved' ? 'success' : ($expense->approval_status === 'rejected' ? 'danger' : 'warning') }}">{{ $expense->approval_status_label }}</span>
                                        <span class="badge badge-light-{{ $expense->payment_status === 'paid' ? 'success' : 'warning' }} mt-1">{{ $expense->payment_status_label }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('expense-management.expenses.show', $expense) }}" class="btn btn-sm btn-icon btn-light-primary"><i class="bi bi-eye"></i></a>
                                        @can('update', $expense)
                                            <a href="{{ route('expense-management.expenses.edit', $expense) }}" class="btn btn-sm btn-icon btn-light"><i class="bi bi-pencil-square"></i></a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $isSuperAdmin ? 10 : 9 }}" class="text-center text-muted py-10">No expenses found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $expenses->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
