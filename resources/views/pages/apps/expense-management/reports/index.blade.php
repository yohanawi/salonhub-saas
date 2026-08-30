<x-default-layout>
    @section('title') Expense Reports @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('expense-management.reports.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.expense-management.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body py-6">
                <form method="GET" class="row g-4 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select form-select-solid">
                            <option value="">All</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-solid"></div>
                    <div class="col-md-2"><label class="form-label">To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-solid"></div>
                    <div class="col-md-2"><button class="btn btn-light-primary w-100">Apply</button></div>
                </form>
            </div>
        </div>

        <div class="row g-6 mb-8">
            @foreach ([['Total Expenses', $totalExpenses], ['Total Paid', $totalPaid], ['Outstanding', $totalOutstanding]] as [$label, $value])
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted fs-7 mb-2">{{ $label }}</div><div class="fw-bolder fs-2">LKR {{ number_format((float) $value, 2) }}</div></div></div>
                </div>
            @endforeach
        </div>

        <div class="row g-8">
            @foreach ([['By Category', $byCategory, 'category'], ['By Branch', $byBranch, 'branch'], ['By Vendor', $byVendor, 'vendor']] as [$title, $rows, $relation])
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header border-0 pt-7"><h3 class="fw-bold mb-0">{{ $title }}</h3></div>
                        <div class="card-body pt-3">
                            @forelse ($rows as $row)
                                <div class="d-flex justify-content-between border-bottom py-3">
                                    <span class="fw-semibold text-gray-800">{{ $row->{$relation}?->full_name ?? $row->{$relation}?->name ?? 'Unassigned' }}</span>
                                    <span class="fw-bold">LKR {{ number_format((float) $row->total, 2) }}</span>
                                </div>
                            @empty
                                <div class="text-muted py-6">No data found.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-default-layout>
