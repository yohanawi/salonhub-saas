<x-default-layout>

    @section('title')
        Settings Center
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('settings.index') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.settings.partials._alerts')

        <div class="card border-0 shadow-sm mb-10">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-8">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5 flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-sliders2-vertical text-primary fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h3 class="fw-bolder text-gray-900">
                                    Settings Center
                                </h3>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <span class="badge badge-light-primary px-3 py-2">
                                        <i class="bi bi-grid-1x2-fill me-1"></i>
                                        Control Center
                                    </span>
                                </div>
                            </div>
                            <div class="text-muted fs-7">
                                Configure platform, saloon and branch behavior from one centralized settings hub.
                            </div>
                        </div>
                    </div>

                    @if ($isSuperAdmin || $branches->isNotEmpty())
                        <form method="GET" action="{{ route('settings.index') }}"
                            class="d-flex flex-column flex-lg-row gap-3 align-self-xl-start">
                            @if ($isSuperAdmin)
                                <div>
                                    <label class="form-label fw-semibold fs-8 text-muted">
                                        Saloon
                                    </label>
                                    <select name="tenant_id" class="form-select form-select-solid w-lg-250px"
                                        data-control="select2" onchange="this.form.submit()">
                                        <option value="">
                                            Platform Defaults
                                        </option>
                                        @foreach ($tenants as $tenantOption)
                                            <option value="{{ $tenantOption->id }}" @selected((string) request('tenant_id') === (string) $tenantOption->id)>
                                                {{ $tenantOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if ($branches->isNotEmpty())
                                <div>
                                    <label class="form-label fw-semibold fs-8 text-muted">
                                        Branch Scope
                                    </label>
                                    <select name="branch_id" class="form-select form-select-solid w-lg-250px"
                                        data-control="select2" onchange="this.form.submit()">
                                        <option value="">
                                            Tenant Defaults
                                        </option>
                                        @foreach ($branches as $branchOption)
                                            <option value="{{ $branchOption->id }}" @selected((string) request('branch_id') === (string) $branchOption->id)>
                                                {{ $branchOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- SECTION HEADER --}}
        {{-- ========================================================= --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 mb-6">
            <div>
                <h2 class="fw-bolder text-gray-900 mb-1">
                    Configuration Areas
                </h2>
                <div class="text-muted fs-7">
                    Select a section to manage its settings for the current scope.
                </div>
            </div>

            <span class="badge badge-light-primary px-3 py-2 align-self-start align-self-md-center">
                {{ count($sections) }}
                {{ Str::plural('Section', count($sections)) }}
            </span>
        </div>

        {{-- ========================================================= --}}
        {{-- SETTINGS GRID --}}
        {{-- ========================================================= --}}
        <div class="row g-6">
            @foreach ($sections as $sectionKey => $section)
                @php
                    $storedTotal = collect($section['groups'])->sum(fn($group) => $storedCounts[$group] ?? 0);
                @endphp
                <div class="col-md-6 col-xl-4">
                    <a href="{{ route('settings.edit', [
                        'section' => $sectionKey,
                        'tenant_id' => $tenant?->id,
                        'branch_id' => $branch?->id,
                    ]) }}"
                        class="card border-0 shadow-sm h-100 text-decoration-none">
                        <div class="card-body p-7">
                            <div class="d-flex align-items-start justify-content-between mb-6">
                                <div class="symbol symbol-55px">
                                    <div class="symbol-label bg-light-{{ $section['color'] }} rounded-4">
                                        <i class="bi {{ $section['icon'] }} text-{{ $section['color'] }} fs-2"></i>
                                    </div>
                                </div>
                                <span class="badge badge-light px-3 py-2">
                                    {{ number_format($storedTotal) }}
                                    saved
                                </span>
                            </div>
                            <h3 class="fw-bold text-gray-900 mb-2">
                                {{ $section['label'] }}
                            </h3>
                            <div class="text-muted fs-7 mb-6">
                                {{ $section['description'] }}
                            </div>
                            <div class="separator separator-dashed mb-5"></div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center text-{{ $section['color'] }} fw-bold fs-7">
                                    Open Settings
                                    <i class="bi bi-arrow-right-short fs-2 ms-1"></i>
                                </div>

                                @if ($storedTotal > 0)
                                    <span class="badge badge-light-success">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Configured
                                    </span>
                                @else
                                    <span class="badge badge-light-warning">
                                        <i class="bi bi-circle me-1"></i>
                                        Default
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- ========================================================= --}}
        {{-- HIERARCHY INFO --}}
        {{-- ========================================================= --}}
        <div class="card border-0 shadow-sm mt-8">
            <div class="card-body p-6">
                <div class="d-flex align-items-center justify-content-between flex-column flex-md-row gap-5">
                    <div class="d-flex align-items-center gap-4">
                        <div class="symbol symbol-50px me-1 flex-shrink-0">
                            <div class="symbol-label bg-light-warning">
                                <i class="bi bi-diagram-3-fill text-warning fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <div class="fw-bold text-gray-900">
                                Settings Inheritance
                            </div>
                            <div class="text-muted fs-7">
                                More specific settings take priority, allowing salons and branches to customize only
                                what they need.
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-30px me-2">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-globe2 text-primary fs-8"></i>
                                </div>
                            </div>
                            <span class="fw-semibold text-gray-700 fs-8">
                                Platform Default
                            </span>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-30px me-2">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-shop text-info fs-8"></i>
                                </div>
                            </div>
                            <span class="fw-semibold text-gray-700 fs-8">
                                Tenant Setting
                            </span>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-30px me-2">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-geo-alt-fill text-success fs-8"></i>
                                </div>
                            </div>
                            <span class="fw-semibold text-gray-700 fs-8">
                                Branch Override
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
