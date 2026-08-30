@php
    $isEdit = $staffMember->exists;

    $selectedBranchIds = old('branches')
        ? collect(old('branches'))->filter(fn($item) => !empty($item['enabled']))->keys()->map(fn($id) => (int) $id)
        : $staffMember->branches->pluck('id');

    $primaryBranchId = old(
        'primary_branch_id',
        $staffMember->branches->first(fn($branch) => (bool) $branch->pivot?->is_primary)?->id,
    );

    $selectedServiceIds = old('services')
        ? collect(old('services'))->filter(fn($item) => !empty($item['enabled']))->keys()->map(fn($id) => (int) $id)
        : $staffMember->services->pluck('id');

    $existingSchedules = collect(
        old(
            'schedules',
            $staffMember->schedules
                ->map(
                    fn($schedule) => [
                        'branch_id' => $schedule->branch_id,
                        'day_of_week' => $schedule->day_of_week,
                        'is_working' => $schedule->is_working,
                        'start_time' =>
                            optional($schedule->start_time)->format('H:i') ?: $schedule->getRawOriginal('start_time'),
                        'end_time' =>
                            optional($schedule->end_time)->format('H:i') ?: $schedule->getRawOriginal('end_time'),
                        'title' => $schedule->title,
                    ],
                )
                ->toArray(),
        ),
    );

    $existingBreaks = collect(
        old(
            'breaks',
            $staffMember->breaks
                ->map(
                    fn($break) => [
                        'branch_id' => $break->branch_id,
                        'day_of_week' => $break->day_of_week,
                        'start_time' =>
                            optional($break->start_time)->format('H:i') ?: $break->getRawOriginal('start_time'),
                        'end_time' => optional($break->end_time)->format('H:i') ?: $break->getRawOriginal('end_time'),
                        'title' => $break->title,
                    ],
                )
                ->toArray(),
        ),
    );

    $existingTimeOff = collect(
        old(
            'time_off',
            $staffMember->timeOff
                ->map(
                    fn($item) => [
                        'branch_id' => $item->branch_id,
                        'start_datetime' => optional($item->start_datetime)->format('Y-m-d\TH:i'),
                        'end_datetime' => optional($item->end_datetime)->format('Y-m-d\TH:i'),
                        'type' => $item->type,
                        'status' => $item->status,
                        'reason' => $item->reason,
                    ],
                )
                ->toArray(),
        ),
    );

    $existingCommissionSettings = collect(
        old(
            'commission_settings',
            $staffMember->commissionSettings
                ->map(
                    fn($setting) => [
                        'service_id' => $setting->service_id,
                        'commission_type' => $setting->commission_type,
                        'commission_value' => $setting->commission_value,
                    ],
                )
                ->toArray(),
        ),
    );
@endphp


{{-- ================================================================ --}}
{{-- VALIDATION --}}
{{-- ================================================================ --}}
@if ($errors->any())

    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start mb-8">

        <div class="symbol symbol-45px me-4 flex-shrink-0">
            <div class="symbol-label bg-light-danger">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
            </div>
        </div>

        <div>
            <div class="fw-bold text-gray-900 mb-1">
                We couldn't save this staff member
            </div>

            <div class="text-gray-700">
                {{ $errors->first() }}
            </div>

            @if ($errors->count() > 1)
                <div class="text-muted fs-8 mt-1">
                    Please review the highlighted fields before continuing.
                </div>
            @endif
        </div>

    </div>

@endif


{{-- ================================================================ --}}
{{-- HERO --}}
{{-- ================================================================ --}}
<div class="card border-0 shadow-sm mb-5">

    <div class="card-body p-4">

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-6">

            <div class="d-flex align-items-start">

                <div class="symbol symbol-50px me-5 flex-shrink-0">
                    <div class="symbol-label bg-light-primary rounded-4">
                        <i class="bi bi-person-badge-fill text-primary fs-1"></i>
                    </div>
                </div>

                <div>

                    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">

                        <h3 class="fw-bolder text-gray-900 mb-0">
                            {{ $isEdit ? 'Update Staff Profile' : 'Create Staff Profile' }}
                        </h3>

                        <span class="badge badge-light-primary px-3 py-2">
                            {{ $isEdit ? 'Existing Employee' : 'New Employee' }}
                        </span>

                    </div>

                    <div class="text-muted fs-7">
                        Manage identity, workplace assignments, services, schedule,
                        booking availability and commission settings.
                    </div>

                </div>

            </div>

            <div class="d-flex flex-wrap gap-3">

                <span class="badge badge-light-info px-4 py-3">
                    <i class="bi bi-buildings me-2"></i>
                    {{ number_format($branches->count()) }} Branches
                </span>

                <span class="badge badge-light-success px-4 py-3">
                    <i class="bi bi-scissors me-2"></i>
                    {{ number_format($services->count()) }} Services
                </span>

            </div>

        </div>

    </div>

</div>


<div class="row g-8">

    {{-- ============================================================ --}}
    {{-- MAIN CONTENT --}}
    {{-- ============================================================ --}}
    <div class="col-xl-8">


        {{-- ======================================================== --}}
        {{-- PERSONAL / EMPLOYMENT INFORMATION --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-person-vcard text-primary fs-3"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Staff Information
                        </h3>

                        <div class="text-muted fs-8">
                            Personal, contact and employment details.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                <div class="row g-6">

                    {{-- Tenant --}}
                    @if (($tenants ?? collect())->isNotEmpty() && !$isEdit)

                        <div class="col-12">

                            <div class="rounded-4 bg-light-info p-5">

                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="symbol symbol-45px me-4">
                                            <div class="symbol-label bg-white">
                                                <i class="bi bi-shop text-info fs-3"></i>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="fw-bold text-gray-900 mb-1">
                                                Staff Owner
                                            </div>

                                            <div class="text-muted fs-8">
                                                Select the salon before configuring branches and services.
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <select name="tenant_id" id="staff_tenant_id" data-control="select2"
                                            data-hide-search="true"
                                            class="w-400px form-select bg-white @error('tenant_id') is-invalid @enderror"
                                            required>
                                            <option value="">
                                                Select salon
                                            </option>
                                            @foreach ($tenants as $tenant)
                                                <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenant?->id) === (string) $tenant->id)>
                                                    {{ $tenant->name }}
                                                    {{ $tenant->email ? ' — ' . $tenant->email : '' }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('tenant_id')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                        </div>

                    @endif


                    {{-- Employee Code --}}
                    <div class="col-md-4">

                        <label class="form-label required fw-semibold">
                            Employee Code
                        </label>

                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-upc-scan text-muted"></i>
                            </span>

                            <input type="text" name="employee_code"
                                value="{{ old('employee_code', $staffMember->employee_code) }}"
                                class="form-control @error('employee_code') is-invalid @enderror" placeholder="STF-0001"
                                required>

                            @error('employee_code')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- First Name --}}
                    <div class="col-md-4">

                        <label class="form-label required fw-semibold">
                            First Name
                        </label>

                        <input type="text" name="first_name" id="staff_first_name"
                            value="{{ old('first_name', $staffMember->first_name) }}"
                            class="form-control @error('first_name') is-invalid @enderror" placeholder="Nadeesha"
                            required>

                        @error('first_name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Last Name --}}
                    <div class="col-md-4">

                        <label class="form-label required fw-semibold">
                            Last Name
                        </label>

                        <input type="text" name="last_name" id="staff_last_name"
                            value="{{ old('last_name', $staffMember->last_name) }}"
                            class="form-control @error('last_name') is-invalid @enderror" placeholder="Perera" required>

                        @error('last_name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Email --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Email Address
                        </label>

                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-envelope text-muted"></i>
                            </span>

                            <input type="email" name="email" value="{{ old('email', $staffMember->email) }}"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="staff@example.com">

                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- Phone --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Phone Number
                        </label>

                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-telephone text-muted"></i>
                            </span>

                            <input type="text" name="phone" value="{{ old('phone', $staffMember->phone) }}"
                                class="form-control @error('phone') is-invalid @enderror"
                                placeholder="+94 77 123 4567">

                            @error('phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- Gender --}}
                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Gender
                        </label>

                        <select name="gender" class="form-select" data-control="select2" data-hide-search="true">

                            <option value="">
                                Not specified
                            </option>

                            @foreach ($genders as $gender)
                                <option value="{{ $gender }}" @selected(old('gender', $staffMember->gender) === $gender)>
                                    {{ str($gender)->replace('_', ' ')->headline() }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    {{-- DOB --}}
                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Date of Birth
                        </label>

                        <input type="date" name="date_of_birth"
                            value="{{ old('date_of_birth', optional($staffMember->date_of_birth)->format('Y-m-d')) }}"
                            class="form-control">

                    </div>


                    {{-- Hire Date --}}
                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Hire Date
                        </label>

                        <input type="date" name="hire_date"
                            value="{{ old('hire_date', optional($staffMember->hire_date)->format('Y-m-d')) }}"
                            class="form-control">

                    </div>


                    {{-- Job Title --}}
                    <div class="col-md-6">

                        <label class="form-label required fw-semibold">
                            Job Title
                        </label>

                        <div class="input-group">

                            <span class="input-group-text border-0 bg-light">
                                <i class="bi bi-briefcase text-muted"></i>
                            </span>

                            <input type="text" name="job_title" id="staff_job_title"
                                value="{{ old('job_title', $staffMember->job_title) }}"
                                class="form-control @error('job_title') is-invalid @enderror"
                                placeholder="Senior Stylist" required>

                            @error('job_title')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    {{-- User --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Linked User Account
                        </label>

                        <select name="user_id" class="form-select" data-control="select2" data-hide-search="true">

                            <option value="">
                                No system login
                            </option>

                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((string) old('user_id', $staffMember->user_id) === (string) $user->id)>
                                    {{ $user->name }} — {{ $user->email }}
                                </option>
                            @endforeach

                        </select>

                        <div class="text-muted fs-8 mt-2">
                            Link an account only when this employee requires dashboard access.
                        </div>

                    </div>


                    {{-- Bio --}}
                    <div class="col-12">

                        <label class="form-label fw-semibold">
                            Bio / Internal Notes
                        </label>

                        <textarea name="bio" rows="4" class="form-control form-control-solid"
                            placeholder="Experience, specialization or internal notes...">{{ old('bio', $staffMember->bio) }}</textarea>

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- BRANCH ASSIGNMENT --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-buildings text-info fs-3"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Branch Assignment
                        </h3>

                        <div class="text-muted fs-8">
                            Select the locations where this employee can work.
                        </div>
                    </div>

                </div>

                @if ($branches->isNotEmpty())
                    <div class="card-toolbar">
                        <span class="badge badge-light-info px-3 py-2">
                            {{ $selectedBranchIds->count() }}
                            Selected
                        </span>
                    </div>
                @endif

            </div>


            <div class="card-body pt-4">

                <div class="row g-5">

                    @forelse ($branches as $branch)
                        @php
                            $isChecked = $selectedBranchIds->contains($branch->id);
                            $pivot = $staffMember->branches->firstWhere('id', $branch->id)?->pivot;
                        @endphp

                        <div class="col-md-6">

                            <div class="rounded-4 border {{ $isChecked ? 'border-primary bg-light-primary' : 'border-gray-300' }} p-5 h-100"
                                data-branch-card>

                                <input type="hidden" name="branches[{{ $branch->id }}][enabled]" value="0">

                                <div class="d-flex align-items-start justify-content-between gap-4 mb-5">

                                    <div class="d-flex align-items-center">

                                        <div class="symbol symbol-45px me-4">
                                            <div class="symbol-label bg-white">
                                                <i class="bi bi-building text-primary fs-3"></i>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="fw-bold text-gray-900">
                                                {{ $branch->name }}
                                            </div>

                                            <div class="text-muted fs-8">
                                                Staff work location
                                            </div>
                                        </div>

                                    </div>

                                    <label class="form-check form-switch form-check-custom form-check-solid">

                                        <input class="form-check-input branch-enabled" type="checkbox"
                                            name="branches[{{ $branch->id }}][enabled]" value="1"
                                            @checked($isChecked)>

                                    </label>

                                </div>


                                <div class="separator separator-dashed mb-5"></div>


                                <div class="row g-4 align-items-end">

                                    <div class="col-sm-6">

                                        <label class="form-label fs-8 fw-semibold">
                                            Primary Location
                                        </label>

                                        <label class="form-check form-check-custom form-check-solid">

                                            <input class="form-check-input" type="radio" name="primary_branch_id"
                                                value="{{ $branch->id }}" @checked((string) $primaryBranchId === (string) $branch->id)>

                                            <span class="form-check-label fw-semibold fs-8">
                                                Set as primary
                                            </span>

                                        </label>

                                    </div>


                                    <div class="col-sm-6">

                                        <label class="form-label fs-8 fw-semibold">
                                            Assignment Status
                                        </label>

                                        <select name="branches[{{ $branch->id }}][status]" data-control="select2"
                                            data-hide-search="true" class="form-select form-select-sm">

                                            <option value="active" @selected(old("branches.{$branch->id}.status", $pivot?->status ?? 'active') === 'active')>
                                                Active
                                            </option>

                                            <option value="inactive" @selected(old("branches.{$branch->id}.status", $pivot?->status) === 'inactive')>
                                                Inactive
                                            </option>

                                        </select>

                                    </div>

                                </div>

                            </div>

                        </div>

                    @empty

                        <div class="col-12">

                            <div class="text-center py-12">

                                <div class="symbol symbol-80px mb-5">
                                    <div class="symbol-label bg-light-warning rounded-circle">
                                        <i class="bi bi-building-exclamation text-warning fs-1"></i>
                                    </div>
                                </div>

                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No branches available
                                </h4>

                                <div class="text-muted">
                                    Create an active branch before assigning staff.
                                </div>

                            </div>

                        </div>
                    @endforelse

                </div>


                @error('branches')
                    <div class="text-danger fs-7 mt-4">
                        {{ $message }}
                    </div>
                @enderror

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- SERVICE ASSIGNMENT --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-success">
                            <i class="bi bi-scissors text-success fs-3"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Service Assignment
                        </h3>

                        <div class="text-muted fs-8">
                            Choose the services this employee can perform.
                        </div>
                    </div>

                </div>

                @if ($services->isNotEmpty())
                    <div class="card-toolbar">
                        <span class="badge badge-light-success px-3 py-2">
                            {{ $selectedServiceIds->count() }}
                            Selected
                        </span>
                    </div>
                @endif

            </div>


            <div class="card-body pt-4">

                <div class="row g-5">

                    @forelse ($services as $service)
                        @php
                            $pivot = $staffMember->services->firstWhere('id', $service->id)?->pivot;

                            $serviceSelected = $selectedServiceIds->contains($service->id);
                        @endphp

                        <div class="col-md-6">

                            <div class="rounded-4 border {{ $serviceSelected ? 'border-success bg-light-success' : 'border-gray-300' }} p-5 h-100"
                                data-service-card>

                                <input type="hidden" name="services[{{ $service->id }}][enabled]" value="0">


                                <div class="d-flex justify-content-between align-items-start gap-4 mb-5">

                                    <div class="d-flex align-items-center">

                                        <div class="symbol symbol-45px me-4">
                                            <div class="symbol-label bg-white">
                                                <i class="bi bi-stars text-success fs-3"></i>
                                            </div>
                                        </div>

                                        <div>

                                            <div class="fw-bold text-gray-900">
                                                {{ $service->name }}
                                            </div>

                                            <div class="text-muted fs-8">
                                                {{ $service->category?->name ?? 'Uncategorized' }}
                                            </div>

                                        </div>

                                    </div>


                                    <label class="form-check form-switch form-check-custom form-check-solid">

                                        <input class="form-check-input service-enabled" type="checkbox"
                                            name="services[{{ $service->id }}][enabled]" value="1"
                                            @checked($serviceSelected)>

                                    </label>

                                </div>


                                <div class="separator separator-dashed mb-5"></div>


                                <div class="row g-4">

                                    <div class="col-sm-6">

                                        <label class="form-label fs-8 fw-semibold">
                                            Duration Override
                                        </label>

                                        <div class="input-group input-group-sm">

                                            <input type="number"
                                                name="services[{{ $service->id }}][custom_duration_minutes]"
                                                value="{{ old("services.{$service->id}.custom_duration_minutes", $pivot?->custom_duration_minutes) }}"
                                                min="5" max="1440" class="form-control"
                                                placeholder="{{ $service->default_duration_minutes ?? $service->duration_minutes }}">

                                            <span class="input-group-text">
                                                min
                                            </span>

                                        </div>

                                    </div>


                                    <div class="col-sm-6">

                                        <label class="form-label fs-8 fw-semibold">
                                            Price Override
                                        </label>

                                        <div class="input-group input-group-sm">

                                            <span class="input-group-text">
                                                LKR
                                            </span>

                                            <input type="number" name="services[{{ $service->id }}][custom_price]"
                                                value="{{ old("services.{$service->id}.custom_price", $pivot?->custom_price) }}"
                                                min="0" step="0.01" class="form-control"
                                                placeholder="{{ $service->default_price ?? $service->price }}">

                                        </div>

                                    </div>


                                    <div class="col-12">

                                        <label class="form-label fs-8 fw-semibold">
                                            Assignment Status
                                        </label>

                                        <select name="services[{{ $service->id }}][status]" data-control="select2"
                                            data-hide-search="true" class="form-select form-select-sm">

                                            <option value="active" @selected(old("services.{$service->id}.status", $pivot?->status ?? 'active') === 'active')>
                                                Active
                                            </option>

                                            <option value="inactive" @selected(old("services.{$service->id}.status", $pivot?->status) === 'inactive')>
                                                Inactive
                                            </option>

                                        </select>

                                    </div>

                                </div>

                            </div>

                        </div>

                    @empty

                        <div class="col-12">

                            <div class="text-center py-12">

                                <div class="symbol symbol-80px mb-5">
                                    <div class="symbol-label bg-light-warning rounded-circle">
                                        <i class="bi bi-scissors text-warning fs-1"></i>
                                    </div>
                                </div>

                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No services available
                                </h4>

                                <div class="text-muted">
                                    Create active services before assigning them to staff.
                                </div>

                            </div>

                        </div>
                    @endforelse

                </div>


                @error('services')
                    <div class="text-danger fs-7 mt-4">
                        {{ $message }}
                    </div>
                @enderror

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- WEEKLY SCHEDULE --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-warning">
                            <i class="bi bi-calendar-week text-warning fs-3"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Weekly Working Schedule
                        </h3>

                        <div class="text-muted fs-8">
                            Define the employee's regular working hours by branch.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                <div class="table-responsive">

                    <table class="table align-middle table-row-dashed gy-5">

                        <thead>
                            <tr class="fw-bold text-muted fs-8 text-uppercase">
                                <th class="min-w-100px">Working</th>
                                <th class="min-w-130px">Day</th>
                                <th class="min-w-200px">Branch</th>
                                <th class="min-w-140px">Start</th>
                                <th class="min-w-140px">End</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($days as $dayNumber => $dayName)
                                @php
                                    $schedule = $existingSchedules->firstWhere('day_of_week', $dayNumber) ?? [
                                        'branch_id' => $primaryBranchId,
                                        'day_of_week' => $dayNumber,
                                        'is_working' => !in_array($dayNumber, [7], true),
                                        'start_time' => '09:00',
                                        'end_time' => $dayNumber === 6 ? '20:00' : '17:00',
                                    ];
                                @endphp

                                <tr>

                                    <td>

                                        <input type="hidden" name="schedules[{{ $dayNumber }}][day_of_week]"
                                            value="{{ $dayNumber }}">

                                        <input type="hidden" name="schedules[{{ $dayNumber }}][is_working]"
                                            value="0">

                                        <label class="form-check form-switch form-check-custom form-check-solid">

                                            <input class="form-check-input" type="checkbox"
                                                name="schedules[{{ $dayNumber }}][is_working]" value="1"
                                                @checked(!empty($schedule['is_working']))>

                                        </label>

                                    </td>


                                    <td>
                                        <div class="fw-bold text-gray-900">
                                            {{ $dayName }}
                                        </div>
                                    </td>


                                    <td>

                                        <select name="schedules[{{ $dayNumber }}][branch_id]"
                                            data-control="select2" data-hide-search="true"
                                            class="form-select form-select-sm">

                                            <option value="">
                                                Select branch
                                            </option>

                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}" @selected((string) ($schedule['branch_id'] ?? '') === (string) $branch->id)>
                                                    {{ $branch->name }}
                                                </option>
                                            @endforeach

                                        </select>

                                    </td>


                                    <td>

                                        <input type="time" name="schedules[{{ $dayNumber }}][start_time]"
                                            value="{{ $schedule['start_time'] ?? '09:00' }}"
                                            class="form-control form-control-sm">

                                    </td>


                                    <td>

                                        <input type="time" name="schedules[{{ $dayNumber }}][end_time]"
                                            value="{{ $schedule['end_time'] ?? '17:00' }}"
                                            class="form-control form-control-sm">

                                    </td>

                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- BREAKS AND TIME OFF --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-danger">
                            <i class="bi bi-clock-history text-danger fs-3"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Breaks & Time Off
                        </h3>

                        <div class="text-muted fs-8">
                            Configure recurring breaks and temporary unavailability.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                {{-- Recurring Breaks --}}
                <div class="mb-10">

                    <div class="d-flex justify-content-between align-items-center mb-5">

                        <div>
                            <h4 class="fw-bold text-gray-900 mb-1">
                                Recurring Breaks
                            </h4>

                            <div class="text-muted fs-8">
                                Weekly lunch or other non-bookable periods.
                            </div>
                        </div>

                        <span class="badge badge-light-warning px-3 py-2">
                            <i class="bi bi-arrow-repeat me-1"></i>
                            Weekly
                        </span>

                    </div>


                    @for ($i = 0; $i < max(1, $existingBreaks->count()); $i++)

                        @php($break = $existingBreaks->get($i, []))

                        <div class="rounded-4 bg-light p-5 mb-4">

                            <div class="row g-4 align-items-end">

                                <div class="col-md-3">

                                    <label class="form-label fs-8 fw-semibold">
                                        Branch
                                    </label>

                                    <select name="breaks[{{ $i }}][branch_id]" data-control="select2"
                                        data-hide-search="true" class="form-select form-select-sm bg-white">

                                        <option value="">
                                            None
                                        </option>

                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected((string) ($break['branch_id'] ?? '') === (string) $branch->id)>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach

                                    </select>

                                </div>


                                <div class="col-md-2">

                                    <label class="form-label fs-8 fw-semibold">
                                        Day
                                    </label>

                                    <select name="breaks[{{ $i }}][day_of_week]" data-control="select2"
                                        data-hide-search="true" class="form-select form-select-sm bg-white">

                                        <option value="">
                                            Day
                                        </option>

                                        @foreach ($days as $dayNumber => $dayName)
                                            <option value="{{ $dayNumber }}" @selected((string) ($break['day_of_week'] ?? '') === (string) $dayNumber)>
                                                {{ $dayName }}
                                            </option>
                                        @endforeach

                                    </select>

                                </div>


                                <div class="col-md-2">

                                    <label class="form-label fs-8 fw-semibold">
                                        Start
                                    </label>

                                    <input type="time" name="breaks[{{ $i }}][start_time]"
                                        value="{{ $break['start_time'] ?? '' }}"
                                        class="form-control form-control-sm bg-white">

                                </div>


                                <div class="col-md-2">

                                    <label class="form-label fs-8 fw-semibold">
                                        End
                                    </label>

                                    <input type="time" name="breaks[{{ $i }}][end_time]"
                                        value="{{ $break['end_time'] ?? '' }}"
                                        class="form-control form-control-sm bg-white">

                                </div>


                                <div class="col-md-3">

                                    <label class="form-label fs-8 fw-semibold">
                                        Title
                                    </label>

                                    <input type="text" name="breaks[{{ $i }}][title]"
                                        value="{{ $break['title'] ?? '' }}"
                                        class="form-control form-control-sm bg-white" placeholder="Lunch Break">

                                </div>

                            </div>

                        </div>

                    @endfor

                </div>


                <div class="separator separator-dashed my-8"></div>


                {{-- Time Off --}}
                <div>

                    <div class="d-flex justify-content-between align-items-center mb-5">

                        <div>
                            <h4 class="fw-bold text-gray-900 mb-1">
                                Time Off
                            </h4>

                            <div class="text-muted fs-8">
                                Leave, training or temporary unavailability.
                            </div>
                        </div>

                        <span class="badge badge-light-danger px-3 py-2">
                            <i class="bi bi-calendar-x me-1"></i>
                            Exceptions
                        </span>

                    </div>


                    @for ($i = 0; $i < max(1, $existingTimeOff->count()); $i++)

                        @php($item = $existingTimeOff->get($i, []))

                        <div class="rounded-4 bg-light p-5 mb-4">

                            <div class="row g-4">

                                <div class="col-md-3">

                                    <label class="form-label fs-8 fw-semibold">
                                        Type
                                    </label>

                                    <select name="time_off[{{ $i }}][type]" data-control="select2"
                                        data-hide-search="true" class="form-select form-select-sm bg-white">

                                        @foreach ($timeOffTypes as $type)
                                            <option value="{{ $type }}" @selected(($item['type'] ?? 'unavailable') === $type)>
                                                {{ str($type)->replace('_', ' ')->headline() }}
                                            </option>
                                        @endforeach

                                    </select>

                                </div>


                                <div class="col-md-3">

                                    <label class="form-label fs-8 fw-semibold">
                                        Branch
                                    </label>

                                    <select name="time_off[{{ $i }}][branch_id]" data-control="select2"
                                        data-hide-search="true" class="form-select form-select-sm bg-white">

                                        <option value="">
                                            All branches
                                        </option>

                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected((string) ($item['branch_id'] ?? '') === (string) $branch->id)>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach

                                    </select>

                                </div>


                                <div class="col-md-3">

                                    <label class="form-label fs-8 fw-semibold">
                                        From
                                    </label>

                                    <input type="datetime-local" name="time_off[{{ $i }}][start_datetime]"
                                        value="{{ $item['start_datetime'] ?? '' }}"
                                        class="form-control form-control-sm bg-white">

                                </div>


                                <div class="col-md-3">

                                    <label class="form-label fs-8 fw-semibold">
                                        Until
                                    </label>

                                    <input type="datetime-local" name="time_off[{{ $i }}][end_datetime]"
                                        value="{{ $item['end_datetime'] ?? '' }}"
                                        class="form-control form-control-sm bg-white">

                                </div>


                                <div class="col-md-9">

                                    <label class="form-label fs-8 fw-semibold">
                                        Reason
                                    </label>

                                    <input type="text" name="time_off[{{ $i }}][reason]"
                                        value="{{ $item['reason'] ?? '' }}"
                                        class="form-control form-control-sm bg-white"
                                        placeholder="Annual leave, training, personal leave...">

                                </div>


                                <div class="col-md-3">

                                    <label class="form-label fs-8 fw-semibold">
                                        Status
                                    </label>

                                    <select name="time_off[{{ $i }}][status]" data-control="select2"
                                        data-hide-search="true" class="form-select form-select-sm bg-white">

                                        @foreach (['approved', 'pending', 'rejected', 'cancelled'] as $status)
                                            <option value="{{ $status }}" @selected(($item['status'] ?? 'approved') === $status)>
                                                {{ str($status)->headline() }}
                                            </option>
                                        @endforeach

                                    </select>

                                </div>

                            </div>

                        </div>

                    @endfor

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================ --}}
    {{-- SIDEBAR --}}
    {{-- ============================================================ --}}
    <div class="col-xl-4">


        {{-- Staff Preview --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-body text-center p-7">

                <div class="symbol symbol-85px symbol-circle mb-5">
                    <div class="symbol-label bg-light-primary">
                        <i class="bi bi-person-fill text-primary fs-1"></i>
                    </div>
                </div>

                <h3 id="staff_preview_name" class="fw-bold text-gray-900 mb-1">
                    {{ trim(
                        old('first_name', $staffMember->first_name ?: 'New') .
                            ' ' .
                            old('last_name', $staffMember->last_name ?: 'Staff Member'),
                    ) }}
                </h3>

                <div id="staff_preview_role" class="text-muted mb-4">
                    {{ old('job_title', $staffMember->job_title ?: 'Staff profile') }}
                </div>

                <span class="badge badge-light-primary px-3 py-2">
                    <i class="bi bi-person-badge me-1"></i>
                    Staff Member
                </span>

            </div>

        </div>


        {{-- Booking Settings --}}
        <div class="card border-0 shadow-sm mb-8">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-sliders text-primary"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Booking & Settings
                        </h3>

                        <div class="text-muted fs-8">
                            Staff availability and account behavior.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                {{-- Status --}}
                <div class="mb-7">

                    <label class="form-label required fw-semibold">
                        Employment Status
                    </label>

                    <select name="status" class="form-select form-select-solid" required data-control="select2"
                        data-hide-search="true">

                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $staffMember->status ?? 'active') === $status)>
                                {{ str($status)->replace('_', ' ')->headline() }}
                            </option>
                        @endforeach

                    </select>

                </div>


                {{-- Bookable --}}
                <label
                    class="d-flex justify-content-between align-items-center rounded-4 border border-gray-300 p-5 mb-4">

                    <div class="d-flex align-items-center">

                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-calendar-check text-success"></i>
                            </div>
                        </div>

                        <div>

                            <div class="fw-bold text-gray-900 mb-1">
                                Available for Booking
                            </div>

                            <div class="text-muted fs-8">
                                Allow appointment allocation.
                            </div>

                        </div>

                    </div>

                    <div class="form-check form-switch form-check-custom form-check-solid">

                        <input type="hidden" name="is_bookable" value="0">

                        <input class="form-check-input" type="checkbox" name="is_bookable" value="1"
                            @checked(old('is_bookable', $staffMember->is_bookable ?? true))>

                    </div>

                </label>


                {{-- Online --}}
                <label class="d-flex justify-content-between align-items-center rounded-4 border border-gray-300 p-5">

                    <div class="d-flex align-items-center">

                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-info">
                                <i class="bi bi-globe text-info"></i>
                            </div>
                        </div>

                        <div>

                            <div class="fw-bold text-gray-900 mb-1">
                                Public Booking Profile
                            </div>

                            <div class="text-muted fs-8">
                                Show this employee online.
                            </div>

                        </div>

                    </div>

                    <div class="form-check form-switch form-check-custom form-check-solid">

                        <input type="hidden" name="show_online" value="0">

                        <input class="form-check-input" type="checkbox" name="show_online" value="1"
                            @checked(old('show_online', $staffMember->show_online ?? false))>

                    </div>

                </label>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- COMMISSION --}}
        {{-- ======================================================== --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header border-0 pt-8">

                <div class="card-title">

                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-success">
                            <i class="bi bi-percent text-success"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Commission Settings
                        </h3>

                        <div class="text-muted fs-8">
                            Configure default and service-specific commissions.
                        </div>
                    </div>

                </div>

            </div>


            <div class="card-body pt-4">

                <div class="rounded-4 bg-light-success p-5 mb-7">

                    <div class="fw-bold text-gray-900 mb-1">
                        Default Commission
                    </div>

                    <div class="text-muted fs-8 mb-5">
                        Applied when a service does not have an override.
                    </div>


                    <div class="row g-4">

                        <div class="col-6">

                            <label class="form-label fs-8 fw-semibold">
                                Type
                            </label>

                            <select name="commission_type" class="form-select bg-white" data-control="select2"
                                data-hide-search="true">

                                @foreach ($commissionTypes as $type)
                                    <option value="{{ $type }}" @selected(old('commission_type', $staffMember->commission_type ?? 'percentage') === $type)>
                                        {{ str($type)->headline() }}
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        <div class="col-6">

                            <label class="form-label fs-8 fw-semibold">
                                Value
                            </label>

                            <input type="number" name="commission_value"
                                value="{{ old('commission_value', $staffMember->commission_value ?? 0) }}"
                                min="0" step="0.01" class="form-control bg-white">

                        </div>

                    </div>

                </div>


                {{-- Service Overrides --}}
                <div class="d-flex justify-content-between align-items-center mb-5">

                    <div>
                        <div class="fw-bold text-gray-900">
                            Service Overrides
                        </div>

                        <div class="text-muted fs-8">
                            Optional special commission rules.
                        </div>
                    </div>

                    <span class="badge badge-light-primary">
                        Optional
                    </span>

                </div>


                @for ($i = 0; $i < max(1, $existingCommissionSettings->count()); $i++)

                    @php($setting = $existingCommissionSettings->get($i, []))

                    <div class="rounded-4 border border-gray-300 p-4 mb-4">

                        <div class="mb-4">

                            <label class="form-label fs-8 fw-semibold">
                                Service
                            </label>

                            <select name="commission_settings[{{ $i }}][service_id]"
                                data-control="select2" data-hide-search="true" class="form-select form-select-sm">

                                <option value="">
                                    Default / All services
                                </option>

                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}" @selected((string) ($setting['service_id'] ?? '') === (string) $service->id)>
                                        {{ $service->name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        <div class="row g-3">

                            <div class="col-6">

                                <label class="form-label fs-8">
                                    Type
                                </label>

                                <select name="commission_settings[{{ $i }}][commission_type]"
                                    data-control="select2" data-hide-search="true"
                                    class="form-select form-select-sm">

                                    @foreach ($commissionTypes as $type)
                                        <option value="{{ $type }}" @selected(($setting['commission_type'] ?? 'percentage') === $type)>
                                            {{ str($type)->headline() }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            <div class="col-6">

                                <label class="form-label fs-8">
                                    Value
                                </label>

                                <input type="number"
                                    name="commission_settings[{{ $i }}][commission_value]"
                                    value="{{ $setting['commission_value'] ?? '' }}" min="0" step="0.01"
                                    class="form-control form-control-sm" placeholder="Value">

                            </div>

                        </div>

                    </div>

                @endfor

            </div>


            {{-- Actions --}}
            <div class="card-footer border-0 pt-2 pb-7">

                <div class="d-grid gap-3">

                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check2-circle me-2"></i>

                        {{ $isEdit ? 'Save Staff Changes' : 'Create Staff Member' }}
                    </button>


                    <a href="{{ $isEdit ? route('staff-management.staff.show', $staffMember) : route('staff-management.staff.index') }}"
                        class="btn btn-light">
                        <i class="bi bi-x-lg me-2"></i>
                        Cancel
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- ================================================================ --}}
{{-- SCRIPTS --}}
{{-- ================================================================ --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /*
            |--------------------------------------------------------------------------
            | Tenant Selection
            |--------------------------------------------------------------------------
            */

            const tenantSelect =
                document.getElementById('staff_tenant_id');

            tenantSelect?.addEventListener('change', function() {

                if (!this.value) {
                    return;
                }

                const createUrl =
                    @json(route('staff-management.staff.create'));

                window.location.href =
                    `${createUrl}?tenant_id=${encodeURIComponent(this.value)}`;

            });


            /*
            |--------------------------------------------------------------------------
            | Live Staff Preview
            |--------------------------------------------------------------------------
            */

            const firstNameInput =
                document.getElementById('staff_first_name');

            const lastNameInput =
                document.getElementById('staff_last_name');

            const jobTitleInput =
                document.getElementById('staff_job_title');

            const previewName =
                document.getElementById('staff_preview_name');

            const previewRole =
                document.getElementById('staff_preview_role');


            function refreshStaffPreview() {

                if (previewName) {

                    const first =
                        firstNameInput?.value.trim() || 'New';

                    const last =
                        lastNameInput?.value.trim() || 'Staff Member';

                    previewName.textContent =
                        `${first} ${last}`;

                }


                if (previewRole) {

                    previewRole.textContent =
                        jobTitleInput?.value.trim() ||
                        'Staff profile';

                }

            }


            firstNameInput?.addEventListener(
                'input',
                refreshStaffPreview
            );

            lastNameInput?.addEventListener(
                'input',
                refreshStaffPreview
            );

            jobTitleInput?.addEventListener(
                'input',
                refreshStaffPreview
            );


            /*
            |--------------------------------------------------------------------------
            | Branch Card State
            |--------------------------------------------------------------------------
            */

            document
                .querySelectorAll('[data-branch-card]')
                .forEach(function(card) {

                    const checkbox =
                        card.querySelector('.branch-enabled');

                    function refreshBranchCard() {

                        const enabled =
                            checkbox?.checked ?? false;

                        card.classList.toggle(
                            'border-primary',
                            enabled
                        );

                        card.classList.toggle(
                            'bg-light-primary',
                            enabled
                        );

                        card.classList.toggle(
                            'border-gray-300',
                            !enabled
                        );

                    }

                    checkbox?.addEventListener(
                        'change',
                        refreshBranchCard
                    );

                    refreshBranchCard();

                });


            /*
            |--------------------------------------------------------------------------
            | Service Card State
            |--------------------------------------------------------------------------
            */

            document
                .querySelectorAll('[data-service-card]')
                .forEach(function(card) {

                    const checkbox =
                        card.querySelector('.service-enabled');

                    function refreshServiceCard() {

                        const enabled =
                            checkbox?.checked ?? false;

                        card.classList.toggle(
                            'border-success',
                            enabled
                        );

                        card.classList.toggle(
                            'bg-light-success',
                            enabled
                        );

                        card.classList.toggle(
                            'border-gray-300',
                            !enabled
                        );

                    }

                    checkbox?.addEventListener(
                        'change',
                        refreshServiceCard
                    );

                    refreshServiceCard();

                });

        });
    </script>
@endpush
