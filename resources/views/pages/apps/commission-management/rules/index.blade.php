<x-default-layout>
    @section('title') Commission Rules @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('commission-management.rules.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.commission-management.partials._alerts')
        <div class="d-flex justify-content-between align-items-center mb-8">
            <form method="GET" class="d-flex gap-3">
                @if($isSuperAdmin)
                    <select name="tenant_id" class="form-select form-select-solid w-250px" onchange="this.form.submit()">
                        <option value="">All salons</option>
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>
                        @endforeach
                    </select>
                @endif
            </form>
            @can('create', \App\Models\CommissionRule::class)
                <a href="{{ route('commission-management.rules.create', request()->only('tenant_id')) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add Rule</a>
            @endcan
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Scope</th>@if($isSuperAdmin)<th>Salon</th>@endif<th>Applies To</th><th>Commission</th><th>Basis</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($rules as $rule)
                                <tr>
                                    <td class="fw-bold">{{ str($rule->commission_scope)->replace('_', ' + ')->headline() }}</td>
                                    @if($isSuperAdmin)<td>{{ $rule->tenant?->name }}</td>@endif
                                    <td>{{ collect([$rule->branch?->name, $rule->staff?->full_name, $rule->service?->name, $rule->product?->name])->filter()->implode(' / ') ?: 'Tenant default' }}</td>
                                    <td>{{ str($rule->commission_type)->headline() }} {{ $rule->commission_type === 'percentage' ? $rule->commission_value . '%' : 'LKR ' . number_format((float) $rule->commission_value, 2) }}</td>
                                    <td>{{ str($rule->calculate_on)->replace('_', ' ')->headline() }}</td>
                                    <td><span class="badge badge-light-{{ $rule->is_active ? 'success' : 'danger' }}">{{ $rule->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('commission-management.rules.edit', $rule) }}" class="btn btn-sm btn-icon btn-light"><i class="bi bi-pencil-square"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="text-center text-muted py-10">No commission rules found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $rules->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
