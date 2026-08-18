<x-default-layout>
    @section('title') Salary Structures @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.salary-structures.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')
        <div class="d-flex justify-content-between mb-8">
            <form method="GET">
                @if($isSuperAdmin)
                    <select name="tenant_id" class="form-select form-select-solid w-250px" onchange="this.form.submit()">
                        <option value="">All salons</option>
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>
                        @endforeach
                    </select>
                @endif
            </form>
            @can('create', \App\Models\StaffSalaryStructure::class)
                <a href="{{ route('payroll.salary-structures.create', request()->only('tenant_id')) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add Salary Structure</a>
            @endcan
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Salary Structures</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Staff</th>@if($isSuperAdmin)<th>Salon</th>@endif<th>Branch</th><th>Type</th><th>Basic Salary</th><th>Effective</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse($structures as $structure)
                                <tr>
                                    <td class="fw-bold">{{ $structure->staff?->full_name }}</td>
                                    @if($isSuperAdmin)<td>{{ $structure->tenant?->name }}</td>@endif
                                    <td>{{ $structure->branch?->name ?? 'All branches' }}</td>
                                    <td>{{ str($structure->salary_type)->replace('_', ' ')->headline() }}</td>
                                    <td>LKR {{ number_format((float) $structure->basic_salary, 2) }}</td>
                                    <td>{{ $structure->effective_from?->format('d M Y') }} - {{ $structure->effective_to?->format('d M Y') ?? 'Current' }}</td>
                                    <td><span class="badge badge-light-{{ $structure->status === 'active' ? 'success' : 'danger' }}">{{ str($structure->status)->headline() }}</span></td>
                                    <td class="text-end"><a href="{{ route('payroll.salary-structures.edit', $structure) }}" class="btn btn-sm btn-icon btn-light"><i class="bi bi-pencil-square"></i></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $isSuperAdmin ? 8 : 7 }}" class="text-center text-muted py-10">No salary structures found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $structures->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
