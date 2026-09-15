<x-default-layout>
    @section('title')
        {{ $section['title'] }}
    @endsection
    @section('breadcrumbs')
        {{ Breadcrumbs::render('settings.edit', $sectionKey) }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.settings.partials._alerts')

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <div class="symbol-label bg-light-{{ $section['color'] }}">
                                <i class="bi {{ $section['icon'] }} text-{{ $section['color'] }} fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h3 class="fw-bolder text-gray-900">{{ $section['title'] }}</h3>
                                <div class="badge badge-light-{{ $section['color'] }}">{{ $section['label'] }}</div>
                            </div>
                            <div class="text-muted">{{ $section['description'] }}</div>
                        </div>
                    </div>
                    <a href="{{ route('settings.index', ['tenant_id' => $tenant?->id, 'branch_id' => $branch?->id]) }}"
                        class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-8">
            <div class="col-xl-3">
                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-header border-0 py-6">
                        <h3 class="fw-bold mb-0">Settings Menu</h3>
                    </div>
                    <div class="card-body pt-0">
                        @foreach ($sections as $navKey => $navSection)
                            <a href="{{ route('settings.edit', ['section' => $navKey, 'tenant_id' => $tenant?->id, 'branch_id' => $branch?->id]) }}"
                                class="d-flex align-items-center rounded py-1 px-3 mb-1 {{ $sectionKey === $navKey ? 'bg-light-primary' : 'bg-hover-light' }}">
                                <div class="symbol symbol-25px me-3">
                                    <div class="symbol-label bg-light-{{ $navSection['color'] }}">
                                        <i class="bi {{ $navSection['icon'] }} text-{{ $navSection['color'] }}"></i>
                                    </div>
                                </div>
                                <span
                                    class="fw-semibold {{ $sectionKey === $navKey ? 'text-primary' : 'text-gray-700' }}">
                                    {{ $navSection['label'] }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-5">
                    <i class="bi bi-diagram-3 text-info fs-2 me-4"></i>
                    <div>
                        <div class="fw-bold text-gray-900 mb-1">Active Scope</div>
                        <div class="text-gray-700 fs-7">
                            {{ $branch ? $branch->name . ' branch override' : ($tenant ? $tenant->name . ' tenant defaults' : 'Platform defaults') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-9">
                <form method="POST" action="{{ route('settings.update', $sectionKey) }}" enctype="multipart/form-data"
                    class="card border-0 shadow-sm">
                    @csrf
                    @method('PATCH')
                    <div
                        class="d-flex align-items-center justify-content-between gap-3 p-6 border-bottom border-gray-200">
                        <div>
                            <h3 class="fw-bold mb-1">{{ $section['title'] }}</h3>
                            <div class="text-muted fs-7">Saved changes are cached and audited automatically.</div>
                        </div>
                        <div class="d-flex gap-3">
                            @if ($isSuperAdmin)
                                <select name="tenant_id" class="form-select form-select-solid w-200px"
                                    data-control="select2"
                                    onchange="window.location='{{ route('settings.edit', $sectionKey) }}?tenant_id='+this.value">
                                    <option value="">Platform defaults</option>
                                    @foreach ($tenants as $tenantOption)
                                        <option value="{{ $tenantOption->id }}" @selected((string) request('tenant_id') === (string) $tenantOption->id)>
                                            {{ $tenantOption->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @if ($branches->isNotEmpty())
                                <select name="branch_id" class="form-select form-select-solid w-200px"
                                    data-control="select2"
                                    onchange="window.location='{{ route('settings.edit', ['section' => $sectionKey, 'tenant_id' => $tenant?->id]) }}&branch_id='+this.value">
                                    <option value="">Tenant defaults</option>
                                    @foreach ($branches as $branchOption)
                                        <option value="{{ $branchOption->id }}" @selected((string) request('branch_id') === (string) $branchOption->id)>
                                            {{ $branchOption->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        @if ($sectionKey === 'general' && $tenant)
                            <div class="rounded border border-gray-200 p-6 mb-8">
                                <div class="d-flex align-items-center mb-6">
                                    <div class="symbol symbol-40px me-4">
                                        <div class="symbol-label bg-light-primary">
                                            <i class="bi bi-building text-primary fs-3"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="fw-bold text-gray-900 mb-0">Salon Profile</h4>
                                        <div class="text-muted fs-7">Stored on the tenant record, not duplicated in
                                            settings.</div>
                                    </div>
                                </div>
                                <div class="row g-6">
                                    <div class="col-md-6">
                                        <label class="form-label required">Salon Name</label>
                                        <input name="tenant[name]" value="{{ old('tenant.name', $tenant->name) }}"
                                            class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Business Type</label>
                                        <select name="tenant[business_type]" class="form-select form-select-solid"
                                            data-control="select2" data-hide-search="true">
                                            <option value="">Select type</option>
                                            @foreach (\App\Models\Tenant::BUSINESS_TYPES as $type)
                                                <option value="{{ $type }}" @selected(old('tenant.business_type', $tenant->business_type) === $type)>
                                                    {{ $type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Phone</label>
                                        <input name="tenant[phone]" value="{{ old('tenant.phone', $tenant->phone) }}"
                                            class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="tenant[email]"
                                            value="{{ old('tenant.email', $tenant->email) }}"
                                            class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Country</label>
                                        <input name="tenant[country]"
                                            value="{{ old('tenant.country', $tenant->country) }}"
                                            class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Currency</label>
                                        <input name="tenant[currency]"
                                            value="{{ old('tenant.currency', $tenant->currency) }}" maxlength="3"
                                            class="form-control form-control-solid text-uppercase">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Timezone</label>
                                        <input name="tenant[timezone]"
                                            value="{{ old('tenant.timezone', $tenant->timezone) }}"
                                            class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Business Logo</label>
                                        <input type="file" name="tenant[logo]"
                                            class="form-control form-control-solid" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                </div>
                            </div>
                        @endif

                        @foreach (collect($definitions)->groupBy('group') as $group => $groupDefinitions)
                            <div class="rounded border border-gray-200 p-6 mb-8">
                                <div class="d-flex align-items-center mb-6">
                                    <div class="symbol symbol-40px me-4">
                                        <div class="symbol-label bg-light-{{ $section['color'] }}">
                                            <i
                                                class="bi {{ $section['icon'] }} text-{{ $section['color'] }} fs-3"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="fw-bold text-gray-900 mb-0">{{ $registry->groupLabel($group) }}
                                        </h4>
                                        <div class="text-muted fs-7">Configure
                                            {{ strtolower($registry->groupLabel($group)) }} behavior for the active
                                            scope.</div>
                                    </div>
                                </div>
                                <div class="row g-6">
                                    @foreach ($groupDefinitions as $key => $definition)
                                        @php
                                            $oldSettings = old('settings', []);
                                            $value = array_key_exists($key, $oldSettings)
                                                ? $oldSettings[$key]
                                                : $values[$key] ?? ($definition['default'] ?? null);
                                            $column = in_array($definition['input'], ['textarea'], true)
                                                ? 'col-12'
                                                : 'col-md-6';
                                        @endphp
                                        <div class="{{ $column }}">
                                            @if ($definition['input'] === 'switch')
                                                <div
                                                    class="d-flex align-items-center justify-content-between bg-light rounded p-4 h-100">
                                                    <div>
                                                        <div class="fw-semibold text-gray-900">
                                                            {{ $definition['label'] }}</div>
                                                        <div class="text-muted fs-8">{{ $key }}</div>
                                                    </div>
                                                    <label
                                                        class="form-check form-switch form-check-custom form-check-solid">
                                                        <input type="hidden" name="settings[{{ $key }}]"
                                                            value="0">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="settings[{{ $key }}]" value="1"
                                                            @checked((bool) $value)>
                                                    </label>
                                                </div>
                                            @elseif($definition['input'] === 'select')
                                                <label
                                                    class="form-label fw-semibold">{{ $definition['label'] }}</label>
                                                <select name="settings[{{ $key }}]" data-control="select2"
                                                    data-hide-search="true" class="form-select form-select-solid">
                                                    @foreach ($definition['options'] as $optionValue => $optionLabel)
                                                        <option value="{{ $optionValue }}"
                                                            @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($definition['input'] === 'textarea')
                                                <label
                                                    class="form-label fw-semibold">{{ $definition['label'] }}</label>
                                                <textarea name="settings[{{ $key }}]" rows="3" class="form-control form-control-solid">{{ $value }}</textarea>
                                            @else
                                                <label
                                                    class="form-label fw-semibold">{{ $definition['label'] }}</label>
                                                <input
                                                    type="{{ $definition['input'] === 'password' ? 'password' : ($definition['input'] === 'time' ? 'time' : ($definition['input'] === 'number' ? 'number' : 'text')) }}"
                                                    name="settings[{{ $key }}]"
                                                    value="{{ $definition['input'] === 'password' ? '' : $value }}"
                                                    class="form-control form-control-solid"
                                                    @if ($definition['input'] === 'number') step="{{ $definition['step'] ?? '1' }}" @endif
                                                    @if ($definition['input'] === 'password' && in_array($key, $encryptedKeys, true)) placeholder="Saved securely - enter a new value to replace" @endif>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-3 border-0 pt-0">
                        <a href="{{ route('settings.index', ['tenant_id' => $tenant?->id, 'branch_id' => $branch?->id]) }}"
                            class="btn btn-light btn-sm">Cancel</a>
                        <button class="btn btn-primary btn-sm">
                            <i class="bi bi-check2 me-2"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-default-layout>
