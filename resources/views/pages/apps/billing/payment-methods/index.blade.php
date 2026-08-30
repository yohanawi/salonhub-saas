<x-default-layout>
    @section('title')
        Payment Methods
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.payment-methods.index') }}
    @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center border-0 shadow-sm mb-8">
                <i class="bi bi-check-circle-fill fs-2 me-4"></i>
                <div class="fw-semibold">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- PAGE HERO --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm overflow-hidden mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-8">
                    <div class="d-flex align-items-start gap-5">
                        <div class="d-flex align-items-center justify-content-center rounded-4 bg-light-primary flex-shrink-0"
                            style="width: 52px; height: 52px;">
                            <i class="bi bi-wallet2 text-primary fs-1"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Payment Methods
                                </h3>
                                <span class="badge badge-light-primary px-3 py-2">
                                    POS Settings
                                </span>
                            </div>
                            <div class="text-muted fs-7">
                                Configure the ways customers can pay during checkout.
                            </div>
                        </div>
                    </div>
                    @can('create', \App\Models\PaymentMethod::class)
                        <a href="{{ route('billing.payment-methods.create') }}"
                            class="btn btn-primary align-self-start d-flex align-items-center">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            Add Payment Method
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- PAYMENT METHODS LIST --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Checkout Methods
                        </h3>
                        <div class="text-muted fs-7">
                            Payment options available to the POS and billing workflow.
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <span class="badge badge-light-primary px-4 py-3">
                        {{ number_format($paymentMethods->total()) }}
                        {{ Str::plural('Method', $paymentMethods->total()) }}
                    </span>
                </div>
            </div>

            <div class="card-body pt-3 px-0 px-md-9 pb-8">
                @if ($paymentMethods->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-5">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-220px">Payment Method</th>
                                    @if ($isSuperAdmin ?? false)
                                        <th class="min-w-170px">Salon</th>
                                    @endif
                                    <th class="min-w-100px">Code</th>
                                    <th class="min-w-140px">Type</th>
                                    <th class="min-w-150px">Reference</th>
                                    <th class="min-w-120px">Status</th>
                                    <th class="text-end min-w-100px">Action</th>
                                </tr>
                            </thead>

                            <tbody class="fw-semibold text-gray-700">
                                @foreach ($paymentMethods as $method)
                                    @php
                                        $type = strtolower((string) $method->type);

                                        $methodConfig = match ($type) {
                                            'cash' => [
                                                'icon' => 'bi-cash-stack',
                                                'class' => 'success',
                                            ],

                                            'card' => [
                                                'icon' => 'bi-credit-card',
                                                'class' => 'primary',
                                            ],

                                            'bank_transfer', 'bank' => [
                                                'icon' => 'bi-bank',
                                                'class' => 'info',
                                            ],

                                            'online' => [
                                                'icon' => 'bi-globe2',
                                                'class' => 'warning',
                                            ],

                                            'wallet' => [
                                                'icon' => 'bi-wallet2',
                                                'class' => 'info',
                                            ],

                                            default => [
                                                'icon' => 'bi-credit-card-2-front',
                                                'class' => 'secondary',
                                            ],
                                        };
                                    @endphp
                                    <tr>
                                        {{-- Method --}}
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex align-items-center justify-content-center rounded-3 bg-light-{{ $methodConfig['class'] }} me-4 flex-shrink-0"
                                                    style="width: 46px; height: 46px;">
                                                    <i
                                                        class="bi {{ $methodConfig['icon'] }} text-{{ $methodConfig['class'] }} fs-3"></i>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-gray-900 fs-6">
                                                        {{ $method->name }}
                                                    </span>
                                                    <span class="text-muted fs-8 mt-1">
                                                        POS payment option
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Salon --}}
                                        @if ($isSuperAdmin ?? false)
                                            <td>
                                                @if ($method->tenant)
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-semibold text-gray-900">
                                                            {{ $method->tenant->name }}
                                                        </span>
                                                        <span class="text-muted fs-8">
                                                            Tenant
                                                        </span>
                                                    </div>
                                                @else
                                                    <span class="text-muted">
                                                        —
                                                    </span>
                                                @endif
                                            </td>
                                        @endif

                                        {{-- Code --}}
                                        <td>
                                            <span class="badge badge-light px-3 py-2 font-monospace">
                                                {{ $method->code }}
                                            </span>
                                        </td>

                                        {{-- Type --}}
                                        <td>
                                            <span class="badge badge-light-{{ $methodConfig['class'] }} px-3 py-2">
                                                <i class="bi {{ $methodConfig['icon'] }} me-1"></i>
                                                {{ $method->type_label }}
                                            </span>
                                        </td>

                                        {{-- Reference --}}
                                        <td>
                                            @if ($method->requires_reference)
                                                <div class="d-flex align-items-center">
                                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-light-warning me-3"
                                                        style="width: 32px; height: 32px;">
                                                        <i class="bi bi-hash text-warning"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold text-gray-900">
                                                            Required
                                                        </div>
                                                        <div class="text-muted fs-8">
                                                            Transaction reference
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="d-flex align-items-center">
                                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-light me-3"
                                                        style="width: 32px; height: 32px;">
                                                        <i class="bi bi-dash text-muted"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold text-gray-700">
                                                            Optional
                                                        </div>
                                                        <div class="text-muted fs-8">
                                                            No reference needed
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Status --}}
                                        <td>
                                            @if ($method->is_active)
                                                <span class="badge badge-light-success px-3 py-2">
                                                    <i class="bi bi-check-circle-fill me-1"></i>
                                                    Active
                                                </span>
                                            @else
                                                <span class="badge badge-light-danger px-3 py-2">
                                                    <i class="bi bi-x-circle-fill me-1"></i>
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Action --}}
                                        <td class="text-end">
                                            @can('update', $method)
                                                <a href="{{ route('billing.payment-methods.edit', $method) }}"
                                                    class="btn btn-icon btn-sm btn-light-primary" data-bs-toggle="tooltip"
                                                    title="Edit payment method">
                                                    <i class="bi bi-pencil-square fs-5"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">
                                                    —
                                                </span>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 mt-8 px-2">
                        <div class="text-muted fs-7">
                            Showing
                            <span class="fw-bold text-gray-800">
                                {{ $paymentMethods->firstItem() ?? 0 }}
                            </span>
                            to
                            <span class="fw-bold text-gray-800">
                                {{ $paymentMethods->lastItem() ?? 0 }}
                            </span>
                            of
                            <span class="fw-bold text-gray-800">
                                {{ number_format($paymentMethods->total()) }}
                            </span>
                            payment methods
                        </div>
                        <div>
                            {{ $paymentMethods->withQueryString()->links() }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-20 px-5">
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-light-primary mx-auto mb-7"
                            style="width: 88px; height: 88px;">
                            <i class="bi bi-wallet2 text-primary" style="font-size: 2.6rem;"></i>
                        </div>
                        <h2 class="fw-bolder text-gray-900 mb-3">
                            No payment methods yet
                        </h2>
                        <div class="text-muted fs-6 mx-auto mb-7" style="max-width: 480px;">
                            Add payment options such as cash, card, bank transfer or online payment
                            so staff can use them during checkout.
                        </div>
                        @can('create', \App\Models\PaymentMethod::class)
                            <a href="{{ route('billing.payment-methods.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle-fill me-2"></i>
                                Add First Payment Method
                            </a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('pages.apps.billing._sweet-alerts')
</x-default-layout>
