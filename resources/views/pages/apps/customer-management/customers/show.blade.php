<x-default-layout>

    @section('title')
        {{ $customer->first_name }} Profile
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('customer-management.customers.show', $customer) }}
    @endsection

    @php
        $initials = collect(explode(' ', trim($customer->full_name)))
            ->filter()
            ->take(2)
            ->map(fn($name) => strtoupper(substr($name, 0, 1)))
            ->implode('');

        $statusClass = match ($customer->status) {
            'active' => 'success',
            'blocked' => 'danger',
            'inactive' => 'warning',
            default => 'secondary',
        };

        $balance = max(0, $totalSpend - $totalPaid);
        $paymentProgress = $totalSpend > 0 ? min(100, ($totalPaid / $totalSpend) * 100) : 0;
    @endphp

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-8">
                <div class="symbol symbol-45px me-4">
                    <div class="symbol-label bg-light-success">
                        <i class="bi bi-check-circle-fill fs-2 text-success"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-bold text-gray-900 mb-1">
                        Success
                    </div>
                    <div>
                        {{ session('status') }}
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-5 overflow-hidden">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-8">
                    <div class="d-flex flex-column flex-md-row align-items-md-start gap-6">
                        <div class="symbol symbol-70px flex-shrink-0">
                            <div class="symbol-label bg-light-primary text-primary fs-2x fw-bolder rounded-4">
                                {{ $initials ?: 'CU' }}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    {{ $customer->full_name }}
                                </h3>
                                <span class="badge badge-light-{{ $statusClass }} px-3 py-2">
                                    <i class="bi bi-circle-fill fs-9 me-2"></i>
                                    {{ $customer->status_label }}
                                </span>
                                @if ($customer->marketing_consent)
                                    <span class="badge badge-light-info px-3 py-2">
                                        <i class="bi bi-megaphone-fill me-2"></i>
                                        Marketing Consent
                                    </span>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-4 text-muted fs-7 mb-2">
                                <span>
                                    <i class="bi bi-person-vcard me-1"></i>
                                    {{ $customer->customer_code }}
                                </span>
                                <span>
                                    <i class="bi bi-telephone me-1"></i>
                                    {{ $customer->phone }}
                                </span>
                                @if ($customer->email)
                                    <span>
                                        <i class="bi bi-envelope me-1"></i>
                                        {{ $customer->email }}
                                    </span>
                                @endif
                            </div>
                            <div class="rounded-3 bg-light-primary p-4">
                                <div class="d-flex align-items-start">
                                    <div class="symbol symbol-35px me-3 flex-shrink-0">
                                        <div class="symbol-label bg-white">
                                            <i class="bi bi-chat-square-heart text-primary"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-muted fs-9 fw-semibold text-uppercase mb-1">
                                            Customer Note
                                        </div>
                                        <div class="text-gray-700">
                                            {{ $customer->notes ?: 'No profile notes have been added for this customer yet.' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <a href="{{ route('customer-management.customers.index') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back
                        </a>
                        @can('update', $customer)
                            <a href="{{ route('customer-management.customers.edit', $customer) }}"
                                class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil-square me-2"></i>
                                Edit Customer
                            </a>
                        @endcan
                        @can('delete', $customer)
                            <form method="POST" action="{{ route('customer-management.customers.destroy', $customer) }}"
                                data-swal-confirm
                                data-swal-title="Deactivate customer?"
                                data-swal-text="This customer will be marked as inactive."
                                data-swal-confirm-button="Yes, deactivate"
                                data-swal-cancel-button="Cancel">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-light-danger btn-sm">
                                    <i class="bi bi-person-dash me-2"></i>
                                    Deactivate
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 mb-5">
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-shop fs-3 text-primary"></i>
                                </div>
                            </div>
                            <span class="badge badge-light-primary">
                                Branch
                            </span>
                        </div>
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-2">
                            Primary Branch
                        </div>
                        <div class="fw-bold fs-5 text-gray-900">
                            {{ $customer->branch?->name ?? 'Not Assigned' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Visits --}}
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-calendar2-check fs-3 text-info"></i>
                                </div>
                            </div>
                            <span class="badge badge-light-info">
                                Visits
                            </span>
                        </div>
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">
                            Total Visits
                        </div>
                        <div class="fw-bolder fs-2x text-gray-900">
                            {{ number_format($customer->appointments_count) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Spend --}}
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-cash-stack fs-3 text-success"></i>
                                </div>
                            </div>
                            <span class="badge badge-light-success">
                                Revenue
                            </span>
                        </div>
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-2">
                            Total Spend
                        </div>
                        <div class="fw-bolder fs-5 text-success">
                            LKR {{ number_format($totalSpend, 2) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Last Visit --}}
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-clock-history fs-3 text-warning"></i>
                                </div>
                            </div>
                            <span class="badge badge-light-warning">
                                Activity
                            </span>
                        </div>
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-2">
                            Last Visit
                        </div>
                        <div class="fw-bold fs-6 text-gray-900">
                            {{ optional($customer->last_visit_at)->format('M d, Y') ?: 'No visits yet' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-8">
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-person-lines-fill text-primary fs-3"></i>
                                </div>
                            </div>
                            <div>

                        @include('pages.apps.customer-management.customers._sweet-alerts')
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Customer Details
                                </h3>
                                <div class="text-muted fs-8">
                                    Personal and contact information.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-4">
                        <div class="row g-6">
                            <div class="col-md-6 col-xl-12 col-xxl-6">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-light-primary">
                                            <i class="bi bi-telephone text-primary"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-muted fs-8 mb-1">
                                            Phone Number
                                        </div>
                                        <div class="fw-semibold text-gray-900">
                                            {{ $customer->phone ?: 'Not provided' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Email --}}
                            <div class="col-md-6 col-xl-12 col-xxl-6">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-light-info">
                                            <i class="bi bi-envelope text-info"></i>
                                        </div>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-muted fs-8 mb-1">
                                            Email Address
                                        </div>
                                        <div class="fw-semibold text-gray-900 text-truncate"
                                            title="{{ $customer->email }}">
                                            {{ $customer->email ?: 'Not provided' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Gender --}}
                            <div class="col-md-6 col-xl-12 col-xxl-6">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-light-success">
                                            <i class="bi bi-person text-success"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-muted fs-8 mb-1">
                                            Gender
                                        </div>
                                        <div class="fw-semibold text-gray-900">
                                            {{ $customer->gender ? str($customer->gender)->headline() : 'Not specified' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- DOB --}}
                            <div class="col-md-6 col-xl-12 col-xxl-6">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-light-warning">
                                            <i class="bi bi-cake2 text-warning"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-muted fs-8 mb-1">
                                            Date of Birth
                                        </div>
                                        <div class="fw-semibold text-gray-900">
                                            {{ optional($customer->date_of_birth)->format('M d, Y') ?: 'Not provided' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Address --}}
                            <div class="col-12">
                                <div class="separator separator-dashed my-2"></div>
                                <div class="d-flex align-items-start mt-5">
                                    <div class="symbol symbol-45px me-4 flex-shrink-0">
                                        <div class="symbol-label bg-light-danger">
                                            <i class="bi bi-geo-alt text-danger"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-muted fs-8 mb-1">
                                            Address
                                        </div>
                                        <div class="fw-semibold text-gray-900">
                                            {{ $customer->address ?: 'No address provided' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-journal-text text-warning fs-3"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Customer Notes
                                </h3>
                                <div class="text-muted fs-8">
                                    Salon observations and customer preferences.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-3">
                        @can('viewNotes', $customer)
                            @forelse (($customer->noteEntries ?? collect())->sortByDesc('created_at') as $note)
                                <div class="py-5 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">
                                    <div class="d-flex align-items-start">
                                        <div class="symbol symbol-40px me-4 flex-shrink-0">
                                            <div class="symbol-label bg-light-warning">
                                                <i class="bi bi-chat-quote text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="text-gray-800 fw-semibold lh-lg">
                                                {{ $note->note }}
                                            </div>
                                            <div class="d-flex flex-wrap align-items-center gap-3 text-muted fs-8 mt-3">
                                                <span>
                                                    <i class="bi bi-person me-1"></i>
                                                    {{ $note->user?->name ?? 'System' }}
                                                </span>
                                                <span>
                                                    <i class="bi bi-clock me-1"></i>
                                                    {{ $note->created_at->format('M d, Y · h:i A') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-12">
                                    <div class="symbol symbol-70px mb-5">
                                        <div class="symbol-label bg-light">
                                            <i class="bi bi-journal-plus text-muted fs-1"></i>
                                        </div>
                                    </div>
                                    <h4 class="fw-bold text-gray-900 mb-2">
                                        No Customer Notes
                                    </h4>
                                    <div class="text-muted fs-7">
                                        No note history has been recorded for this customer.
                                    </div>
                                </div>
                            @endforelse
                        @else
                            <div class="text-center py-12">
                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light-danger">
                                        <i class="bi bi-shield-lock text-danger fs-1"></i>
                                    </div>
                                </div>
                                <h4 class="fw-bold text-gray-900 mb-2">
                                    Notes Restricted
                                </h4>
                                <div class="text-muted fs-7">
                                    You don't have permission to view customer notes.
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-calendar2-week text-info fs-3"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Appointment History
                                </h3>
                                <div class="text-muted fs-8">
                                    Most recent salon appointments.
                                </div>
                            </div>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-info px-3 py-2">
                                {{ number_format($customer->appointments_count) }}
                                {{ Str::plural('Visit', $customer->appointments_count) }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body pt-3">
                        @forelse (($customer->appointments ?? collect())
                                ->sortByDesc('starts_at')
                                ->take(8)
                            as $appointment)
                            @php
                                $appointmentStatusClass = match ($appointment->status) {
                                    'completed' => 'success',
                                    'confirmed' => 'primary',
                                    'booked', 'scheduled' => 'info',
                                    'cancelled' => 'danger',
                                    'no_show' => 'warning',
                                    default => 'secondary',
                                };
                            @endphp
                            <div class="py-5 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">
                                <div
                                    class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-45px me-4">
                                            <div class="symbol-label bg-light-info">
                                                <i class="bi bi-calendar-check text-info fs-3"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-bold fs-6 text-gray-900 mb-2">
                                                {{ $appointment->branch?->name ?? 'Branch not available' }}
                                            </div>
                                            <div class="d-flex flex-wrap gap-3 text-muted fs-8">
                                                <span>
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    {{ optional($appointment->starts_at)->format('M d, Y') }}
                                                </span>
                                                <span>
                                                    <i class="bi bi-clock me-1"></i>
                                                    {{ optional($appointment->starts_at)->format('h:i A') }}
                                                </span>
                                                @if ($appointment->appointment_number)
                                                    <span>
                                                        <i class="bi bi-hash me-1"></i>
                                                        {{ $appointment->appointment_number }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge badge-light-{{ $appointmentStatusClass }} px-3 py-2">
                                        {{ str($appointment->status)->replace('_', ' ')->headline() }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light">
                                        <i class="bi bi-calendar-x text-muted fs-1"></i>
                                    </div>
                                </div>
                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No Appointment History
                                </h4>
                                <div class="text-muted fs-7">
                                    This customer has not made any appointments yet.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-4">
                        <div class="card-title">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-wallet2 text-success fs-3"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Payment Summary
                                </h3>
                                <div class="text-muted fs-8">
                                    Customer billing and payment position.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-4">
                        @if ($customer->sales->isNotEmpty())
                            <div class="row g-4 mb-2">
                                <div class="col-md-4">
                                    <div class="rounded-4 bg-light-primary p-4 h-100">
                                        <div class="d-flex align-items-center justify-content-between mb-4">
                                            <div class="symbol symbol-40px">
                                                <div class="symbol-label bg-white">
                                                    <i class="bi bi-receipt text-primary"></i>
                                                </div>
                                            </div>
                                            <span class="badge badge-light-primary">
                                                Billed
                                            </span>
                                        </div>
                                        <div class="text-muted fs-8 fw-semibold mb-1">
                                            TOTAL BILLED
                                        </div>
                                        <div class="fw-bolder fs-5 text-gray-900">
                                            LKR {{ number_format($totalSpend, 2) }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Paid --}}
                                <div class="col-md-4">
                                    <div class="rounded-4 bg-light-primary p-4 h-100">
                                        <div class="d-flex align-items-center justify-content-between mb-4">
                                            <div class="symbol symbol-40px">
                                                <div class="symbol-label bg-white">
                                                    <i class="bi bi-check-circle text-success"></i>
                                                </div>
                                            </div>
                                            <span class="badge badge-light-success">
                                                Collected
                                            </span>
                                        </div>
                                        <div class="text-muted fs-8 fw-semibold mb-1">
                                            TOTAL PAID
                                        </div>
                                        <div class="fw-bolder fs-5 text-success">
                                            LKR {{ number_format($totalPaid, 2) }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Balance --}}
                                <div class="col-md-4">
                                    <div
                                        class="rounded-4 {{ $balance > 0 ? 'bg-light-danger' : 'bg-light-success' }} p-5 h-100">
                                        <div class="d-flex align-items-center justify-content-between mb-4">
                                            <div class="symbol symbol-40px">
                                                <div class="symbol-label bg-white">
                                                    <i
                                                        class="bi {{ $balance > 0 ? 'bi-exclamation-circle text-danger' : 'bi-check2-circle text-success' }}"></i>
                                                </div>
                                            </div>
                                            <span
                                                class="badge {{ $balance > 0 ? 'badge-light-danger' : 'badge-light-success' }}">
                                                {{ $balance > 0 ? 'Due' : 'Settled' }}
                                            </span>
                                        </div>
                                        <div class="text-muted fs-8 fw-semibold mb-1">
                                            BALANCE
                                        </div>
                                        <div
                                            class="fw-bolder fs-5 {{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                                            LKR {{ number_format($balance, 2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Progress --}}
                            <div class="rounded-4 bg-light p-5">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="fw-bold text-gray-900">
                                            Payment Progress
                                        </div>
                                        <div class="text-muted fs-8">
                                            Percentage of billed amount collected.
                                        </div>
                                    </div>
                                    <span class="badge badge-light-success px-3 py-2 fs-7">
                                        {{ number_format($paymentProgress, 0) }}%
                                    </span>
                                </div>
                                <div class="progress h-8px bg-gray-200">
                                    <div class="progress-bar bg-success" role="progressbar"
                                        style="width: {{ $paymentProgress }}%;"
                                        aria-valuenow="{{ $paymentProgress }}" aria-valuemin="0"
                                        aria-valuemax="100"></div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light">
                                        <i class="bi bi-credit-card text-muted fs-1"></i>
                                    </div>
                                </div>
                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No Payment History
                                </h4>
                                <div class="text-muted fs-7">
                                    Billing information will appear after the customer makes a purchase.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-scissors text-primary fs-3"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Service History
                                </h3>
                                <div class="text-muted fs-8">
                                    Treatments and services used by this customer.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <div class="text-center py-12">
                            <div class="symbol symbol-70px mb-5">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-stars text-primary fs-1"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-gray-900 mb-2">
                                Service History
                            </h4>
                            <div class="text-muted fs-7 mx-auto mb-5">
                                Completed appointments and POS transactions can be combined
                                to build the customer's complete service history.
                            </div>
                            <div class="d-flex justify-content-center flex-wrap gap-2">
                                <span class="badge badge-light-primary px-3 py-2">
                                    <i class="bi bi-scissors me-1"></i>
                                    Services
                                </span>
                                <span class="badge badge-light-info px-3 py-2">
                                    <i class="bi bi-person me-1"></i>
                                    Staff
                                </span>
                                <span class="badge badge-light-success px-3 py-2">
                                    <i class="bi bi-cash me-1"></i>
                                    Pricing
                                </span>
                                <span class="badge badge-light-warning px-3 py-2">
                                    <i class="bi bi-heart me-1"></i>
                                    Preferences
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
