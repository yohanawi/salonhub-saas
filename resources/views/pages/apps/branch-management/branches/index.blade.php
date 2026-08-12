<x-default-layout>

    @section('title')
        Branches
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('branches.index') }}
    @endsection

    <div id="kt_app_content_container">
        {{-- Alerts --}}
        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center mb-7">
                <i class="bi bi-check-circle-fill fs-2 me-3"></i>
                <div>{{ session('status') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger d-flex align-items-center mb-7">
                <i class="bi bi-exclamation-circle-fill fs-2 me-3"></i>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-end mb-8">
            @can('create', \App\Models\Branch::class)
                <a href="{{ route('branches.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle fs-5"></i>
                    Add Branch
                </a>
            @endcan
        </div>

        {{-- Summary --}}
        <div class="row g-5 mb-8">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-building fs-2 text-primary"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7">
                                Total Branches
                            </div>

                            <div class="fw-bold fs-2 text-gray-900">
                                {{ $branches->count() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-success">
                                <i class="bi bi-check-circle fs-2 text-success"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7">
                                Active Branches
                            </div>
                            <div class="fw-bold fs-2 text-gray-900">
                                {{ $branches->where('status', \App\Models\Branch::STATUS_ACTIVE)->count() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-warning">
                                <i class="bi bi-star fs-2 text-warning"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7">
                                Main Branch
                            </div>
                            <div class="fw-bold fs-5 text-gray-900">
                                {{ optional($branches->firstWhere('is_main', true))->name ?? 'Not Selected' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>


        {{-- Branch Cards --}}
        <div class="row g-6">
            @forelse ($branches as $branch)
                <div class="col-md-6 col-xl-4">
                    <div class="card branch-card border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            {{-- Top --}}
                            <div class="d-flex align-items-start justify-content-between mb-5">
                                <div class="d-flex align-items-start">
                                    <div class="symbol symbol-45px me-4">
                                        <div class="symbol-label bg-light-primary">
                                            <i class="bi bi-shop fs-2 text-primary"></i>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <h3 class="fw-bold text-gray-900 mb-1">
                                                {{ $branch->name }}
                                            </h3>
                                            @if ($branch->is_main)
                                                <span class="badge badge-light-warning fw-semibold">
                                                    <i class="bi bi-star-fill me-1"></i>
                                                    Main
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-muted fs-7">
                                            Branch Code:
                                            <span class="fw-semibold text-gray-700">
                                                {{ $branch->code }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Status --}}
                                @php
                                    $statusClass = match ($branch->status) {
                                        \App\Models\Branch::STATUS_ACTIVE => 'badge-light-success',
                                        \App\Models\Branch::STATUS_TEMPORARILY_CLOSED => 'badge-light-warning',
                                        default => 'badge-light-danger',
                                    };
                                @endphp

                                <span class="badge {{ $statusClass }}">
                                    <span class="bullet bullet-dot me-2"></span>
                                    {{ $branch->status_label }}
                                </span>
                            </div>

                            {{-- Address --}}
                            <div class="d-flex align-items-start mb-6">
                                <i class="bi bi-geo-alt text-muted fs-5 me-3 mt-1"></i>
                                <div class="text-gray-700 fs-6">
                                    {{ $branch->address_summary ?: 'No location details added.' }}
                                </div>
                            </div>

                            {{-- Statistics --}}
                            <div class="row g-3 mb-6">
                                <div class="col-4">
                                    <div class="bg-light rounded text-center py-3">
                                        <div class="fw-bold fs-5 text-gray-900">
                                            {{ $branch->users_count }}
                                        </div>
                                        <div class="text-muted fs-8">
                                            Users
                                        </div>
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="bg-light rounded text-center py-3">
                                        <div class="fw-bold fs-5 text-gray-900">
                                            {{ $branch->staff_count }}
                                        </div>
                                        <div class="text-muted fs-8">
                                            Staff
                                        </div>
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="bg-light rounded text-center py-3">
                                        <div class="fw-bold fs-5 text-gray-900">
                                            {{ $branch->appointments_count }}
                                        </div>
                                        <div class="text-muted fs-8">
                                            Bookings
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Divider --}}
                            <div class="separator separator-dashed mb-5"></div>

                            {{-- Actions --}}
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <a href="{{ route('branches.show', $branch) }}"
                                    class="btn btn-sm btn-light-primary flex-grow-1">
                                    <i class="bi bi-grid me-1"></i>
                                    Manage
                                </a>

                                @can('update', $branch)
                                    <a href="{{ route('branches.edit', $branch) }}"
                                        class="btn btn-sm btn-light flex-grow-1">
                                        <i class="bi bi-pencil-square me-1"></i>
                                        Edit
                                    </a>
                                @endcan

                                <form method="POST" action="{{ route('branches.switch', $branch) }}"
                                    class="flex-grow-1">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light-success w-100">
                                        <i class="bi bi-arrow-repeat me-1"></i>
                                        Switch
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty

                {{-- Empty State --}}
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-15">
                            <div class="symbol symbol-80px mb-6">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-building-add fs-1 text-primary"></i>
                                </div>
                            </div>
                            <h2 class="fw-bold text-gray-900 mb-3">
                                No branches yet
                            </h2>
                            <div class="text-muted fs-6 mb-7">
                                Create your first salon branch to start managing
                                staff, services and appointments.
                            </div>
                            @can('create', \App\Models\Branch::class)
                                <a href="{{ route('branches.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Create First Branch
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    @push('styles')
        <style>
            .branch-card {
                transition: all 0.25s ease;
            }

            .branch-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08) !important;
            }

            .branch-card .symbol-label {
                border-radius: 12px;
            }
        </style>
    @endpush

</x-default-layout>
