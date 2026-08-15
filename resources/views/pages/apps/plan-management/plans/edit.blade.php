<x-default-layout>

    @section('title')
        Edit Plan
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('plan-management.plans.edit', $plan) }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.plan-management.plans._form', [
            'action' => route('plan-management.plans.update', $plan),
            'method' => 'PUT',
            'submitLabel' => 'Update Plan',
        ])
    </div>

</x-default-layout>
