<x-default-layout>
    @section('title') Payroll Runs @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.runs.index') }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.payroll.partials._alerts')
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Payroll Runs</h3></div>
            <div class="card-body pt-0">
                <div class="table-responsive"><table class="table align-middle table-row-dashed gy-5"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Run</th>@if($isSuperAdmin)<th>Salon</th>@endif<th>Period</th><th>Employees</th><th>Net Pay</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
                    @forelse($runs as $run)
                        <tr><td class="fw-bold">{{ $run->run_number }}</td>@if($isSuperAdmin)<td>{{ $run->tenant?->name }}</td>@endif<td>{{ $run->period?->name }}</td><td>{{ number_format($run->employees_count) }}</td><td>LKR {{ number_format((float) $run->net_pay, 2) }}</td><td><span class="badge badge-light-primary">{{ $run->status_label }}</span></td><td class="text-end"><a href="{{ route('payroll.runs.show', $run) }}" class="btn btn-sm btn-light">View</a></td></tr>
                    @empty
                        <tr><td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="text-center text-muted py-10">No payroll runs found.</td></tr>
                    @endforelse
                </tbody></table></div>{{ $runs->links() }}
            </div>
        </div>
    </div>
</x-default-layout>
