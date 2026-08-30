<x-default-layout>
    @section('title') Salary Structures @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.salary-structures.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-8">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5">
                    <div class="d-flex align-items-start">
                        <div class="symbol symbol-55px me-5">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-person-vcard text-success fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="badge badge-light-success mb-3">Compensation Profiles</div>
                            <h1 class="fw-bolder text-gray-900 mb-1">Salary Structures</h1>
                            <div class="text-muted">Set salary rates, commission inclusion, and payment details for staff.</div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        @if($isSuperAdmin)
                            <form method="GET">
                                <select name="tenant_id" class="form-select form-select-solid w-250px" onchange="this.form.submit()">
                                    <option value="">All salons</option>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                        @can('create', \App\Models\StaffSalaryStructure::class)
                            <a href="{{ route('payroll.salary-structures.create', request()->only('tenant_id')) }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i>Add Salary Structure
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold mb-1">Salary Structures</h3>
                        <div class="text-muted fs-7">{{ number_format($structures->total()) }} configured structures</div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Staff</th>
                                @if($isSuperAdmin)<th>Salon</th>@endif
                                <th>Branch</th>
                                <th>Type</th>
                                <th>Basic Salary</th>
                                <th>Effective</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($structures as $structure)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-4">
                                                <div class="symbol-label bg-light-primary">
                                                    <i class="bi bi-person text-primary fs-3"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-gray-900">{{ $structure->staff?->full_name }}</div>
                                                <div class="text-muted fs-8">{{ $structure->payment_method ?: 'Payment method not set' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    @if($isSuperAdmin)<td class="text-gray-700">{{ $structure->tenant?->name }}</td>@endif
                                    <td class="text-gray-700">{{ $structure->branch?->name ?? 'All branches' }}</td>
                                    <td><span class="badge badge-light-info">{{ str($structure->salary_type)->replace('_', ' ')->headline() }}</span></td>
                                    <td class="fw-bold text-gray-900">LKR {{ number_format((float) $structure->basic_salary, 2) }}</td>
                                    <td>
                                        <div class="text-gray-800">{{ $structure->effective_from?->format('d M Y') }}</div>
                                        <div class="text-muted fs-8">to {{ $structure->effective_to?->format('d M Y') ?? 'Current' }}</div>
                                    </td>
                                    <td><span class="badge badge-light-{{ $structure->status === 'active' ? 'success' : 'danger' }}">{{ str($structure->status)->headline() }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('payroll.salary-structures.edit', $structure) }}" class="btn btn-sm btn-icon btn-light-primary" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isSuperAdmin ? 8 : 7 }}" class="text-center py-12">
                                        <div class="symbol symbol-60px mx-auto mb-4">
                                            <div class="symbol-label bg-light">
                                                <i class="bi bi-person-vcard text-muted fs-1"></i>
                                            </div>
                                        </div>
                                        <div class="fw-bold text-gray-800">No salary structures found.</div>
                                        <div class="text-muted fs-7">Add salary rules before generating payroll.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $structures->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
