<x-default-layout>
    @section('title')
        Purchase Membership
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.memberships.create') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <form method="POST" action="{{ route('loyalty-management.memberships.store') }}" class="card border-0 shadow-sm">
            @csrf
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-person-vcard-fill text-primary fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Purchase Membership
                        </h3>
                        <div class="text-muted fs-8">
                            Assign a membership plan to a customer and optionally record payment.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-8">
                @if ($isSuperAdmin)
                    <div class="mb-8">
                        <label class="form-label required fw-semibold">
                            Tenant
                        </label>
                        <select name="tenant_id" class="form-select form-select-solid" required>
                            <option value="">
                                Select tenant
                            </option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}">
                                    {{ $tenant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="row g-6">
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="symbol symbol-35px">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-person-fill text-primary"></i>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold text-gray-900">
                                    Customer & Plan
                                </div>
                                <div class="text-muted fs-8">
                                    Choose who is buying and which package they receive.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required fw-semibold">
                            Customer
                        </label>
                        <select name="customer_id" class="form-select form-select-solid" required>
                            <option value="">
                                Select customer
                            </option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">
                                    {{ $customer->full_name }} - {{ $customer->phone }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required fw-semibold">
                            Membership Plan
                        </label>
                        <select name="membership_plan_id" class="form-select form-select-solid" required>
                            <option value="">
                                Select plan
                            </option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">
                                    {{ $plan->name }} - LKR {{ number_format((float) $plan->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Branch
                        </label>
                        <select name="branch_id" class="form-select form-select-solid">
                            <option value="">
                                Customer branch
                            </option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required fw-semibold">
                            Start Date
                        </label>
                        <input type="date" name="start_date" value="{{ old('start_date', today()->toDateString()) }}"
                            class="form-control form-control-solid" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Payment Method
                        </label>
                        <select name="payment_method_id" class="form-select form-select-solid">
                            <option value="">
                                Record without invoice
                            </option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">
                                    {{ $method->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="separator separator-dashed my-2"></div>
                    </div>

                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="symbol symbol-35px">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-receipt text-success"></i>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold text-gray-900">
                                    Payment Details
                                </div>
                                <div class="text-muted fs-8">
                                    Override charged amounts only when needed.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Price Paid
                        </label>
                        <input type="number" step="0.01" name="price_paid" class="form-control form-control-solid">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Joining Fee Paid
                        </label>
                        <input type="number" step="0.01" name="joining_fee_paid" class="form-control form-control-solid">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Transaction Reference
                        </label>
                        <input name="transaction_reference" class="form-control form-control-solid">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-8">
                    <a href="{{ route('loyalty-management.memberships.index') }}" class="btn btn-light">
                        Cancel
                    </a>
                    <button class="btn btn-primary">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Purchase Membership
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-default-layout>
