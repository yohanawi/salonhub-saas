<x-default-layout>
    @section('title')
        Add Staff
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('staff-management.staff.create') }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('staff-management.staff.store') }}">
            @csrf
            @include('pages/apps.staff-management.staff._form')
        </form>
    </div>
</x-default-layout>
