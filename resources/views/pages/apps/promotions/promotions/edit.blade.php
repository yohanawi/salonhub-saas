<x-default-layout>
    @section('title') Edit Promotion @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('promotions.promotions.edit', $promotion) }} @endsection
    <div id="kt_app_content_container">
        @include('pages/apps.promotions.partials._alerts')
        <form method="POST" action="{{ route('promotions.promotions.update', $promotion) }}" class="card border-0 shadow-sm">
            @method('PUT')
            <div class="card-body p-8">@include('pages/apps.promotions.promotions._form')</div>
        </form>
    </div>
</x-default-layout>
