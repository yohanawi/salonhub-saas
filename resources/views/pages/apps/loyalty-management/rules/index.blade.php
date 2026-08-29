<x-default-layout>
    @section('title')
        Loyalty Earning Rules
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('loyalty-management.rules.index') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-7">
                    <div class="d-flex align-items-start gap-5">
                        <div class="symbol symbol-55px flex-shrink-0">
                            <div class="symbol-label bg-light-success rounded-4">
                                <i class="bi bi-lightning-charge-fill text-success fs-1"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    Earning Rules
                                </h3>
                                <span class="badge badge-light-success px-3 py-2">
                                    {{ number_format($rules->total()) }}
                                    {{ Str::plural('Rule', $rules->total()) }}
                                </span>
                            </div>
                            <div class="text-muted fs-6">
                                Define how customers earn points across spend, services, products and branches.
                            </div>
                        </div>
                    </div>

                    @can('create', \App\Models\LoyaltyEarningRule::class)
                        <a href="{{ route('loyalty-management.rules.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            Create Rule
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-diagram-3-fill text-primary fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="fw-bold text-gray-900 mb-1">
                            Rule Directory
                        </h3>
                        <div class="text-muted fs-8">
                            Prioritized earning conditions used by loyalty programs.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-6">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th class="min-w-240px">Rule</th>
                                <th class="min-w-180px">Program</th>
                                <th class="min-w-110px">Type</th>
                                <th class="min-w-210px">Earn</th>
                                <th class="min-w-110px">Status</th>
                                <th class="text-end min-w-100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rules as $rule)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-4">
                                                <div class="symbol-label bg-light-success rounded-3">
                                                    <i class="bi bi-lightning-charge-fill text-success fs-3"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-gray-900">
                                                    {{ $rule->name }}
                                                </div>
                                                <div class="text-muted fs-8">
                                                    Priority {{ number_format($rule->priority ?? 100) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-gray-900">
                                            {{ $rule->program?->name ?? 'No program' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-info px-3 py-2">
                                            {{ str($rule->rule_type)->headline() }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-gray-900">
                                            {{ $rule->rule_type === 'bonus' ? number_format($rule->points_awarded) . ' bonus points' : 'LKR ' . number_format((float) $rule->spend_amount, 2) . ' = ' . number_format($rule->points_awarded) . ' point(s)' }}
                                        </div>
                                        <div class="text-muted fs-8">
                                            Minimum LKR {{ number_format((float) ($rule->minimum_purchase_amount ?? 0), 2) }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-{{ $rule->status === 'active' ? 'success' : 'secondary' }} px-3 py-2">
                                            {{ str($rule->status)->headline() }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('loyalty-management.rules.edit', $rule) }}"
                                            class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="tooltip"
                                            title="Edit Rule">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="text-center py-15">
                                            <div class="symbol symbol-80px mb-5">
                                                <div class="symbol-label bg-light-success rounded-circle">
                                                    <i class="bi bi-lightning text-success fs-1"></i>
                                                </div>
                                            </div>
                                            <div class="fw-bold text-gray-900 mb-2">
                                                No earning rules configured
                                            </div>
                                            <div class="text-muted">
                                                Add rules to decide how customers collect loyalty points.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($rules->hasPages())
                    <div class="border-top border-gray-200 pt-6 mt-6">
                        {{ $rules->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-default-layout>
