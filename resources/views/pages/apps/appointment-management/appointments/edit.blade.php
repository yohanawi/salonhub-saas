<x-default-layout>
    @section('title')
        Edit Appointment
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('appointment-management.appointments.edit', $appointment) }}
    @endsection

    <div id="kt_app_content_container">
        <div class="card border-0 shadow-sm overflow-hidden mb-8">
            <div class="card-body p-8 p-lg-10">
                <div class="d-flex align-items-center gap-5">
                    <div class="symbol symbol-60px">
                        <span class="symbol-label bg-light-primary rounded-4">
                            <i class="bi bi-pencil-square text-primary fs-1"></i>
                        </span>
                    </div>
                    <div>
                        <h1 class="fw-bolder text-gray-900 mb-1">Edit Appointment</h1>
                        <div class="text-muted">{{ $appointment->appointment_number }}</div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('appointment-management.appointments.update', $appointment) }}">
            @csrf
            @method('PUT')
            @include('pages.apps.appointment-management.appointments._form')
        </form>
    </div>
</x-default-layout>
