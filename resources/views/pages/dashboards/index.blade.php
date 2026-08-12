<x-default-layout>

    @section('title')
        Dashboard
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('dashboard') }}
    @endsection

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
            @include('partials/widgets/cards/_widget-20')

            @include('partials/widgets/cards/_widget-7')
        </div>
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
            @include('partials/widgets/cards/_widget-17')

            @include('partials/widgets/lists/_widget-26')
        </div>
        <div class="col-xxl-6">
            @include('partials/widgets/engage/_widget-10')
        </div>
    </div>

    <div class="row gx-5 gx-xl-10">
        <div class="col-xxl-6 mb-5 mb-xl-10">
            @include('partials/widgets/charts/_widget-8')
        </div>
        <div class="col-xl-6 mb-5 mb-xl-10">
            @include('partials/widgets/tables/_widget-16')
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-xxl-6">
            @include('partials/widgets/cards/_widget-18')
        </div>
        <div class="col-xl-6">
            @include('partials/widgets/charts/_widget-36')
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-xl-4">
            @include('partials/widgets/charts/_widget-35')
        </div>
        <div class="col-xl-8">
            @include('partials/widgets/tables/_widget-14')
        </div>
    </div>

    <div class="row gx-5 gx-xl-10">
        <div class="col-xl-4">
            @include('partials/widgets/charts/_widget-31')
        </div>
        <div class="col-xl-8">
            @include('partials/widgets/charts/_widget-24')
        </div>
    </div>

    @if ($showOnboardingModal)
        <style>
            .onboarding-plan-card {
                transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease, transform .15s ease;
            }

            .onboarding-plan-card:hover {
                border-color: var(--bs-primary) !important;
                box-shadow: 0 .5rem 1.25rem rgba(15, 23, 42, .08);
                transform: translateY(-1px);
            }

            .onboarding-plan-card.is-selected {
                border-color: var(--bs-primary) !important;
                background-color: var(--bs-primary-light);
                box-shadow: 0 0 0 .15rem rgba(var(--bs-primary-rgb), .18);
            }

            .onboarding-plan-grid {
                max-height: 430px;
                overflow-y: auto;
                overflow-x: hidden;
                padding-right: .35rem;
            }

            .onboarding-plan-grid .badge {
                font-size: .72rem;
            }

            .onboarding-plan-grid .card-body {
                min-height: 100%;
            }
        </style>

        <div class="modal fade" id="kt_onboarding_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
            data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable mw-900px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold mb-0">
                            Set up your salon
                        </h2>
                    </div>

                    <form method="POST" action="{{ route('onboarding.complete') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="modal-body py-8 px-10">
                            @if ($errors->any())
                                <div class="alert alert-danger p-5 mb-8">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <div class="d-flex align-items-center justify-content-between mb-8 flex-wrap gap-4">
                                @foreach (['Business', 'Branch', 'Hours', 'Setup', 'Plan'] as $index => $label)
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-circle badge-light-primary me-2 onboarding-step-badge"
                                            data-step-badge="{{ $index + 1 }}">
                                            {{ $index + 1 }}
                                        </span>
                                        <span class="fw-semibold text-gray-700">{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div data-onboarding-step="1">
                                <h3 class="fw-bold mb-6">Business details</h3>

                                <div class="row g-5">
                                    <div class="col-md-6">
                                        <label class="form-label required">Country</label>
                                        <input type="text" name="country"
                                            value="{{ old('country', auth()->user()->tenant?->country ?? 'Sri Lanka') }}"
                                            class="form-control" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Business phone</label>
                                        <input type="text" name="business_phone"
                                            value="{{ old('business_phone', auth()->user()->tenant?->phone) }}"
                                            class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label required">Currency</label>
                                        <select name="currency" class="form-select" required data-control="select2"
                                            data-hide-search="true">
                                            @foreach (['LKR', 'USD', 'EUR', 'GBP', 'INR'] as $currency)
                                                <option value="{{ $currency }}" @selected(old('currency', auth()->user()->tenant?->currency ?? 'LKR') === $currency)>
                                                    {{ $currency }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label required">Timezone</label>
                                        <select name="timezone" class="form-select" required data-control="select2"
                                            data-hide-search="true">
                                            @foreach (['Asia/Colombo', 'Asia/Kolkata', 'Asia/Dubai', 'Europe/London', 'America/New_York'] as $timezone)
                                                <option value="{{ $timezone }}" @selected(old('timezone', auth()->user()->tenant?->timezone ?? config('app.timezone')) === $timezone)>
                                                    {{ $timezone }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label required">Business type</label>
                                        <select name="business_type" class="form-select" required data-control="select2"
                                            data-hide-search="true">
                                            @foreach (\App\Models\Tenant::BUSINESS_TYPES as $businessType)
                                                <option value="{{ $businessType }}" @selected(old('business_type', auth()->user()->tenant?->business_type ?? 'Beauty Salon') === $businessType)>
                                                    {{ $businessType }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Business logo</label>
                                        <input type="file" name="business_logo" accept="image/*"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="d-none" data-onboarding-step="2">
                                <h3 class="fw-bold mb-6">First branch</h3>

                                <div class="row g-5">
                                    <div class="col-md-6">
                                        <label class="form-label required">Branch name</label>
                                        <input type="text" name="branch_name"
                                            value="{{ old('branch_name', 'Main Branch') }}" class="form-control"
                                            placeholder="Branch name" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="branch_phone" value="{{ old('branch_phone') }}"
                                            placeholder="Branch phone" class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="branch_email"
                                            value="{{ old('branch_email', auth()->user()->email) }}"
                                            class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Postal code</label>
                                        <input type="text" name="branch_postal_code"
                                            placeholder="Branch postal code" value="{{ old('branch_postal_code') }}"
                                            class="form-control">
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label required">Address</label>
                                        <input type="text" name="branch_address" placeholder="Branch address"
                                            value="{{ old('branch_address') }}" class="form-control" required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label required">City</label>
                                        <input type="text" name="branch_city" placeholder="Branch city"
                                            value="{{ old('branch_city', 'Colombo') }}" class="form-control"
                                            required>
                                    </div>
                                </div>
                            </div>

                            <div class="d-none" data-onboarding-step="3">
                                <h3 class="fw-bold mb-3">Business hours</h3>

                                @php
                                    $days = [
                                        1 => ['Monday', '09:00', '19:00', false],
                                        2 => ['Tuesday', '09:00', '19:00', false],
                                        3 => ['Wednesday', '09:00', '19:00', false],
                                        4 => ['Thursday', '09:00', '19:00', false],
                                        5 => ['Friday', '09:00', '19:00', false],
                                        6 => ['Saturday', '09:00', '20:00', false],
                                        7 => ['Sunday', '', '', true],
                                    ];
                                @endphp

                                <div class="d-flex flex-column gap-4">
                                    @foreach ($days as $dayNumber => [$dayName, $opensAt, $closesAt, $isClosed])
                                        <div class="row g-3 align-items-center">
                                            <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]"
                                                value="{{ $dayNumber }}">

                                            <div class="col-md-3 fw-semibold text-gray-800">
                                                {{ $dayName }}
                                            </div>

                                            <div class="col-md-3">
                                                <input type="time" name="hours[{{ $loop->index }}][opens_at]"
                                                    value="{{ $opensAt }}" class="form-control">
                                            </div>

                                            <div class="col-md-3">
                                                <input type="time" name="hours[{{ $loop->index }}][closes_at]"
                                                    value="{{ $closesAt }}" class="form-control">
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-check form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="hours[{{ $loop->index }}][is_closed]" value="1"
                                                        @checked($isClosed)>
                                                    <span class="form-check-label">Closed</span>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="d-none" data-onboarding-step="4">
                                <h3 class="fw-bold mb-6">Initial setup</h3>

                                <div class="mb-8">
                                    <label class="form-label required">Services</label>
                                    <div class="text-muted fs-7 mb-4">Add at least one service your salon offers.</div>

                                    <div id="onboarding-services" class="d-flex flex-column gap-4">
                                        <div class="row g-3 onboarding-service-row">
                                            <div class="col-md-4">
                                                <label class="form-label required">Service name</label>
                                                <input type="text" name="services[0][name]" class="form-control"
                                                    placeholder="Service name" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label required">Category</label>
                                                <input type="text" name="services[0][category]"
                                                    class="form-control" placeholder="Category" value="General"
                                                    required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label required">Duration</label>
                                                <input type="number" name="services[0][duration_minutes]"
                                                    class="form-control" placeholder="Minutes" value="60"
                                                    min="5" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label required">Price</label>
                                                <input type="number" name="services[0][price]" class="form-control"
                                                    placeholder="Price" value="0" min="0" step="0.01"
                                                    required>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-icon btn-light btn-sm invisible"
                                                    tabindex="-1">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-light-primary btn-sm mt-4"
                                        id="add-onboarding-service">
                                        Add another service
                                    </button>
                                </div>

                                <div class="separator my-8"></div>

                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div>
                                        <label class="form-label mb-1">First staff member</label>
                                        <div class="text-muted fs-7">Optional. You can skip this if the salon owner is
                                            the first stylist.</div>
                                    </div>

                                    <button type="button" class="btn btn-light-primary btn-sm" id="add-first-staff">
                                        <i class="bi bi-plus-lg"></i>
                                        Add staff
                                    </button>
                                </div>

                                <div class="row g-3 d-none" id="first-staff-fields">
                                    <div class="col-md-3">
                                        <label class="form-label">First name</label>
                                        <input type="text" name="staff_first_name" class="form-control"
                                            placeholder="First name">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Last name</label>
                                        <input type="text" name="staff_last_name" class="form-control"
                                            placeholder="Last name">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="staff_phone" class="form-control"
                                            placeholder="Phone">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Job title</label>
                                        <input type="text" name="staff_job_title" class="form-control"
                                            placeholder="Job title">
                                    </div>
                                </div>
                            </div>

                            <div class="d-none" data-onboarding-step="5">
                                <h3 class="fw-bold mb-6">Choose subscription plan</h3>

                                @php
                                    $selectedPlanId = old(
                                        'plan_id',
                                        $subscriptionPlans->firstWhere('slug', 'free-trial')?->id ??
                                            $subscriptionPlans->first()?->id,
                                    );
                                @endphp

                                <div class="alert alert-primary d-flex align-items-center p-4 mb-6">
                                    <i class="bi bi-stars fs-2 me-3"></i>
                                    <div class="fw-semibold">
                                        Start with the 14-day trial, or choose a paid plan now. Your selected plan is
                                        saved with this salon.
                                    </div>
                                </div>

                                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4 onboarding-plan-grid"
                                    data-plan-options>
                                    @forelse ($subscriptionPlans as $plan)
                                        @php
                                            $isSelected = (string) $selectedPlanId === (string) $plan->id;
                                            $features = collect($plan->features ?? [])
                                                ->filter(fn($value) => $value !== false)
                                                ->map(function ($value, $key) {
                                                    $label =
                                                        \App\Models\Plan::FEATURE_OPTIONS[$key] ??
                                                        str($key)->headline()->toString();

                                                    return $value === 'limited' ? "{$label} (Limited)" : $label;
                                                })
                                                ->take(4);
                                        @endphp

                                        <div class="col">
                                            <input class="visually-hidden onboarding-plan-input" type="radio"
                                                name="plan_id" id="onboarding-plan-{{ $plan->id }}"
                                                value="{{ $plan->id }}" required @checked($isSelected)>

                                            <label
                                                class="card h-100 border border-2 cursor-pointer onboarding-plan-card {{ $isSelected ? 'is-selected' : 'border-gray-300' }}"
                                                data-plan-card for="onboarding-plan-{{ $plan->id }}">
                                                <div class="card-body p-4">
                                                    <div
                                                        class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                                        <div>
                                                            <div class="fw-bold fs-5 text-gray-900">
                                                                {{ $plan->name }}</div>
                                                            <div class="text-muted fs-7">
                                                                @if ($plan->slug === 'free-trial')
                                                                    14-day trial.
                                                                @elseif ($plan->slug === 'starter')
                                                                    Small salons.
                                                                @elseif ($plan->slug === 'professional')
                                                                    Growing salons.
                                                                @else
                                                                    Larger teams.
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="d-flex flex-column align-items-end gap-2">
                                                            <span
                                                                class="badge badge-primary {{ $isSelected ? '' : 'd-none' }}"
                                                                data-selected-badge>
                                                                Selected
                                                            </span>

                                                            @if ($plan->is_recommended)
                                                                <span
                                                                    class="badge badge-light-primary">Recommended</span>
                                                            @elseif ($plan->trial_days > 0)
                                                                <span class="badge badge-light-success">Trial</span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        @if ($plan->price > 0)
                                                            <span class="fw-bold fs-4 text-gray-900">LKR
                                                                {{ number_format((float) $plan->price) }}</span>
                                                            <span class="text-muted fs-8">/month</span>
                                                        @else
                                                            <span class="fw-bold fs-4 text-gray-900">Free</span>
                                                        @endif
                                                    </div>

                                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                                        <span class="badge badge-light">Branches:
                                                            {{ $plan->max_branches ?? 'Unlimited' }}</span>
                                                        <span class="badge badge-light">Staff:
                                                            {{ $plan->max_staff ?? 'Unlimited' }}</span>
                                                        <span class="badge badge-light">Users:
                                                            {{ $plan->max_users ?? 'Unlimited' }}</span>
                                                    </div>

                                                    <div class="d-flex flex-column gap-2 fs-8">
                                                        @foreach ($features as $feature)
                                                            <div class="d-flex align-items-center text-gray-700">
                                                                <i
                                                                    class="bi bi-check-circle-fill text-success me-2"></i>
                                                                {{ $feature }}
                                                            </div>
                                                        @endforeach

                                                        @if (collect($plan->features ?? [])->filter(fn($value) => $value !== false)->count() > $features->count())
                                                            <div class="text-muted fs-8 mt-1">
                                                                More features included
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <div class="alert alert-warning mb-0">
                                                No subscription plans are active. Please seed or create a plan first.
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light d-none" id="onboarding-back">Back</button>
                            <button type="button" class="btn btn-primary" id="onboarding-next">Next</button>
                            <button type="submit" class="btn btn-primary d-none" id="onboarding-submit">Finish
                                setup</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                KTUtil.onDOMContentLoaded(function() {
                    @if (session('status') === 'onboarding-completed')
                        Swal.fire({
                            title: 'Setup complete!',
                            text: 'Your salon is ready. You can now start managing services, staff, appointments, and customers.',
                            icon: 'success',
                            buttonsStyling: false,
                            confirmButtonText: 'Open dashboard',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            }
                        });
                    @endif

                    const onboardingModal = document.getElementById('kt_onboarding_modal');

                    if (onboardingModal) {
                        bootstrap.Modal.getOrCreateInstance(onboardingModal).show();
                    }

                    let currentStep = 1;
                    let serviceIndex = 1;
                    const steps = document.querySelectorAll('[data-onboarding-step]');
                    const maxStep = steps.length;
                    const badges = document.querySelectorAll('[data-step-badge]');
                    const backButton = document.getElementById('onboarding-back');
                    const nextButton = document.getElementById('onboarding-next');
                    const submitButton = document.getElementById('onboarding-submit');
                    const staffButton = document.getElementById('add-first-staff');
                    const staffFields = document.getElementById('first-staff-fields');
                    const servicesWrapper = document.getElementById('onboarding-services');
                    const planInputs = document.querySelectorAll('input[name="plan_id"]');

                    const getCurrentStepElement = function() {
                        return document.querySelector(`[data-onboarding-step="${currentStep}"]`);
                    };

                    const refreshPlanSelection = function() {
                        planInputs.forEach((input) => {
                            const card = document.querySelector(`label[for="${input.id}"]`);

                            if (!card) {
                                return;
                            }

                            card.classList.toggle('is-selected', input.checked);
                            card.classList.toggle('border-gray-300', !input.checked);

                            const selectedBadge = card.querySelector('[data-selected-badge]');

                            if (selectedBadge) {
                                selectedBadge.classList.toggle('d-none', !input.checked);
                            }
                        });
                    };

                    const isCurrentStepValid = function() {
                        const stepElement = getCurrentStepElement();

                        if (!stepElement) {
                            return false;
                        }

                        if (stepElement.querySelector('[data-plan-options]')) {
                            return stepElement.querySelector('input[name="plan_id"]:checked') !== null;
                        }

                        const requiredFields = stepElement.querySelectorAll(
                            'input[required], select[required], textarea[required]');

                        return Array.from(requiredFields).every((field) => {
                            if (field.disabled || field.closest('.d-none')) {
                                return true;
                            }

                            if (field.type === 'radio') {
                                return stepElement.querySelector(
                                    `input[type="radio"][name="${field.name}"]:checked`) !== null;
                            }

                            if (field.type === 'checkbox') {
                                return field.checked;
                            }

                            return field.checkValidity() && field.value.trim() !== '';
                        });
                    };

                    const updateActionButtons = function() {
                        const isValid = isCurrentStepValid();

                        nextButton.disabled = currentStep !== maxStep && !isValid;
                        submitButton.disabled = currentStep === maxStep && !isValid;
                    };

                    const showStep = function(step) {
                        steps.forEach((element) => {
                            element.classList.toggle('d-none', element.getAttribute('data-onboarding-step') !==
                                String(step));
                        });

                        badges.forEach((badge) => {
                            const isActive = Number(badge.getAttribute('data-step-badge')) === step;
                            badge.classList.toggle('badge-primary', isActive);
                            badge.classList.toggle('badge-light-primary', !isActive);
                        });

                        backButton.classList.toggle('d-none', step === 1);
                        nextButton.classList.toggle('d-none', step === maxStep);
                        submitButton.classList.toggle('d-none', step !== maxStep);
                        refreshPlanSelection();
                        updateActionButtons();
                    };

                    nextButton.addEventListener('click', function() {
                        if (!isCurrentStepValid()) {
                            updateActionButtons();
                            return;
                        }

                        currentStep = Math.min(currentStep + 1, maxStep);
                        showStep(currentStep);
                    });

                    backButton.addEventListener('click', function() {
                        currentStep = Math.max(currentStep - 1, 1);
                        showStep(currentStep);
                    });

                    document.getElementById('add-onboarding-service').addEventListener('click', function() {
                        const row = document.createElement('div');
                        row.className = 'row g-3 onboarding-service-row';
                        row.innerHTML = `
                            <div class="col-md-4">
                                <label class="form-label required">Service name</label>
                                <input type="text" name="services[${serviceIndex}][name]" class="form-control" placeholder="Service name" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Category</label>
                                <input type="text" name="services[${serviceIndex}][category]" class="form-control" placeholder="Category" value="General" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label required">Duration</label>
                                <input type="number" name="services[${serviceIndex}][duration_minutes]" class="form-control" placeholder="Minutes" value="60" min="5" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label required">Price</label>
                                <input type="number" name="services[${serviceIndex}][price]" class="form-control" placeholder="Price" value="0" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-icon btn-light-danger btn-sm" data-remove-service title="Delete service">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        `;
                        servicesWrapper.appendChild(row);
                        serviceIndex++;
                        updateActionButtons();
                    });

                    servicesWrapper.addEventListener('click', function(event) {
                        const deleteButton = event.target.closest('[data-remove-service]');

                        if (deleteButton) {
                            deleteButton.closest('.onboarding-service-row').remove();
                            updateActionButtons();
                        }
                    });

                    staffButton.addEventListener('click', function() {
                        staffFields.classList.remove('d-none');
                        staffButton.classList.add('d-none');
                        updateActionButtons();
                    });

                    planInputs.forEach((input) => {
                        input.addEventListener('change', function() {
                            refreshPlanSelection();
                            updateActionButtons();
                        });
                    });

                    document.getElementById('kt_onboarding_modal').addEventListener('input', updateActionButtons);
                    document.getElementById('kt_onboarding_modal').addEventListener('change', function() {
                        refreshPlanSelection();
                        updateActionButtons();
                    });

                    showStep(currentStep);
                });
            </script>
        @endpush
    @endif

</x-default-layout>
