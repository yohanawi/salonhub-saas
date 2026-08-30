<x-default-layout>

    @section('title')
        Add Branch
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('branches.create') }}
    @endsection

    <div id="kt_app_content_container">
        @if ($errors->any())
            <div class="alert alert-danger mb-6">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('branches.store') }}">
            @csrf

            @include('pages/apps.branch-management.branches._form', [
                'branch' => $branch,
                'tenants' => $tenants,
            ])
        </form>
    </div>

</x-default-layout>
