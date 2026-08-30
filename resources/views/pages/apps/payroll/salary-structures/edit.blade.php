<x-default-layout>
    @section('title') Edit Salary Structure @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('payroll.salary-structures.edit', $structure) }} @endsection
    <div id="kt_app_content_container">@include('pages/apps.payroll.partials._alerts')<div class="card border-0 shadow-sm"><div class="card-header border-0 py-6"><h3 class="fw-bold mb-0">Edit Salary Structure</h3></div><div class="card-body">@include('pages/apps.payroll.salary-structures._form')</div></div></div>
</x-default-layout>
