<x-default-layout>

    @section('title')
        Edit Branch
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('branches.edit', $branch) }}
    @endsection

    <div id="kt_app_content_container">
        @if ($errors->any())
            <div class="alert alert-danger mb-6">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('branches.update', $branch) }}">
            @csrf
            @method('PUT')

            @include('pages/apps.branch-management.branches._form', [
                'branch' => $branch,
                'assignableUsers' => $assignableUsers,
                'assignedUserIds' => $assignedUserIds,
            ])
        </form>
    </div>

</x-default-layout>
