<x-default-layout>

    @section('title')
        Branches
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('branches.index') }}
    @endsection


    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-8">
                <div class="symbol symbol-45px me-4">
                    <div class="symbol-label bg-light-success">
                        <i class="bi bi-check-circle-fill text-success fs-2"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-bold text-gray-900 mb-1">
                        Success
                    </div>
                    <div>
                        {{ session('status') }}
                    </div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start mb-8">
                <div class="symbol symbol-45px me-4 flex-shrink-0">
                    <div class="symbol-label bg-light-danger">
                        <i class="bi bi-exclamation-circle-fill text-danger fs-2"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-bold text-gray-900 mb-1">
                        Something went wrong
                    </div>
                    <div>
                        {{ $errors->first() }}
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8">
                <div class="card-title">
                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="bi bi-grid-3x3-gap text-primary fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-bold text-gray-900 mb-1">
                            Branch Directory
                        </h2>
                        <div class="text-muted fs-8">
                            Overview of salon locations and operational activity.
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    @can('create', \App\Models\Branch::class)
                        <a href="{{ route('branches.create') }}" class="btn btn-primary align-self-start">
                            <i class="bi bi-plus-circle-fill me-2"></i>
                            Add Branch
                        </a>
                    @endcan
                </div>
            </div>

            <div class="card-body pt-4">
                <div class="row g-6">
                    @forelse ($branches as $branch)
                        @php
                            $statusClass = $branch->trashed()
                                ? 'danger'
                                : match ($branch->status) {
                                    \App\Models\Branch::STATUS_ACTIVE => 'success',
                                    \App\Models\Branch::STATUS_TEMPORARILY_CLOSED => 'warning',
                                    default => 'danger',
                                };
                        @endphp
                        <div class="col-md-6 col-xl-4">
                            <div class="card border border-gray-200 shadow-sm h-100">
                                <div class="card-body p-6">
                                    <div class="d-flex align-items-start justify-content-between gap-4 mb-6">
                                        <div class="d-flex align-items-start">
                                            <div class="symbol symbol-50px me-4 flex-shrink-0">
                                                <div
                                                    class="symbol-label {{ $branch->trashed() ? 'bg-light-danger' : 'bg-light-primary' }}">
                                                    <i
                                                        class="bi {{ $branch->trashed() ? 'bi-archive' : 'bi-shop' }}
                                                        {{ $branch->trashed() ? 'text-danger' : 'text-primary' }} fs-2">
                                                    </i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                    <h3 class="fw-bold text-gray-900 mb-0">
                                                        {{ $branch->name }}
                                                    </h3>
                                                    @if ($branch->is_main)
                                                        <span class="badge badge-light-warning">
                                                            <i class="bi bi-star-fill me-1"></i>
                                                            Main
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-muted fs-8">
                                                    <i class="bi bi-upc me-1"></i>
                                                    Branch Code
                                                    <span class="fw-semibold text-gray-700 ms-1">
                                                        {{ $branch->code }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <span class="badge badge-light-{{ $statusClass }} px-3 py-2 flex-shrink-0">
                                            <i class="bi bi-circle-fill fs-9 me-2"></i>
                                            {{ $branch->trashed() ? 'Archived' : $branch->status_label }}
                                        </span>
                                    </div>

                                    <div class="rounded-3 bg-light p-4 mb-6">
                                        <div class="d-flex align-items-start">
                                            <div class="symbol symbol-35px me-3 flex-shrink-0">
                                                <div class="symbol-label bg-white">
                                                    <i class="bi bi-geo-alt text-primary"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-muted fs-9 fw-semibold text-uppercase mb-1">
                                                    Location
                                                </div>
                                                <div class="text-gray-700 fs-7">
                                                    {{ $branch->address_summary ?: 'No location details added.' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-6">
                                        <div class="col-4">
                                            <div
                                                class="rounded-3 bg-light-primary p-3 text-center d-flex align-items-center justify-content-between">
                                                <div class="symbol symbol-35px">
                                                    <div class="symbol-label bg-white">
                                                        <i class="bi bi-people text-primary"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bolder fs-5 text-gray-900">
                                                        {{ number_format($branch->users_count) }}
                                                    </div>
                                                    <div class="text-muted fs-9">
                                                        Users
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div
                                                class="rounded-3 bg-light-success p-3 text-center d-flex align-items-center justify-content-between">
                                                <div class="symbol symbol-35px">
                                                    <div class="symbol-label bg-white">
                                                        <i class="bi bi-person-badge text-success"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bolder fs-5 text-gray-900">
                                                        {{ number_format($branch->staff_count) }}
                                                    </div>
                                                    <div class="text-muted fs-9">
                                                        Staff
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div
                                                class="rounded-3 bg-light-info p-3 text-center d-flex align-items-center justify-content-between">
                                                <div class="symbol symbol-35px">
                                                    <div class="symbol-label bg-white">
                                                        <i class="bi bi-calendar-check text-info"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bolder fs-5 text-gray-900">
                                                        {{ number_format($branch->appointments_count) }}
                                                    </div>
                                                    <div class="text-muted fs-9">
                                                        Bookings
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($branch->is_main)
                                        <div class="rounded-3 bg-light-warning p-4 mb-6">
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-35px me-3">
                                                    <div class="symbol-label bg-white">
                                                        <i class="bi bi-star-fill text-warning"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-gray-900 fs-8">
                                                        Primary Salon Location
                                                    </div>
                                                    <div class="text-muted fs-9">
                                                        Default branch for salon operations.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="separator separator-dashed mb-5"></div>

                                    <div class="d-flex gap-3 mb-3">
                                        <a href="{{ route('branches.show', $branch) }}"
                                            class="btn btn-sm btn-light-primary flex-grow-1">
                                            <i class="bi bi-grid me-1"></i>
                                            Manage
                                        </a>
                                        @can('viewReports', $branch)
                                            <a href="{{ route('branches.reports.show', $branch) }}"
                                                class="btn btn-sm btn-light-info flex-grow-1">
                                                <i class="bi bi-bar-chart me-1"></i>
                                                Reports
                                            </a>
                                        @endcan

                                        @can('update', $branch)
                                            <a href="{{ route('branches.edit', $branch) }}"
                                                class="btn btn-sm btn-light flex-grow-1">
                                                <i class="bi bi-pencil-square me-1"></i>
                                                Edit
                                            </a>
                                        @endcan
                                        @if ($branch->trashed())
                                            @can('restore', $branch)
                                                <form method="POST" action="{{ route('branches.restore', $branch) }}"
                                                    class="flex-grow-1">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-light-success w-100">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                                                        Restore
                                                    </button>
                                                </form>
                                            @endcan
                                        @else
                                            <form method="POST" action="{{ route('branches.switch', $branch) }}"
                                                class="flex-grow-1">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-light-success w-100">
                                                    <i class="bi bi-arrow-repeat me-1"></i>
                                                    Switch
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center py-15">
                                <div class="symbol symbol-90px mb-6">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="bi bi-building-add text-primary fs-1"></i>
                                    </div>
                                </div>
                                <h2 class="fw-bolder text-gray-900 mb-3">
                                    No branches yet
                                </h2>
                                <div class="text-muted fs-6 mx-auto mb-7">
                                    Create your first salon branch to start managing
                                    staff, services and appointments.
                                </div>
                                @can('create', \App\Models\Branch::class)
                                    <a href="{{ route('branches.create') }}" class="btn btn-primary">
                                        <i class="bi bi-plus-circle-fill me-2"></i>
                                        Create First Branch
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
