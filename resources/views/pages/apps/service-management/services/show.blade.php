<x-default-layout>

    @section('title')
        Service Details
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('services.show', $service) }}
    @endsection

    <div id="kt_app_content_container" class="app-container container-xxl">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-8">
                <span class="symbol symbol-40px me-4">
                    <span class="symbol-label bg-light-success">
                        <i class="bi bi-check-lg fs-2 text-success"></i>
                    </span>
                </span>
                <div>
                    <div class="fw-bold">Success</div>
                    <div>{{ session('status') }}</div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-8">
                <span class="symbol symbol-40px me-4">
                    <span class="symbol-label bg-light-danger">
                        <i class="bi bi-exclamation-lg fs-2 text-danger"></i>
                    </span>
                </span>

                <div>
                    <div class="fw-bold">Something went wrong</div>
                    <div>{{ $errors->first() }}</div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm overflow-hidden mb-8">
            <div class="card-body p-8">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-6">
                    <div class="d-flex align-items-start">
                        <span class="symbol symbol-70px me-5">
                            <span class="symbol-label bg-light-primary rounded-3">
                                <i class="bi bi-scissors fs-2x text-primary"></i>
                            </span>
                        </span>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h1 class="fw-bold text-gray-900 mb-0">
                                    {{ $service->name }}
                                </h1>
                                @if ($service->is_active)
                                    <span class="badge badge-light-success px-3 py-2">
                                        <span class="bullet bullet-dot bg-success me-2"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="badge badge-light-danger px-3 py-2">
                                        <span class="bullet bullet-dot bg-danger me-2"></span>
                                        Inactive
                                    </span>
                                @endif
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                @if ($service->category)
                                    <span class="badge badge-light-info">
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
                            <div class="text-gray-700 fs-6 mw-800px">
                                {{ $service->description ?: 'No description provided for this service.' }}
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 align-items-start">
                        <a href="{{ route('services.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back
                        </a>
                        @can('update', $service)
                            <a href="{{ route('services.edit', $service) }}" class="btn btn-primary">
                                <i class="bi bi-pencil-square me-2"></i>
                                Edit Service
                            </a>
                            <form method="POST" action="{{ route('services.status.update', $service) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $service->is_active ? 0 : 1 }}">
                                <button type="submit"
                                    class="btn {{ $service->is_active ? 'btn-light-warning' : 'btn-light-success' }}"
                                    onclick="return confirm(
                                            '{{ $service->is_active ? 'Deactivate this service?' : 'Activate this service?' }}'
                                        )">
                                    <i class="bi bi-power me-2"></i>
                                    {{ $service->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 mb-8">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 service-stat-card">
                    <div class="card-body p-6">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted fw-semibold fs-7 mb-2">
                                    Default Price
                                </div>
                                <div class="fw-bold fs-2 text-gray-900">
                                    LKR
                                    {{ number_format((float) ($service->default_price ?? $service->price), 2) }}
                                </div>
                                <div class="text-muted fs-8 mt-1">
                                    Base price before branch overrides
                                </div>
                            </div>
                            <span class="symbol symbol-55px">
                                <span class="symbol-label bg-light-success">
                                    <i class="bi bi-cash-stack fs-2 text-success"></i>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                @php
                    $defaultDuration = $service->default_duration_minutes ?? $service->duration_minutes;
                @endphp
                <div class="card border-0 shadow-sm h-100 service-stat-card">
                    <div class="card-body p-6">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted fw-semibold fs-7 mb-2">
                                    Default Duration
                                </div>
                                <div class="fw-bold fs-2 text-gray-900">
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
                            <span class="symbol symbol-55px">
                                <span class="symbol-label bg-light-info">
                                    <i class="bi bi-clock-history fs-2 text-info"></i>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                @php
                    $assignedBranches = $service->branches->count();
                    $activeBranches = $service->branches
                        ->filter(fn($branch) => (bool) $branch->pivot?->is_active)
                        ->count();
                @endphp
                <div class="card border-0 shadow-sm h-100 service-stat-card">
                    <div class="card-body p-6">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted fw-semibold fs-7 mb-2">
                                    Assigned Branches
                                </div>
                                <div class="fw-bold fs-2 text-gray-900">
                                    {{ $assignedBranches }}
                                </div>
                                <div class="text-muted fs-8 mt-1">
                                    {{ $activeBranches }} currently available
                                </div>
                            </div>
                            <span class="symbol symbol-55px">
                                <span class="symbol-label bg-light-primary">
                                    <i class="bi bi-shop fs-2 text-primary"></i>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8 pb-4">
                <div class="card-title">
                    <div class="d-flex align-items-center">
                        <span class="symbol symbol-40px me-3">
                            <span class="symbol-label bg-light-primary">
                                <i class="bi bi-diagram-3 fs-2 text-primary"></i>
                            </span>
                        </span>
                        <div>
                            <h2 class="fw-bold text-gray-900 mb-1">
                                Branch Configuration
                            </h2>
                            <div class="text-muted fs-7">
                                View availability, pricing and duration for each branch.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <span class="badge badge-light-primary px-4 py-3">
                        {{ $branches->count() }}
                        {{ Str::plural('Branch', $branches->count()) }}
                    </span>
                </div>
            </div>

            <div class="card-body pt-5">
                <div class="d-flex flex-column gap-4">
                    @forelse ($branches as $branch)
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
                        @endphp

                        <div class="branch-config-card border rounded-3 p-5">
                            <div class="row align-items-center gy-5">
                                {{-- Branch --}}
                                <div class="col-lg-4">
                                    <div class="d-flex align-items-center">
                                        <span class="symbol symbol-45px me-4">
                                            <span class="symbol-label bg-light-primary">
                                                <i class="bi bi-building fs-3 text-primary"></i>
                                            </span>
                                        </span>
                                        <div>
                                            <div class="fw-bold text-gray-900 fs-6">
                                                {{ $branch->name }}
                                            </div>
                                            <div class="text-muted fs-8">
                                                Branch service configuration
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-2">
                                    <div class="text-muted fs-8 mb-1">
                                        Price
                                    </div>
                                    @if ($assigned)
                                        <div class="fw-bold text-gray-900">
                                            LKR
                                            {{ number_format((float) $effectivePrice, 2) }}
                                        </div>
                                        @if ($usesDefaultPrice)
                                            <span class="badge badge-light fs-9 mt-1">
                                                Default
                                            </span>
                                        @else
                                            <span class="badge badge-light-primary fs-9 mt-1">
                                                Override
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @endif
                                </div>
                                <div class="col-sm-6 col-lg-2">
                                    <div class="text-muted fs-8 mb-1">
                                        Duration
                                    </div>
                                    @if ($assigned)
                                        <div class="fw-bold text-gray-900">
                                            {{ $effectiveDuration }} min
                                        </div>
                                        @if ($usesDefaultDuration)
                                            <span class="badge badge-light fs-9 mt-1">
                                                Default
                                            </span>
                                        @else
                                            <span class="badge badge-light-info fs-9 mt-1">
                                                Override
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @endif
                                </div>

                                <div class="col-lg-2">
                                    <div class="text-muted fs-8 mb-2">
                                        Availability
                                    </div>
                                    @if (!$assigned)
                                        <span class="badge badge-light px-3 py-2">
                                            <i class="bi bi-slash-circle me-1"></i>
                                            Unavailable
                                        </span>
                                    @elseif ($pivot->is_active)
                                        <span class="badge badge-light-success px-3 py-2">
                                            <span class="bullet bullet-dot bg-success me-2"></span>
                                            Available
                                        </span>
                                    @else
                                        <span class="badge badge-light-danger px-3 py-2">
                                            <span class="bullet bullet-dot bg-danger me-2"></span>
                                            Inactive
                                        </span>
                                    @endif
                                </div>

                                <div class="col-lg-2 text-lg-end">
                                    @if ($assigned)
                                        @if ($pivot->price !== null || $pivot->duration_minutes !== null)
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
                                        <span class="text-muted fs-8">
                                            Not assigned
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-15">
                            <span class="symbol symbol-90px mb-5">
                                <span class="symbol-label bg-light-warning rounded-circle">
                                    <i class="bi bi-shop-window fs-2x text-warning"></i>
                                </span>
                            </span>
                            <h3 class="fw-bold text-gray-900 mb-2">
                                No branches available
                            </h3>
                            <div class="text-muted">
                                There are currently no branches available to configure for this service.
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .service-stat-card {
                transition:
                    transform .2s ease,
                    box-shadow .2s ease;
            }

            .service-stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 .75rem 2rem rgba(0, 0, 0, .06) !important;
            }

            .branch-config-card {
                background: var(--bs-body-bg);
                transition:
                    border-color .2s ease,
                    background-color .2s ease,
                    transform .2s ease,
                    box-shadow .2s ease;
            }

            .branch-config-card:hover {
                border-color: rgba(var(--bs-primary-rgb), .35) !important;
                background:
                    linear-gradient(135deg,
                        rgba(var(--bs-primary-rgb), .025),
                        transparent);
                transform: translateY(-1px);
                box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .04);
            }
        </style>
    @endpush

</x-default-layout>
