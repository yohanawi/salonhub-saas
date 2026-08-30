<x-default-layout>
    @section('title')
        Loyalty Programs
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.programs.index') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-7">
                    <div class="d-flex align-items-start gap-5">
                        <div class="symbol symbol-55px flex-shrink-0">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-gift-fill text-primary fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Loyalty Programs
                                </h3>
                                <span class="badge badge-light-primary px-3 py-2">
                                    {{ number_format($programs->total()) }}
                                    {{ Str::plural('Program', $programs->total()) }}
                                </span>
                            </div>
                            <div class="text-muted fs-6">
                                Configure reward currencies, redemption rules and program-level earning behavior.
                            </div>
                        </div>
                    </div>

                    @can('create', \App\Models\LoyaltyProgram::class)
                        <a href="{{ route('loyalty-management.programs.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            Create Program
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="bi bi-list-stars text-info fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Program Directory
                        </h3>
                        <div class="text-muted fs-8">
                            Reward programs available to customers.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-6">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th class="min-w-240px">Program</th>
                                <th class="min-w-110px">Rules</th>
                                <th class="min-w-110px">Members</th>
                                <th class="min-w-190px">Redemption</th>
                                <th class="min-w-110px">Status</th>
                                <th class="text-end min-w-100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($programs as $program)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-4">
                                                <div class="symbol-label bg-light-primary rounded-3">
                                                    <i class="bi bi-stars text-primary fs-3"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-gray-900">
                                                    {{ $program->name }}
                                                </div>
                                                <div class="text-muted fs-8">
                                                    {{ Str::limit($program->description ?: 'Program rules and redemption settings', 55) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-info px-3 py-2">
                                            {{ number_format($program->rules_count) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-primary px-3 py-2">
                                            {{ number_format($program->accounts_count) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-gray-900">
                                            {{ number_format($program->redemption_points) }} pts
                                        </div>
                                        <div class="text-muted fs-8">
                                            LKR {{ number_format((float) $program->redemption_value, 2) }} value
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-{{ $program->status === 'active' ? 'success' : 'secondary' }} px-3 py-2">
                                            {{ $program->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('loyalty-management.programs.edit', $program) }}"
                                            class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="tooltip"
                                            title="Edit Program">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="text-center py-15">
                                            <div class="symbol symbol-80px mb-5">
                                                <div class="symbol-label bg-light-primary rounded-circle">
                                                    <i class="bi bi-gift text-primary fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="fw-bold text-gray-900 mb-2">
                                                No loyalty programs configured
                                            </div>
                                            <div class="text-muted">
                                                Create a program to start awarding and redeeming customer points.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($programs->hasPages())
                    <div class="border-top border-gray-200 pt-6 mt-6">
                        {{ $programs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-default-layout>
