<x-default-layout>
    @section('title')
        Create Appointment
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('appointment-management.appointments.create') }}
    @endsection

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm overflow-hidden mb-8">
            <div class="card-body p-8 p-lg-10">
                <div class="d-flex align-items-center gap-5">
                    <div class="symbol symbol-60px">
                        <span class="symbol-label bg-light-primary rounded-4">
                            <i class="bi bi-calendar-plus text-primary fs-1"></i>
                        </span>
                    </div>
                    <div>
                        <h1 class="fw-bolder text-gray-900 mb-1">Create Appointment</h1>
                        <div class="text-muted">Book customer services with staff availability checks.</div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('appointment-management.appointments.store') }}">
            @csrf
            @include('pages.apps.appointment-management.appointments._form')
        </form>
    </div>
</x-default-layout>
