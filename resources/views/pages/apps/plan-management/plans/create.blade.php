<x-default-layout>

    @section('title')
        Add Plan
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('plan-management.plans.create') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.plan-management.plans._form', [
            'action' => route('plan-management.plans.store'),
            'method' => 'POST',
            'submitLabel' => 'Create Plan',
        ])
    </div>

</x-default-layout>
