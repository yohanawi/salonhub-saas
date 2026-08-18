<x-default-layout>
    @section('title') Loyalty Earning Rules @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.rules.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6 d-flex justify-content-between">
                <h3 class="fw-bold mb-0">Earning Rules</h3>
                @can('create', \App\Models\LoyaltyEarningRule::class)
                    <a href="{{ route('loyalty-management.rules.create') }}" class="btn btn-primary btn-sm">Create Rule</a>
                @endcan
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Name</th><th>Program</th><th>Type</th><th>Earn</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($rules as $rule)
                                <tr>
                                    <td class="fw-bold">{{ $rule->name }}</td>
                                    <td>{{ $rule->program?->name }}</td>
                                    <td>{{ str($rule->rule_type)->headline() }}</td>
                                    <td>{{ $rule->rule_type === 'bonus' ? number_format($rule->points_awarded) . ' bonus points' : 'LKR ' . number_format((float) $rule->spend_amount, 2) . ' = ' . number_format($rule->points_awarded) . ' point(s)' }}</td>
                                    <td><span class="badge badge-light-{{ $rule->status === 'active' ? 'success' : 'secondary' }}">{{ str($rule->status)->headline() }}</span></td>
                                    <td class="text-end"><a href="{{ route('loyalty-management.rules.edit', $rule) }}" class="btn btn-sm btn-light">Edit</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-10">No earning rules configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $rules->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
