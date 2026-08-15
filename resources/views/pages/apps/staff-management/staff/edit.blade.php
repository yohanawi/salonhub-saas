<x-default-layout>
    @section('title')
        Edit Staff
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('staff-management.staff.edit', $staffMember) }}
    @endsection

    <div id="kt_app_content_container">
        <form method="POST" action="{{ route('staff-management.staff.update', $staffMember) }}">
            @csrf
            @method('PUT')
            @include('pages/apps.staff-management.staff._form')
        </form>
    </div>
</x-default-layout>
