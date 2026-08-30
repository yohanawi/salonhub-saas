<x-default-layout>

    @section('title')
        Service Details
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('services.show', $service) }}
    @endsection

    @php
        $defaultDuration = $service->default_duration_minutes ?? $service->duration_minutes;

        $assignedBranches = $service->branches->count();

        $activeBranches = $service->branches->filter(fn($branch) => (bool) $branch->pivot?->is_active)->count();
    @endphp


    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-8">

                <div class="symbol symbol-45px me-4">
                    <div class="symbol-label bg-light-success">
                        <i class="bi bi-check-circle-fill text-success fs-2"></i>
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


        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start mb-8">

                <div class="symbol symbol-45px me-4 flex-shrink-0">
                    <div class="symbol-label bg-light-danger">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
                    </div>
                </div>

                <div>
                    <div class="fw-bold text-gray-900 mb-1">
                        Something went wrong
                    </div>

                    <div>
                        {{ $errors->first() }}
                    </div>
                </div>

            </div>
        @endif


        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-8">
                    {{-- Service Identity --}}
                    <div class="d-flex flex-column flex-md-row align-items-md-start gap-6">
                        <div class="symbol symbol-60px flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-scissors text-primary fs-1"></i>
                            </div>
                        </div>

                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-1">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    {{ $service->name }}
                                </h3>
                                @if ($service->is_active)
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
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-1">
                                @if ($service->category)
                                    <span class="badge badge-light-info px-3 py-2">
                                        <i class="bi bi-tag me-1"></i>
                                        {{ $service->category->name }}
                                    </span>
                                @else
                                    <span class="badge badge-light">
                                        Uncategorized
                                    </span>
                                @endif

                                @if ($service->slug)
                                    <span class="text-muted fs-8">
                                        <i class="bi bi-link-45deg me-1"></i>
                                        {{ $service->slug }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <a href="{{ route('services.index') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back
                        </a>
                        @can('update', $service)
                            <a href="{{ route('services.edit', $service) }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil-square me-2"></i>
                                Edit Service
                            </a>
                            <form method="POST" action="{{ route('services.status.update', $service) }}"
                                data-swal-confirm
                                data-swal-title="{{ $service->is_active ? 'Deactivate' : 'Activate' }} {{ $service->name }}?"
                                data-swal-text="{{ $service->is_active ? 'This service will no longer be available for new bookings.' : 'This service will become available for new bookings again.' }}"
                                data-swal-icon="warning"
                                data-swal-confirm-button="{{ $service->is_active ? 'Yes, deactivate' : 'Yes, activate' }}"
                                data-swal-cancel-button="Cancel">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $service->is_active ? 0 : 1 }}">
                                <button type="submit"
                                    class="btn {{ $service->is_active ? 'btn-light-warning' : 'btn-light-success' }} btn-sm">
                                    <i class="bi bi-power me-2"></i>
                                    {{ $service->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 mb-5">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-6 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-cash-stack text-success fs-3"></i>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-2">
                                Default Price
                            </div>
                            <div class="fw-bolder fs-3 text-gray-900">
                                LKR
                                {{ number_format((float) ($service->default_price ?? $service->price), 2) }}
                            </div>
                            <div class="text-muted fs-8 mt-1">
                                Base price before branch overrides
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Default Duration --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-6 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-clock-history text-info fs-3"></i>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-2">
                                Default Duration
                            </div>
                            <div class="fw-bolder fs-3 text-gray-900">
                                {{ $defaultDuration }} min
                            </div>
                            @if ($defaultDuration >= 60)
                                <div class="text-muted fs-8 mt-1">
                                    {{ floor($defaultDuration / 60) }} hr
                                    @if ($defaultDuration % 60)
                                        {{ $defaultDuration % 60 }} min
                                    @endif
                                </div>
                            @else
                                <div class="text-muted fs-8 mt-1">
                                    Appointment base duration
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Branch Coverage --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-6 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-shop text-primary fs-3"></i>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">
                                Assigned Branches
                            </div>
                            <div class="fw-bolder fs-2x text-gray-900">
                                {{ number_format($assignedBranches) }}
                            </div>
                            <div class="text-muted fs-8 mt-1">
                                {{ number_format($activeBranches) }}
                                currently available
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
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-diagram-3 text-primary fs-3"></i>
                        </div>
                    </div>

                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            Branch Configuration
                        </h2>
                        <div class="text-muted fs-8">
                            Service availability, effective pricing and duration by branch.
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <span class="badge badge-light-primary px-3 py-2">
                        {{ number_format($branches->count()) }}
                        {{ Str::plural('Branch', $branches->count()) }}
                    </span>
                </div>
            </div>

            <div class="card-body pt-4">
                @if ($branches->isNotEmpty())
                    <div class="row g-5">
                        @foreach ($branches as $branch)
                            @php
                                $assigned = $service->branches->firstWhere('id', $branch->id);
                                $pivot = $assigned?->pivot;
                                $effectivePrice = $assigned
                                    ? $pivot->price ?? ($service->default_price ?? $service->price)
                                    : null;
                                $effectiveDuration = $assigned
                                    ? $pivot->duration_minutes ??
                                        ($service->default_duration_minutes ?? $service->duration_minutes)
                                    : null;
                                $usesDefaultPrice = $assigned && $pivot->price === null;
                                $usesDefaultDuration = $assigned && $pivot->duration_minutes === null;
                                $hasOverride =
                                    $assigned && ($pivot->price !== null || $pivot->duration_minutes !== null);
                            @endphp

                            <div class="col-12">
                                <div
                                    class="rounded-4 border {{ $assigned ? 'border-gray-300' : 'border-gray-200 bg-light' }} px-5">
                                    <div class="row align-items-center g-5">

                                        {{-- Branch --}}
                                        <div class="col-xl-4">

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-50px me-4 flex-shrink-0">

                                                    <div
                                                        class="symbol-label {{ $assigned ? 'bg-light-primary' : 'bg-light' }}">

                                                        <i
                                                            class="bi bi-building {{ $assigned ? 'text-primary' : 'text-muted' }} fs-3"></i>

                                                    </div>

                                                </div>


                                                <div>

                                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">

                                                        <div class="fw-bold text-gray-900 fs-6">
                                                            {{ $branch->name }}
                                                        </div>


                                                        @if ($assigned)
                                                            <span class="badge badge-light-primary fs-9">
                                                                Assigned
                                                            </span>
                                                        @endif

                                                    </div>


                                                    <div class="text-muted fs-8">
                                                        Branch service configuration
                                                    </div>

                                                </div>

                                            </div>

                                        </div>


                                        {{-- Pricing --}}
                                        <div class="col-sm-6 col-xl-2">

                                            <div class="rounded-3 bg-light p-4 h-100">

                                                <div class="d-flex align-items-center mb-2">

                                                    <i class="bi bi-cash text-success me-2"></i>

                                                    <span class="text-muted fs-8 fw-semibold">
                                                        Price
                                                    </span>

                                                </div>


                                                @if ($assigned)
                                                    <div class="fw-bolder text-gray-900 mb-2">
                                                        LKR
                                                        {{ number_format((float) $effectivePrice, 2) }}
                                                    </div>


                                                    @if ($usesDefaultPrice)
                                                        <span class="badge badge-light fs-9">
                                                            Uses Default
                                                        </span>
                                                    @else
                                                        <span class="badge badge-light-primary fs-9">
                                                            Price Override
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">
                                                        —
                                                    </span>
                                                @endif

                                            </div>

                                        </div>


                                        {{-- Duration --}}
                                        <div class="col-sm-6 col-xl-2">

                                            <div class="rounded-3 bg-light p-4 h-100">

                                                <div class="d-flex align-items-center mb-2">

                                                    <i class="bi bi-clock text-info me-2"></i>

                                                    <span class="text-muted fs-8 fw-semibold">
                                                        Duration
                                                    </span>

                                                </div>


                                                @if ($assigned)
                                                    <div class="fw-bolder text-gray-900 mb-2">
                                                        {{ $effectiveDuration }} min
                                                    </div>


                                                    @if ($usesDefaultDuration)
                                                        <span class="badge badge-light fs-9">
                                                            Uses Default
                                                        </span>
                                                    @else
                                                        <span class="badge badge-light-info fs-9">
                                                            Duration Override
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">
                                                        —
                                                    </span>
                                                @endif

                                            </div>

                                        </div>


                                        {{-- Availability --}}
                                        <div class="col-sm-6 col-xl-2">

                                            <div>

                                                <div class="text-muted fs-8 fw-semibold mb-2">
                                                    Availability
                                                </div>


                                                @if (!$assigned)
                                                    <span class="badge badge-light px-3 py-2">
                                                        <i class="bi bi-slash-circle me-1"></i>
                                                        Unavailable
                                                    </span>
                                                @elseif ($pivot->is_active)
                                                    <span class="badge badge-light-success px-3 py-2">
                                                        <i class="bi bi-check-circle-fill me-1"></i>
                                                        Available
                                                    </span>
                                                @else
                                                    <span class="badge badge-light-danger px-3 py-2">
                                                        <i class="bi bi-x-circle-fill me-1"></i>
                                                        Inactive
                                                    </span>
                                                @endif

                                            </div>

                                        </div>


                                        {{-- Configuration State --}}
                                        <div class="col-sm-6 col-xl-2 text-xl-end">

                                            <div class="text-muted fs-8 fw-semibold mb-2">
                                                Configuration
                                            </div>


                                            @if ($assigned)
                                                @if ($hasOverride)
                                                    <span class="badge badge-light-warning px-3 py-2">
                                                        <i class="bi bi-sliders me-1"></i>
                                                        Customized
                                                    </span>
                                                @else
                                                    <span class="badge badge-light-primary px-3 py-2">
                                                        <i class="bi bi-arrow-repeat me-1"></i>
                                                        Uses Defaults
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge badge-light">
                                                    Not Assigned
                                                </span>
                                            @endif

                                        </div>

                                    </div>


                                    {{-- Assigned Configuration Summary --}}
                                    @if ($assigned)
                                        <div class="separator separator-dashed my-5"></div>


                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">

                                            <div class="d-flex align-items-center">

                                                <div class="symbol symbol-35px me-3">
                                                    <div class="symbol-label bg-light-success">
                                                        <i class="bi bi-check2 text-success"></i>
                                                    </div>
                                                </div>

                                                <div>

                                                    <div class="fw-semibold text-gray-900 fs-8">
                                                        Effective Branch Service
                                                    </div>

                                                    <div class="text-muted fs-9">
                                                        This is the configuration used for bookings at
                                                        {{ $branch->name }}.
                                                    </div>

                                                </div>

                                            </div>


                                            <div class="d-flex flex-wrap gap-2">

                                                <span class="badge badge-light-success px-3 py-2">
                                                    LKR
                                                    {{ number_format((float) $effectivePrice, 2) }}
                                                </span>

                                                <span class="badge badge-light-info px-3 py-2">
                                                    {{ $effectiveDuration }} min
                                                </span>

                                            </div>

                                        </div>
                                    @endif

                                </div>

                            </div>
                        @endforeach

                    </div>
                @else
                    <div class="text-center py-15">
                        <div class="symbol symbol-90px mb-6">
                            <div class="symbol-label bg-light-warning rounded-circle">
                                <i class="bi bi-shop-window text-warning fs-1"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-gray-900 mb-2">
                            No branches available
                        </h3>
                        <div class="text-muted fs-6">
                            There are currently no branches available to configure
                            for this service.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('pages.apps.service-management.services._sweet-alerts')
</x-default-layout>
