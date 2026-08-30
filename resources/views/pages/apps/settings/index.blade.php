<x-default-layout>
    @section('title') Settings Center @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('settings.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.settings.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8 p-lg-10">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-6">
                    <div class="d-flex align-items-start">
                        <div class="symbol symbol-60px me-5">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-sliders text-primary fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="badge badge-light-primary fw-bold mb-3">Control Center</div>
                            <h1 class="fw-bolder text-gray-900 mb-2">Settings Center</h1>
                            <div class="text-gray-600 fs-6">
                                Configure salon behavior from one tenant-aware settings hub.
                            </div>
                        </div>
                    </div>
                    @if($isSuperAdmin || $branches->isNotEmpty())
                        <form method="GET" action="{{ route('settings.index') }}" class="d-flex flex-column flex-md-row gap-3 align-self-lg-start">
                            @if($isSuperAdmin)
                                <select name="tenant_id" class="form-select form-select-solid w-md-250px" onchange="this.form.submit()">
                                    <option value="">Platform defaults</option>
                                    @foreach($tenants as $tenantOption)
                                        <option value="{{ $tenantOption->id }}" @selected((string) request('tenant_id') === (string) $tenantOption->id)>{{ $tenantOption->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @if($branches->isNotEmpty())
                                <select name="branch_id" class="form-select form-select-solid w-md-250px" onchange="this.form.submit()">
                                    <option value="">Tenant defaults</option>
                                    @foreach($branches as $branchOption)
                                        <option value="{{ $branchOption->id }}" @selected((string) request('branch_id') === (string) $branchOption->id)>{{ $branchOption->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-5 mb-8">
            <i class="bi bi-layers text-info fs-2hx me-4"></i>
            <div>
                <div class="fw-bold text-gray-900 mb-1">
                    {{ $branch ? $branch->name . ' branch override' : ($tenant ? $tenant->name . ' tenant settings' : 'Platform default settings') }}
                </div>
                <div class="text-gray-700">
                    Settings resolve in order: platform default, tenant setting, then branch override.
                </div>
            </div>
        </div>

        <div class="row g-6">
            @foreach($sections as $sectionKey => $section)
                @php
                    $storedTotal = collect($section['groups'])->sum(fn ($group) => $storedCounts[$group] ?? 0);
                @endphp
                <div class="col-md-6 col-xl-4">
                    <a href="{{ route('settings.edit', ['section' => $sectionKey, 'tenant_id' => $tenant?->id, 'branch_id' => $branch?->id]) }}" class="card border-0 shadow-sm h-100 text-decoration-none">
                        <div class="card-body p-7">
                            <div class="d-flex align-items-center justify-content-between mb-6">
                                <div class="symbol symbol-50px">
                                    <div class="symbol-label bg-light-{{ $section['color'] }}">
                                        <i class="bi {{ $section['icon'] }} text-{{ $section['color'] }} fs-2"></i>
                                    </div>
                                </div>
                                <span class="badge badge-light">{{ number_format($storedTotal) }} saved</span>
                            </div>
                            <h3 class="fw-bold text-gray-900 mb-2">{{ $section['label'] }}</h3>
                            <div class="text-muted fs-7 mb-5">{{ $section['description'] }}</div>
                            <div class="d-flex align-items-center text-primary fw-bold">
                                Open settings <i class="bi bi-arrow-right-short fs-2 ms-1"></i>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</x-default-layout>
