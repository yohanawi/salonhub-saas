<x-default-layout>
    @section('title') Payroll Periods @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.periods.index') }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')
        <div class="d-flex justify-content-end mb-8">@can('create', \App\Models\PayrollPeriod::class)<a href="{{ route('payroll.periods.create', request()->only('tenant_id')) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Create Period</a>@endcan</div>
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Payroll Periods</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed gy-5">
                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Period</th>@if($isSuperAdmin)<th>Salon</th>@endif<th>Branch</th><th>Dates</th><th>Runs</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse($periods as $period)
                                <tr>
                                    <td class="fw-bold">{{ $period->name }}</td>
                                    @if($isSuperAdmin)<td>{{ $period->tenant?->name }}</td>@endif
                                    <td>{{ $period->branch?->name ?? 'All branches' }}</td>
                                    <td>{{ $period->start_date?->format('d M Y') }} - {{ $period->end_date?->format('d M Y') }}</td>
                                    <td>{{ number_format($period->runs_count) }}</td>
                                    <td><span class="badge badge-light-primary">{{ $period->status_label }}</span></td>
                                    <td class="text-end"><a href="{{ route('payroll.periods.show', $period) }}" class="btn btn-sm btn-light">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="text-center text-muted py-10">No payroll periods found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $periods->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
