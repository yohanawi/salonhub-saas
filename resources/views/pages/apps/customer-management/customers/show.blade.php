<x-default-layout>

    @section('title')
        Customer Profile
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

    <style>
        .customer-profile-hero {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at top right, rgba(114, 57, 234, .16), transparent 34%),
                radial-gradient(circle at bottom left, rgba(80, 205, 137, .08), transparent 25%),
                linear-gradient(135deg, #ffffff 0%, #faf8ff 100%);
        }

        .customer-profile-hero::after {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            right: -120px;
            bottom: -150px;
            background: rgba(114, 57, 234, .05);
        }

        .customer-avatar {
            width: 92px;
            height: 92px;
            min-width: 92px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #7239ea, #9d6df2);
            color: #fff;
            font-size: 30px;
            font-weight: 700;
            letter-spacing: 1px;
            box-shadow: 0 14px 30px rgba(114, 57, 234, .22);
        }

        .customer-stat-card {
            border: 1px solid #f1f1f4 !important;
            transition: all .2s ease;
        }

        .customer-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(30, 33, 41, .08) !important;
        }

        .customer-stat-icon {
            width: 48px;
            height: 48px;
            min-width: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
        }

        .profile-section {
            border: 1px solid #f1f1f4 !important;
            overflow: hidden;
        }

        .profile-section .card-header {
            min-height: 72px;
        }

        .detail-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .profile-note-box {
            border-left: 3px solid #7239ea;
            background: #faf8ff;
        }

        .timeline {
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 20px;
            bottom: 20px;
            left: 18px;
            width: 2px;
            background: #f1f1f4;
        }

        .timeline-item {
            position: relative;
            padding-left: 55px;
            padding-bottom: 25px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: 10px;
            top: 5px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #7239ea;
            border: 4px solid #f3efff;
            z-index: 2;
        }

        .appointment-item,
        .note-item {
            transition: background-color .2s ease;
        }

        .appointment-item:hover,
        .note-item:hover {
            background: #fafafa;
        }

        .empty-state {
            padding: 35px 20px;
            text-align: center;
            border: 1px dashed #e4e6ef;
            border-radius: 14px;
            background: #fcfcfd;
        }

        .empty-state-icon {
            width: 58px;
            height: 58px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: #f1f1f4;
            margin-bottom: 13px;
        }

        .payment-summary-box {
            border: 1px solid #f1f1f4;
            border-radius: 14px;
            background: #fcfcfd;
        }

        .payment-progress {
            height: 8px;
            border-radius: 20px;
            background: #eef0f4;
            overflow: hidden;
        }

        .payment-progress-bar {
            height: 100%;
            border-radius: 20px;
            background: linear-gradient(90deg, #50cd89, #20c997);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            display: inline-block;
            border-radius: 50%;
        }

        .customer-contact-line {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: center;
        }

        @media (max-width: 767.98px) {
            .customer-avatar {
                width: 72px;
                height: 72px;
                min-width: 72px;
                font-size: 24px;
                border-radius: 20px;
            }
        }
    </style>


    <div id="kt_app_content_container" class="app-container container-xxl">

        {{-- ========================================================= --}}
        {{-- SUCCESS MESSAGE --}}
        {{-- ========================================================= --}}
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-7">

                <span class="customer-stat-icon bg-light-success me-4">
                    <i class="bi bi-check-circle-fill fs-2 text-success"></i>
                </span>

                <div>
                    <div class="fw-bold fs-6">Success</div>
                    <div>{{ session('status') }}</div>
                </div>

            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- CUSTOMER HERO --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm customer-profile-hero mb-7">

            <div class="card-body position-relative p-7 p-lg-10">

                <div class="d-flex flex-column flex-xl-row justify-content-between gap-7">

                    <div class="d-flex flex-column flex-md-row align-items-md-start gap-5">

                        {{-- Avatar --}}
                        <div class="customer-avatar">
                            {{ $initials ?: 'CU' }}
                        </div>


                        {{-- Main Information --}}
                        <div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                                <h1 class="fw-bolder fs-2x text-gray-900 mb-0">
                                    {{ $customer->full_name }}
                                </h1>


                                <span class="badge badge-light-{{ $statusClass }} px-3 py-2">
                                    <span class="status-dot bg-{{ $statusClass }} me-2"></span>
                                    {{ $customer->status_label }}
                                </span>


                                @if ($customer->marketing_consent)
                                    <span class="badge badge-light-info px-3 py-2">
                                        <i class="bi bi-megaphone-fill me-2"></i>
                                        Marketing Consent
                                    </span>
                                @endif

                            </div>


                            <div class="customer-contact-line text-muted mb-4">

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


                            <div class="profile-note-box rounded px-4 py-3 text-gray-700">

                                <i class="bi bi-chat-square-heart text-primary me-2"></i>

                                {{ $customer->notes ?: 'No profile notes have been added for this customer yet.' }}

                            </div>

                        </div>

                    </div>


                    {{-- Actions --}}
                    <div class="d-flex flex-wrap align-items-start gap-3">

                        <a href="{{ route('customer-management.customers.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back
                        </a>


                        @can('update', $customer)
                            <a href="{{ route('customer-management.customers.edit', $customer) }}" class="btn btn-primary">
                                <i class="bi bi-pencil-square me-2"></i>
                                Edit Customer
                            </a>
                        @endcan


                        @can('delete', $customer)
                            <form method="POST" action="{{ route('customer-management.customers.destroy', $customer) }}"
                                onsubmit="return confirm('Are you sure you want to deactivate this customer?')">

                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-light-warning">
                                    <i class="bi bi-person-dash me-2"></i>
                                    Deactivate
                                </button>

                            </form>
                        @endcan

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- CUSTOMER STATS --}}
        {{-- ========================================================= --}}
        <div class="row g-5 mb-7">

            {{-- Branch --}}
            <div class="col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm customer-stat-card h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center">

                            <span class="customer-stat-icon bg-light-primary me-4">
                                <i class="bi bi-shop fs-2 text-primary"></i>
                            </span>

                            <div>
                                <div class="text-muted fs-8 fw-semibold mb-1">
                                    PRIMARY BRANCH
                                </div>

                                <div class="fw-bold fs-5 text-gray-900">
                                    {{ $customer->branch?->name ?? 'Not Assigned' }}
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Visits --}}
            <div class="col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm customer-stat-card h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center">

                            <span class="customer-stat-icon bg-light-info me-4">
                                <i class="bi bi-calendar2-check fs-2 text-info"></i>
                            </span>

                            <div>

                                <div class="text-muted fs-8 fw-semibold mb-1">
                                    TOTAL VISITS
                                </div>

                                <div class="fw-bolder fs-2 text-gray-900">
                                    {{ $customer->appointments_count }}
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Spend --}}
            <div class="col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm customer-stat-card h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center">

                            <span class="customer-stat-icon bg-light-success me-4">
                                <i class="bi bi-cash-stack fs-2 text-success"></i>
                            </span>

                            <div>

                                <div class="text-muted fs-8 fw-semibold mb-1">
                                    TOTAL SPEND
                                </div>

                                <div class="fw-bold fs-5 text-gray-900">
                                    LKR {{ number_format($totalSpend, 2) }}
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Last Visit --}}
            <div class="col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm customer-stat-card h-100">

                    <div class="card-body p-6">

                        <div class="d-flex align-items-center">

                            <span class="customer-stat-icon bg-light-warning me-4">
                                <i class="bi bi-clock-history fs-2 text-warning"></i>
                            </span>

                            <div>

                                <div class="text-muted fs-8 fw-semibold mb-1">
                                    LAST VISIT
                                </div>

                                <div class="fw-bold fs-6 text-gray-900">
                                    {{ optional($customer->last_visit_at)->format('M d, Y') ?: 'No visits yet' }}
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="row g-7">

            {{-- ===================================================== --}}
            {{-- LEFT SIDE --}}
            {{-- ===================================================== --}}
            <div class="col-xl-5">


                {{-- ================================================= --}}
                {{-- CUSTOMER OVERVIEW --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm profile-section mb-7">

                    <div class="card-header border-0">

                        <div class="card-title">

                            <span class="customer-stat-icon bg-light-primary me-4">
                                <i class="bi bi-person-lines-fill fs-3 text-primary"></i>
                            </span>

                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Customer Details
                                </h3>

                                <div class="text-muted fs-8">
                                    Personal and contact information
                                </div>
                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-3">

                        <div class="row g-5">

                            {{-- Phone --}}
                            <div class="col-md-6 col-xl-12 col-xxl-6">

                                <div class="d-flex align-items-center">

                                    <span class="detail-icon bg-light-primary me-4">
                                        <i class="bi bi-telephone text-primary"></i>
                                    </span>

                                    <div class="overflow-hidden">

                                        <div class="text-muted fs-8 mb-1">
                                            Phone
                                        </div>

                                        <div class="fw-semibold text-gray-900">
                                            {{ $customer->phone ?: '-' }}
                                        </div>

                                    </div>

                                </div>

                            </div>


                            {{-- Email --}}
                            <div class="col-md-6 col-xl-12 col-xxl-6">

                                <div class="d-flex align-items-center">

                                    <span class="detail-icon bg-light-info me-4">
                                        <i class="bi bi-envelope text-info"></i>
                                    </span>

                                    <div class="overflow-hidden">

                                        <div class="text-muted fs-8 mb-1">
                                            Email
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

                                    <span class="detail-icon bg-light-success me-4">
                                        <i class="bi bi-person text-success"></i>
                                    </span>

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

                                    <span class="detail-icon bg-light-warning me-4">
                                        <i class="bi bi-cake2 text-warning"></i>
                                    </span>

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

                                <div class="d-flex align-items-start">

                                    <span class="detail-icon bg-light-danger me-4">
                                        <i class="bi bi-geo-alt text-danger"></i>
                                    </span>

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


                {{-- ================================================= --}}
                {{-- NOTES --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm profile-section">

                    <div class="card-header border-0">

                        <div class="card-title">

                            <span class="customer-stat-icon bg-light-warning me-4">
                                <i class="bi bi-journal-text fs-3 text-warning"></i>
                            </span>

                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Customer Notes
                                </h3>

                                <div class="text-muted fs-8">
                                    Salon observations and preference history
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-3">

                        @can('viewNotes', $customer)

                            @forelse ($customer->noteEntries->sortByDesc('created_at') as $note)
                                <div
                                    class="note-item rounded px-3 py-4
                                    {{ !$loop->last ? 'border-bottom' : '' }}">

                                    <div class="d-flex">

                                        <span class="symbol symbol-40px me-4">

                                            <span class="symbol-label bg-light-warning">
                                                <i class="bi bi-chat-quote text-warning"></i>
                                            </span>

                                        </span>


                                        <div>

                                            <div class="text-gray-800 fw-semibold lh-lg">
                                                {{ $note->note }}
                                            </div>


                                            <div class="text-muted fs-8 mt-2">

                                                <i class="bi bi-person me-1"></i>

                                                {{ $note->user?->name ?? 'System' }}

                                                <span class="mx-1">•</span>

                                                <i class="bi bi-clock me-1"></i>

                                                {{ $note->created_at->format('M d, Y · h:i A') }}

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            @empty

                                <div class="empty-state">

                                    <div class="empty-state-icon">
                                        <i class="bi bi-journal-plus fs-2 text-muted"></i>
                                    </div>

                                    <div class="fw-bold text-gray-800 mb-1">
                                        No Customer Notes
                                    </div>

                                    <div class="text-muted fs-8">
                                        No note history has been recorded for this customer yet.
                                    </div>

                                </div>
                            @endforelse
                        @else
                            <div class="empty-state">

                                <div class="empty-state-icon">
                                    <i class="bi bi-shield-lock fs-2 text-muted"></i>
                                </div>

                                <div class="fw-bold text-gray-800 mb-1">
                                    Notes Restricted
                                </div>

                                <div class="text-muted fs-8">
                                    You don't have permission to view customer notes.
                                </div>

                            </div>

                        @endcan

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- RIGHT SIDE --}}
            {{-- ===================================================== --}}
            <div class="col-xl-7">


                {{-- ================================================= --}}
                {{-- APPOINTMENT HISTORY --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm profile-section mb-7">

                    <div class="card-header border-0">

                        <div class="card-title">

                            <span class="customer-stat-icon bg-light-info me-4">
                                <i class="bi bi-calendar2-week fs-3 text-info"></i>
                            </span>

                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Appointment History
                                </h3>

                                <div class="text-muted fs-8">
                                    Most recent salon appointments
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-3">

                        @forelse ($customer->appointments
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

                            <div
                                class="appointment-item d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 rounded px-3 py-4
                                {{ !$loop->last ? 'border-bottom' : '' }}">

                                <div class="d-flex align-items-center">

                                    <span class="symbol symbol-45px me-4">

                                        <span class="symbol-label bg-light-info">
                                            <i class="bi bi-calendar-check fs-3 text-info"></i>
                                        </span>

                                    </span>


                                    <div>

                                        <div class="fw-bold fs-6 text-gray-900 mb-1">
                                            {{ $appointment->branch?->name ?? 'Branch not available' }}
                                        </div>


                                        <div class="text-muted fs-8">

                                            <i class="bi bi-calendar3 me-1"></i>

                                            {{ optional($appointment->starts_at)->format('M d, Y') }}

                                            <span class="mx-1">•</span>

                                            <i class="bi bi-clock me-1"></i>

                                            {{ optional($appointment->starts_at)->format('h:i A') }}

                                        </div>

                                    </div>

                                </div>


                                <span class="badge badge-light-{{ $appointmentStatusClass }} px-3 py-2">
                                    {{ str($appointment->status)->replace('_', ' ')->headline() }}
                                </span>

                            </div>

                        @empty

                            <div class="empty-state">

                                <div class="empty-state-icon">
                                    <i class="bi bi-calendar-x fs-2 text-muted"></i>
                                </div>

                                <div class="fw-bold text-gray-800 mb-1">
                                    No Appointment History
                                </div>

                                <div class="text-muted fs-8">
                                    This customer hasn't completed or scheduled any appointments yet.
                                </div>

                            </div>
                        @endforelse

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- PAYMENT SUMMARY --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm profile-section mb-7">

                    <div class="card-header border-0">

                        <div class="card-title">

                            <span class="customer-stat-icon bg-light-success me-4">
                                <i class="bi bi-wallet2 fs-3 text-success"></i>
                            </span>

                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Payment Summary
                                </h3>

                                <div class="text-muted fs-8">
                                    Customer billing and payment information
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-3">

                        @if ($customer->sales->isNotEmpty())
                            <div class="row g-4 mb-5">

                                {{-- Billed --}}
                                <div class="col-md-4">

                                    <div class="payment-summary-box p-5 h-100">

                                        <div class="text-muted fs-8 fw-semibold mb-2">
                                            TOTAL BILLED
                                        </div>

                                        <div class="fw-bolder fs-5 text-gray-900">
                                            LKR {{ number_format($totalSpend, 2) }}
                                        </div>

                                    </div>

                                </div>


                                {{-- Paid --}}
                                <div class="col-md-4">

                                    <div class="payment-summary-box p-5 h-100">

                                        <div class="text-muted fs-8 fw-semibold mb-2">
                                            TOTAL PAID
                                        </div>

                                        <div class="fw-bolder fs-5 text-success">
                                            LKR {{ number_format($totalPaid, 2) }}
                                        </div>

                                    </div>

                                </div>


                                {{-- Balance --}}
                                <div class="col-md-4">

                                    <div class="payment-summary-box p-5 h-100">

                                        <div class="text-muted fs-8 fw-semibold mb-2">
                                            BALANCE
                                        </div>

                                        <div
                                            class="fw-bolder fs-5 {{ $balance > 0 ? 'text-danger' : 'text-gray-900' }}">
                                            LKR {{ number_format($balance, 2) }}
                                        </div>

                                    </div>

                                </div>

                            </div>


                            <div class="d-flex justify-content-between align-items-center mb-2">

                                <span class="text-muted fs-8 fw-semibold">
                                    Payment Progress
                                </span>

                                <span class="fw-bold text-gray-900">
                                    {{ number_format($paymentProgress, 0) }}%
                                </span>

                            </div>


                            <div class="payment-progress">

                                <div class="payment-progress-bar" style="width: {{ $paymentProgress }}%;"></div>

                            </div>
                        @else
                            <div class="empty-state">

                                <div class="empty-state-icon">
                                    <i class="bi bi-credit-card fs-2 text-muted"></i>
                                </div>

                                <div class="fw-bold text-gray-800 mb-1">
                                    No Payment History
                                </div>

                                <div class="text-muted fs-8">
                                    Billing information will appear after the customer makes a purchase.
                                </div>

                            </div>
                        @endif

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- SERVICE HISTORY --}}
                {{-- ================================================= --}}
                <div class="card border-0 shadow-sm profile-section">

                    <div class="card-header border-0">

                        <div class="card-title">

                            <span class="customer-stat-icon bg-light-primary me-4">
                                <i class="bi bi-scissors fs-3 text-primary"></i>
                            </span>

                            <div>

                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Service History
                                </h3>

                                <div class="text-muted fs-8">
                                    Treatments and salon services used by this customer
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card-body pt-3">

                        {{-- Future dynamic service history --}}
                        <div class="empty-state">

                            <div class="empty-state-icon">
                                <i class="bi bi-stars fs-2 text-muted"></i>
                            </div>

                            <div class="fw-bold text-gray-800 mb-1">
                                Service History Coming From Bookings & POS
                            </div>

                            <div class="text-muted fs-8 mx-auto" style="max-width: 480px;">
                                Completed appointments and POS sales can be used to build
                                a complete timeline of services, staff members, prices and customer preferences.
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-default-layout>
