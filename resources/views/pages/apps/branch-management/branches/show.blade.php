<x-default-layout>

    @section('title')
        Branch Details
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('branches.show', $branch) }}
    @endsection

    @php
        $statusClass = match ($branch->status) {
            \App\Models\Branch::STATUS_ACTIVE => 'success',
            \App\Models\Branch::STATUS_TEMPORARILY_CLOSED => 'warning',
            default => 'danger',
        };
    @endphp

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

        <div class="card border-0 shadow-sm mb-8">
            <div class="card-body p-4 p-lg-6">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-8">
                    <div class="d-flex flex-column flex-md-row align-items-md-start gap-6">
                        <div class="symbol symbol-80px flex-shrink-0">
                            <div
                                class="symbol-label {{ $branch->trashed() ? 'bg-light-danger' : 'bg-light-primary' }} rounded-4">
                                <i
                                    class="bi {{ $branch->trashed() ? 'bi-archive' : 'bi-shop-window' }}
                                    {{ $branch->trashed() ? 'text-danger' : 'text-primary' }} fs-1"></i>
                            </div>
                        </div>

                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                <h3 class="fw-bolder text-gray-900 mb-0">
                                    {{ $branch->name }}
                                </h3>
                                @if ($branch->is_main)
                                    <span class="badge badge-light-warning px-3 py-2">
                                        <i class="bi bi-star-fill me-1"></i>
                                        Main Branch
                                    </span>
                                @endif

                                <span
                                    class="badge badge-light-{{ $branch->trashed() ? 'danger' : $statusClass }} px-3 py-2">
                                    <i class="bi bi-circle-fill fs-9 me-2"></i>
                                    {{ $branch->trashed() ? 'Archived' : $branch->status_label }}
                                </span>
                            </div>

                            <div class="d-flex flex-wrap gap-4 text-muted fs-7 mb-4">
                                <span>
                                    <i class="bi bi-upc-scan me-1"></i>
                                    {{ $branch->code }}
                                </span>
                                <span>
                                    <i class="bi bi-geo-alt me-1"></i>
                                    {{ $branch->address_summary ?: 'No location details added.' }}
                                </span>
                                <span>
                                    <i class="bi bi-clock-history me-1"></i>
                                    {{ $branch->timezone }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        @if ($branch->trashed())
                            @can('restore', $branch)
                                <form method="POST" action="{{ route('branches.restore', $branch) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-light-success btn-sm">
                                        <i class="bi bi-arrow-counterclockwise me-2"></i>
                                        Restore Branch
                                    </button>
                                </form>
                            @endcan
                        @else
                            <form method="POST" action="{{ route('branches.switch', $branch) }}">
                                @csrf
                                <button type="submit" class="btn btn-light-primary btn-sm">
                                    <i class="bi bi-arrow-repeat me-2"></i>
                                    Switch Here
                                </button>
                            </form>

                            @can('update', $branch)
                                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-pencil-square me-2"></i>
                                    Edit Branch
                                </a>
                            @endcan

                            @can('viewReports', $branch)
                                <a href="{{ route('branches.reports.show', $branch) }}" class="btn btn-light-info btn-sm">
                                    <i class="bi bi-bar-chart me-2"></i>
                                    Reports
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 mb-8">
            {{-- Users --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-6">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-people fs-3 text-primary"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-muted fs-8 fw-semibold text-uppercase">
                                    Assigned Users
                                </div>
                                <div class="fw-bolder fs-2x text-gray-900">
                                    {{ number_format($branch->users->count()) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Operating Days --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-6">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-calendar-week fs-3 text-success"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-muted fs-8 fw-semibold text-uppercase">
                                    Operating Days
                                </div>
                                <div class="fw-bolder fs-2x text-gray-900">
                                    {{ number_format($branch->businessHours->where('is_closed', false)->count()) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Currency --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-6">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-cash-stack fs-3 text-info"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-muted fs-8 fw-semibold text-uppercase">
                                    Currency
                                </div>
                                <div class="fw-bolder fs-2x text-gray-900">
                                    {{ $branch->currency }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-8">
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-person-lines-fill text-primary"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Contact Information
                                </h3>
                                <div class="text-muted fs-8">
                                    Branch contact and regional details.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-4">
                        <div class="d-flex align-items-center py-4 border-bottom border-gray-200">
                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-telephone text-primary"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-muted fs-8 mb-1">
                                    Phone
                                </div>
                                <div class="fw-semibold text-gray-900">
                                    {{ $branch->phone ?: 'Not provided' }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center py-4 border-bottom border-gray-200">
                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-envelope text-info"></i>
                                </div>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-muted fs-8 mb-1">
                                    Email
                                </div>
                                <div class="fw-semibold text-gray-900 text-truncate" title="{{ $branch->email }}">
                                    {{ $branch->email ?: 'Not provided' }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center pt-4">
                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-clock-history text-warning"></i>
                                </div>
                            </div>
                            <div>
                                <div class="text-muted fs-8 mb-1">
                                    Timezone
                                </div>
                                <div class="fw-semibold text-gray-900">
                                    {{ $branch->timezone }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div class="symbol symbol-40px me-4">
                                <div class="symbol-label bg-light-info">
                                    <i class="bi bi-receipt text-info"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="fw-bold text-gray-900 mb-1">
                                    Billing Settings
                                </h3>
                                <div class="text-muted fs-8">
                                    Invoice and tax configuration.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-4">
                        <div
                            class="d-flex align-items-center justify-content-between pb-4 mb-4 border-bottom border-gray-200">
                            <span class="text-muted fs-8">
                                Invoice Prefix
                            </span>
                            <span class="badge badge-light-primary px-3 py-2">
                                {{ $branch->invoice_prefix ?: 'Not set' }}
                            </span>
                        </div>

                        <div
                            class="d-flex align-items-center justify-content-between pb-4 mb-4 border-bottom border-gray-200">
                            <span class="text-muted fs-8">
                                Tax Status
                            </span>
                            @if ($branch->tax_enabled)
                                <span class="badge badge-light-success px-3 py-2">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Enabled
                                </span>
                            @else
                                <span class="badge badge-light-secondary px-3 py-2">
                                    Disabled
                                </span>
                            @endif
                        </div>

                        @if ($branch->tax_enabled)
                            <div class="rounded-3 bg-light-success p-4 mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted fs-8">
                                        Tax Name
                                    </span>
                                    <span class="fw-semibold text-gray-900">
                                        {{ $branch->tax_name ?: 'Tax' }}
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fs-8">
                                        Rate
                                    </span>
                                    <span class="fw-bold text-success">
                                        {{ rtrim(rtrim(number_format((float) $branch->tax_rate, 4), '0'), '.') }}%
                                    </span>
                                </div>
                            </div>
                        @endif

                        <div>
                            <div class="text-muted fs-8 mb-1">
                                Tax Number
                            </div>
                            <div class="fw-semibold text-gray-900">
                                {{ $branch->tax_number ?: 'Not provided' }}
                            </div>
                        </div>
                    </div>
                </div>

                @can('changeStatus', $branch)
                    <div class="card border-0 shadow-sm mb-8">
                        <div class="card-header border-0 pt-8">
                            <div class="card-title">
                                <div class="symbol symbol-40px me-4">
                                    <div class="symbol-label bg-light-warning">
                                        <i class="bi bi-toggle-on text-warning"></i>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="fw-bold text-gray-900 mb-1">
                                        Branch Status
                                    </h3>
                                    <div class="text-muted fs-8">
                                        Control branch operational availability.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body pt-4">
                            <form method="POST" action="{{ route('branches.status.update', $branch) }}">
                                @csrf
                                @method('PATCH')
                                <div class="mb-5">
                                    <label class="form-label required fw-semibold">
                                        Status
                                    </label>
                                    <select name="status" class="form-select" required data-control="select2"
                                        data-hide-search="true">
                                        @foreach (\App\Models\Branch::STATUSES as $status)
                                            <option value="{{ $status }}" @selected(old('status', $branch->status) === $status)>
                                                {{ str($status)->replace('_', ' ')->headline() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @if ($branch->is_main)
                                    <div class="rounded-3 bg-light-warning p-4 mb-5">
                                        <div class="d-flex align-items-start">
                                            <div class="symbol symbol-35px me-3">
                                                <div class="symbol-label bg-white">
                                                    <i class="bi bi-exclamation-triangle text-warning"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-gray-900 mb-1">
                                                    Main Branch
                                                </div>
                                                <div class="text-muted fs-8">
                                                    Choose another main branch before making this branch unavailable.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-5">
                                        <label class="form-label fw-semibold">
                                            Replacement Main Branch
                                        </label>
                                        <select name="replacement_main_branch_id" class="form-select"
                                            data-control="select2" data-hide-search="true">
                                            <option value="">
                                                Select branch
                                            </option>
                                            @foreach ($replacementBranches as $replacementBranch)
                                                <option value="{{ $replacementBranch->id }}">
                                                    {{ $replacementBranch->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <button type="submit" class="btn btn-light-primary w-100">
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    Update Status
                                </button>
                            </form>
                        </div>
                    </div>
                @endcan

                @can('delete', $branch)
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-8">
                            <div class="card-title">
                                <div class="symbol symbol-40px me-4">
                                    <div class="symbol-label bg-light-danger">
                                        <i class="bi bi-archive text-danger"></i>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="fw-bold text-danger mb-1">
                                        Archive Branch
                                    </h3>
                                    <div class="text-muted fs-8">
                                        Remove this branch from active operations.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body pt-4">
                            <form method="POST" action="{{ route('branches.archive', $branch) }}"
                                data-swal-confirm
                                data-swal-title="Archive {{ $branch->name }}?"
                                data-swal-text="Archived branches will no longer be available for normal operations. This can be undone later by restoring the branch."
                                data-swal-icon="warning"
                                data-swal-confirm-button="Yes, archive"
                                data-swal-cancel-button="Keep active">
                                @csrf
                                @method('DELETE')
                                @if ($branch->is_main)
                                    <div class="mb-5">
                                        <label class="form-label required fw-semibold">
                                            Replacement Main Branch
                                        </label>
                                        <select name="replacement_main_branch_id" class="form-select" required
                                            data-control="select2" data-hide-search="true">
                                            <option value="">
                                                Select branch
                                            </option>
                                            @foreach ($replacementBranches as $replacementBranch)
                                                <option value="{{ $replacementBranch->id }}">
                                                    {{ $replacementBranch->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="rounded-3 bg-light-danger p-4 mb-5">
                                    <div class="text-muted fs-8">
                                        <i class="bi bi-exclamation-triangle text-danger me-1"></i>
                                        Archived branches will no longer be available for normal operations.
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-light-danger w-100">
                                    <i class="bi bi-archive me-1"></i>
                                    Archive Branch
                                </button>
                            </form>
                        </div>
                    </div>
                @endcan
            </div>

            <div class="col-xl-8">
                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-calendar-week text-success fs-2"></i>
                                </div>
                            </div>
                            <div>
                                <h2 class="fw-bold text-gray-900 mb-1">
                                    Business Hours
                                </h2>
                                <div class="text-muted fs-8">
                                    Weekly opening and closing hours.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-4">
                        @can('manageHours', $branch)
                            <form method="POST" action="{{ route('branches.hours.update', $branch) }}">
                                @csrf
                                @method('PUT')
                                <div class="d-flex flex-column gap-4">
                                    @foreach (\App\Models\Branch::DAY_LABELS as $dayNumber => $dayLabel)
                                        @php
                                            $hours = $branch->businessHours->firstWhere('day_of_week', $dayNumber);
                                        @endphp
                                        <div class="rounded-4 border border-gray-300 p-4">
                                            <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]"
                                                value="{{ $dayNumber }}">
                                            <div class="row align-items-center g-4">
                                                <div class="col-md-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-35px me-3">
                                                            <div class="symbol-label bg-light-primary">
                                                                <i class="bi bi-calendar-day text-primary"></i>
                                                            </div>
                                                        </div>
                                                        <div class="fw-bold text-gray-900">
                                                            {{ $dayLabel }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="text-muted fs-8 mb-2">
                                                        Opens
                                                    </label>
                                                    <input type="time" name="hours[{{ $loop->index }}][opens_at]"
                                                        value="{{ old("hours.{$loop->index}.opens_at", $hours?->opens_at?->format('H:i')) }}"
                                                        class="form-control">
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="text-muted fs-8 mb-2">
                                                        Closes
                                                    </label>
                                                    <input type="time" name="hours[{{ $loop->index }}][closes_at]"
                                                        value="{{ old("hours.{$loop->index}.closes_at", $hours?->closes_at?->format('H:i')) }}"
                                                        class="form-control">
                                                </div>

                                                <div class="col-md-3">
                                                    <label
                                                        class="d-flex align-items-center justify-content-between rounded-3 bg-light p-3">
                                                        <span class="fw-semibold text-gray-700">
                                                            Closed
                                                        </span>
                                                        <div
                                                            class="form-check form-switch form-check-custom form-check-solid">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="hours[{{ $loop->index }}][is_closed]"
                                                                value="1" @checked(old("hours.{$loop->index}.is_closed", $hours?->is_closed))>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="d-flex justify-content-end mt-6">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Save Business Hours
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="d-flex flex-column gap-3">
                                @foreach (\App\Models\Branch::DAY_LABELS as $dayNumber => $dayLabel)
                                    @php
                                        $hours = $branch->businessHours->firstWhere('day_of_week', $dayNumber);
                                    @endphp

                                    <div
                                        class="d-flex align-items-center justify-content-between rounded-3 border border-gray-300 p-4">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-35px me-3">
                                                <div class="symbol-label bg-light-primary">
                                                    <i class="bi bi-calendar-day text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="fw-semibold text-gray-900">
                                                {{ $dayLabel }}
                                            </div>
                                        </div>
                                        @if ($hours?->is_closed)
                                            <span class="badge badge-light-danger px-3 py-2">
                                                Closed
                                            </span>
                                        @else
                                            <span class="badge badge-light-success px-3 py-2">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ $hours?->opens_at?->format('H:i') ?? '-' }}
                                                -
                                                {{ $hours?->closes_at?->format('H:i') ?? '-' }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endcan
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-8">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-calendar-event text-warning fs-2"></i>
                                </div>
                            </div>
                            <div>
                                <h2 class="fw-bold text-gray-900 mb-1">
                                    Special Hours & Holidays
                                </h2>
                                <div class="text-muted fs-8">
                                    Override regular hours for specific dates.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-4">
                        @can('manageHours', $branch)
                            <form method="POST" action="{{ route('branches.special-hours.store', $branch) }}"
                                class="rounded-4 bg-light p-5 mb-7">
                                @csrf
                                <div class="row g-4 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label required">
                                            Date
                                        </label>
                                        <input type="date" name="date" value="{{ old('date') }}"
                                            class="form-control bg-white" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">
                                            Label
                                        </label>
                                        <input type="text" name="label" value="{{ old('label') }}"
                                            class="form-control bg-white" placeholder="Holiday">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">
                                            Opens
                                        </label>
                                        <input type="time" name="opens_at" value="{{ old('opens_at') }}"
                                            class="form-control bg-white">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">
                                            Closes
                                        </label>
                                        <input type="time" name="closes_at" value="{{ old('closes_at') }}"
                                            class="form-control bg-white">
                                    </div>

                                    <div class="col-md-2">
                                        <label
                                            class="d-flex align-items-center justify-content-between rounded-3 bg-white p-3">
                                            <span class="fw-semibold">
                                                Closed
                                            </span>
                                            <div class="form-check form-switch form-check-custom form-check-solid">
                                                <input class="form-check-input" type="checkbox" name="is_closed"
                                                    value="1" @checked(old('is_closed'))>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">
                                            Note
                                        </label>
                                        <textarea name="note" class="form-control bg-white" rows="2" placeholder="Optional note">{{ old('note') }}</textarea>
                                    </div>

                                    <div class="col-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-light-primary">
                                            <i class="bi bi-plus-circle me-1"></i>
                                            Save Special Hours
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endcan

                        @forelse ($branch->specialHours as $specialHour)
                            <div class="py-5 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">
                                <div
                                    class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                                    <div class="d-flex align-items-start">
                                        <div class="symbol symbol-45px me-4 flex-shrink-0">
                                            <div
                                                class="symbol-label {{ $specialHour->is_closed ? 'bg-light-danger' : 'bg-light-warning' }}">
                                                <i
                                                    class="bi {{ $specialHour->is_closed ? 'bi-calendar-x text-danger' : 'bi-calendar-event text-warning' }}"></i>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                <div class="fw-bold text-gray-900 fs-6">
                                                    {{ $specialHour->date->format('d M Y') }}
                                                </div>
                                                @if ($specialHour->label)
                                                    <span class="badge badge-light">
                                                        {{ $specialHour->label }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="text-muted fs-8">
                                                @if ($specialHour->is_closed)
                                                    Closed all day
                                                @else
                                                    {{ $specialHour->opens_at?->format('H:i') }}
                                                    -
                                                    {{ $specialHour->closes_at?->format('H:i') }}
                                                @endif

                                                @if ($specialHour->note)
                                                    <span class="mx-1">•</span>
                                                    {{ $specialHour->note }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @can('manageHours', $branch)
                                        <form method="POST"
                                            action="{{ route('branches.special-hours.destroy', [$branch, $specialHour]) }}"
                                            data-swal-confirm
                                            data-swal-title="Remove special hours?"
                                            data-swal-text="This override for {{ $specialHour->date->format('d M Y') }} will be deleted."
                                            data-swal-icon="warning"
                                            data-swal-confirm-button="Yes, remove"
                                            data-swal-cancel-button="Cancel">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger">
                                                <i class="bi bi-trash me-1"></i>
                                                Remove
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light">
                                        <i class="bi bi-calendar-check text-muted fs-1"></i>
                                    </div>
                                </div>
                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No Special Hours
                                </h4>
                                <div class="text-muted fs-7">
                                    No upcoming holiday or override schedules have been configured.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title">
                            <div class="symbol symbol-45px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-people text-primary fs-2"></i>
                                </div>
                            </div>

                            <div>
                                <h2 class="fw-bold text-gray-900 mb-1">
                                    Assigned Users
                                </h2>
                                <div class="text-muted fs-8">
                                    Users currently allowed to access this branch.
                                </div>
                            </div>
                        </div>

                        <div class="card-toolbar">
                            <span class="badge badge-light-primary px-3 py-2">
                                {{ $branch->users->count() }}
                                {{ Str::plural('User', $branch->users->count()) }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body pt-4">
                        @if ($branch->users->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-5">
                                    <thead>
                                        <tr class="text-muted fw-bold fs-8 text-uppercase">
                                            <th class="min-w-220px">
                                                User
                                            </th>
                                            <th class="min-w-160px">
                                                Role
                                            </th>
                                            <th class="min-w-120px">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-700">
                                        @foreach ($branch->users as $assignedUser)
                                            <tr>
                                                {{-- User --}}
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-45px me-4">
                                                            <div
                                                                class="symbol-label bg-light-primary fw-bold text-primary">
                                                                {{ strtoupper(substr($assignedUser->name, 0, 1)) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-gray-900 mb-1">
                                                                {{ $assignedUser->name }}
                                                            </div>
                                                            <div class="text-muted fs-8">
                                                                {{ $assignedUser->email }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                {{-- Role --}}
                                                <td>
                                                    @forelse ($assignedUser->roles as $role)
                                                        <span class="badge badge-light-primary me-1 mb-1">
                                                            {{ $role->name }}
                                                        </span>
                                                    @empty
                                                        <span class="text-muted">
                                                            —
                                                        </span>
                                                    @endforelse
                                                </td>
                                                {{-- Status --}}
                                                <td>
                                                    @php
                                                        $userActive = ($assignedUser->status ?? 'active') === 'active';
                                                    @endphp
                                                    <span
                                                        class="badge badge-light-{{ $userActive ? 'success' : 'danger' }} px-3 py-2">
                                                        <i class="bi bi-circle-fill fs-9 me-2"></i>
                                                        {{ str($assignedUser->status ?? 'active')->headline() }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="symbol symbol-70px mb-5">
                                    <div class="symbol-label bg-light">
                                        <i class="bi bi-people text-muted fs-1"></i>
                                    </div>
                                </div>
                                <h4 class="fw-bold text-gray-900 mb-2">
                                    No Users Assigned
                                </h4>
                                <div class="text-muted fs-7">
                                    Assign managers or staff to this branch from the edit page.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('pages.apps.branch-management.branches._sweet-alerts')
</x-default-layout>
