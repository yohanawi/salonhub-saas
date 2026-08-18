<x-default-layout>
    @section('title') Purchase Membership @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.memberships.create') }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')
        <form method="POST" action="{{ route('loyalty-management.memberships.store') }}" class="card border-0 shadow-sm">
            @csrf
            <div class="card-body p-8">
                @if ($isSuperAdmin)
                    <div class="mb-5"><label class="form-label required">Tenant</label><select name="tenant_id" class="form-select form-select-solid" required><option value="">Select tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach</select></div>
                @endif
                <div class="row g-6">
                    <div class="col-md-6"><label class="form-label required">Customer</label><select name="customer_id" class="form-select form-select-solid" required><option value="">Select customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->full_name }} - {{ $customer->phone }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label required">Membership Plan</label><select name="membership_plan_id" class="form-select form-select-solid" required><option value="">Select plan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->name }} - LKR {{ number_format((float) $plan->price, 2) }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Branch</label><select name="branch_id" class="form-select form-select-solid"><option value="">Customer branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label required">Start Date</label><input type="date" name="start_date" value="{{ old('start_date', today()->toDateString()) }}" class="form-control form-control-solid" required></div>
                    <div class="col-md-4"><label class="form-label">Payment Method</label><select name="payment_method_id" class="form-select form-select-solid"><option value="">Record without invoice</option>@foreach($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Price Paid</label><input type="number" step="0.01" name="price_paid" class="form-control form-control-solid"></div>
                    <div class="col-md-4"><label class="form-label">Joining Fee Paid</label><input type="number" step="0.01" name="joining_fee_paid" class="form-control form-control-solid"></div>
                    <div class="col-md-4"><label class="form-label">Transaction Reference</label><input name="transaction_reference" class="form-control form-control-solid"></div>
                </div>
                <div class="text-end mt-8"><button class="btn btn-primary">Purchase Membership</button></div>
            </div>
        </form>
    </div>
</x-default-layout>
