<x-default-layout>
    @section('title') Loyalty Programs @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.programs.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6 d-flex justify-content-between">
                <h3 class="fw-bold mb-0">Loyalty Programs</h3>
                @can('create', \App\Models\LoyaltyProgram::class)
                    <a href="{{ route('loyalty-management.programs.create') }}" class="btn btn-primary btn-sm">Create Program</a>
                @endcan
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Name</th><th>Rules</th><th>Members</th><th>Redemption</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($programs as $program)
                                <tr>
                                    <td class="fw-bold">{{ $program->name }}</td>
                                    <td>{{ number_format($program->rules_count) }}</td>
                                    <td>{{ number_format($program->accounts_count) }}</td>
                                    <td>{{ number_format($program->redemption_points) }} pts = LKR {{ number_format((float) $program->redemption_value, 2) }}</td>
                                    <td><span class="badge badge-light-{{ $program->status === 'active' ? 'success' : 'secondary' }}">{{ $program->status_label }}</span></td>
                                    <td class="text-end"><a href="{{ route('loyalty-management.programs.edit', $program) }}" class="btn btn-sm btn-light">Edit</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-10">No loyalty programs configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $programs->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
