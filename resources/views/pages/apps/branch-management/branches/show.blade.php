<x-default-layout>

    @section('title')
        Branch Details
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('branches.show', $branch) }}
    @endsection

    <div id="kt_app_content_container">

        {{-- Alerts --}}
        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center mb-7">
                <i class="bi bi-check-circle-fill fs-2 me-3"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger d-flex align-items-center mb-7">
                <i class="bi bi-exclamation-circle-fill fs-2 me-3"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- Hero Header --}}
        <div class="card border-0 shadow-sm mb-7 overflow-hidden">
            <div class="card-body p-7">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-6">
                    <div class="d-flex align-items-start gap-4">
                        <div class="symbol symbol-70px">
                            <div class="symbol-label bg-light-primary rounded-4">
                                <i class="bi bi-shop-window fs-1 text-primary"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <h1 class="fw-bold text-gray-900 mb-0">
                                    {{ $branch->name }}
                                </h1>
                                @if ($branch->is_main)
                                    <span class="badge badge-light-warning">
                                        <i class="bi bi-star-fill me-1"></i>
                                        Main Branch
                                    </span>
                                @endif
                                @if ($branch->trashed())
                                    <span class="badge badge-light-danger">
                                        Archived
                                    </span>
                                @endif
                                @php
                                    $statusClass = match ($branch->status) {
                                        \App\Models\Branch::STATUS_ACTIVE => 'badge-light-success',
                                        \App\Models\Branch::STATUS_TEMPORARILY_CLOSED => 'badge-light-warning',
                                        default => 'badge-light-danger',
                                    };
                                @endphp
                                <span class="badge {{ $branch->trashed() ? 'badge-light-danger' : $statusClass }}">
                                    {{ $branch->trashed() ? 'Archived' : $branch->status_label }}
                                </span>
                            </div>
                            <div class="text-muted fw-semibold mb-2">
                                {{ $branch->code }}
                            </div>
                            <div class="d-flex align-items-center text-gray-700">
                                <i class="bi bi-geo-alt me-2 text-muted"></i>
                                {{ $branch->address_summary ?: 'No location details added.' }}
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        @if ($branch->trashed())
                            @can('restore', $branch)
                                <form method="POST" action="{{ route('branches.restore', $branch) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-light-success">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                                        Restore Branch
                                    </button>
                                </form>
                            @endcan
                        @else
                            <form method="POST" action="{{ route('branches.switch', $branch) }}">
                                @csrf
                                <button type="submit" class="btn btn-light-primary">
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    Switch Here
                                </button>
                            </form>

                            @can('update', $branch)
                                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-primary">
                                    <i class="bi bi-pencil-square me-1"></i>
                                    Edit Branch
                                </a>
                            @endcan

                            @can('viewReports', $branch)
                                <a href="{{ route('branches.reports.show', $branch) }}" class="btn btn-light-info">
                                    <i class="bi bi-bar-chart me-1"></i>
                                    Reports
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="row g-5 mb-7">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-people fs-2 text-primary"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7">
                                Assigned Users
                            </div>
                            <div class="fw-bold fs-2 text-gray-900">
                                {{ $branch->users->count() }}
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
                                <i class="bi bi-clock fs-2 text-success"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7">
                                Operating Days
                            </div>
                            <div class="fw-bold fs-2 text-gray-900">
                                {{ $branch->businessHours->where('is_closed', false)->count() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-info">
                                <i class="bi bi-cash-stack fs-2 text-info"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7">
                                Currency
                            </div>
                            <div class="fw-bold fs-2 text-gray-900">
                                {{ $branch->currency }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-7">
            {{-- LEFT SIDEBAR --}}
            <div class="col-xl-4">
                <div class="position-sticky" style="top: 100px;">
                    {{-- Contact --}}
                    <div class="card border-0 shadow-sm mb-6">
                        <div class="card-header border-0 pt-7">
                            <div class="card-title d-flex align-items-center gap-3">
                                <div class="symbol symbol-40px">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="bi bi-person-lines-fill text-primary"></i>
                                    </div>
                                </div>
                                <h2 class="fw-bold mb-0">
                                    Contact Information
                                </h2>
                            </div>
                        </div>

                        <div class="card-body pt-3">
                            <div class="d-flex flex-column gap-5">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-telephone fs-4 text-muted me-4"></i>
                                    <div>
                                        <div class="text-muted fs-8">
                                            Phone
                                        </div>
                                        <div class="fw-semibold text-gray-900">
                                            {{ $branch->phone ?: 'Not provided' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center">
                                    <i class="bi bi-envelope fs-4 text-muted me-4"></i>
                                    <div>
                                        <div class="text-muted fs-8">
                                            Email
                                        </div>
                                        <div class="fw-semibold text-gray-900">
                                            {{ $branch->email ?: 'Not provided' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center">
                                    <i class="bi bi-clock-history fs-4 text-muted me-4"></i>
                                    <div>
                                        <div class="text-muted fs-8">
                                            Timezone
                                        </div>
                                        <div class="fw-semibold text-gray-900">
                                            {{ $branch->timezone }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Billing --}}
                    <div class="card border-0 shadow-sm mb-6">
                        <div class="card-header border-0 pt-7">
                            <div class="card-title d-flex align-items-center gap-3">
                                <div class="symbol symbol-40px">
                                    <div class="symbol-label bg-light-info">
                                        <i class="bi bi-receipt text-info"></i>
                                    </div>
                                </div>
                                <h2 class="fw-bold mb-0">
                                    Billing Settings
                                </h2>
                            </div>
                        </div>

                        <div class="card-body pt-3">
                            <div class="d-flex flex-column gap-5">
                                <div>
                                    <div class="text-muted fs-8 mb-1">
                                        Invoice Prefix
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ $branch->invoice_prefix ?: 'Not set' }}
                                    </span>
                                </div>

                                <div>
                                    <div class="text-muted fs-8 mb-1">
                                        Tax
                                    </div>
                                    @if ($branch->tax_enabled)
                                        <div class="fw-semibold text-gray-900">
                                            {{ $branch->tax_name }}
                                            {{ rtrim(rtrim(number_format((float) $branch->tax_rate, 4), '0'), '.') }}%
                                        </div>
                                    @else
                                        <span class="badge badge-light">
                                            Disabled
                                        </span>
                                    @endif
                                </div>

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
                    </div>

                    {{-- Status --}}
                    @can('changeStatus', $branch)
                        <div class="card border-0 shadow-sm">
                            <div class="card-header border-0 pt-7">
                                <div class="card-title d-flex align-items-center gap-3">
                                    <div class="symbol symbol-40px">
                                        <div class="symbol-label bg-light-warning">
                                            <i class="bi bi-toggle-on text-warning"></i>
                                        </div>
                                    </div>
                                    <h2 class="fw-bold mb-0">
                                        Branch Status
                                    </h2>
                                </div>
                            </div>

                            <div class="card-body pt-3">
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
                                        <div class="alert alert-warning py-4 mb-5">
                                            <div class="fw-semibold mb-1">
                                                Main Branch
                                            </div>
                                            <div class="fs-8">
                                                Select another branch before making this branch unavailable.
                                            </div>
                                        </div>

                                        <div class="mb-5">
                                            <label class="form-label fw-semibold">
                                                Replacement Main Branch
                                            </label>
                                            <select name="replacement_main_branch_id" class="form-select">
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
                        <div class="card border-0 shadow-sm mt-6">
                            <div class="card-header border-0 pt-7">
                                <div class="card-title d-flex align-items-center gap-3">
                                    <div class="symbol symbol-40px">
                                        <div class="symbol-label bg-light-danger">
                                            <i class="bi bi-archive text-danger"></i>
                                        </div>
                                    </div>
                                    <h2 class="fw-bold mb-0">
                                        Archive Branch
                                    </h2>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <form method="POST" action="{{ route('branches.archive', $branch) }}">
                                    @csrf
                                    @method('DELETE')

                                    @if ($branch->is_main)
                                        <label class="form-label fw-semibold">
                                            Replacement Main Branch
                                        </label>
                                        <select name="replacement_main_branch_id" class="form-select mb-4" required>
                                            <option value="">
                                                Select branch
                                            </option>
                                            @foreach ($replacementBranches as $replacementBranch)
                                                <option value="{{ $replacementBranch->id }}">
                                                    {{ $replacementBranch->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif

                                    <button type="submit" class="btn btn-light-danger w-100">
                                        <i class="bi bi-archive me-1"></i>
                                        Archive Branch
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>

            {{-- RIGHT CONTENT --}}
            <div class="col-xl-8">
                {{-- Business Hours --}}
                <div class="card border-0 shadow-sm mb-7">
                    <div class="card-header border-0 pt-7">
                        <div class="card-title d-flex align-items-center gap-3">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-success">
                                    <i class="bi bi-calendar-week fs-2 text-success"></i>
                                </div>
                            </div>
                            <div>
                                <h2 class="fw-bold mb-1">
                                    Business Hours
                                </h2>
                                <div class="text-muted fs-8">
                                    Configure weekly opening and closing hours.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-3">
                        @can('manageHours', $branch)
                            <form method="POST" action="{{ route('branches.hours.update', $branch) }}">
                                @csrf
                                @method('PUT')
                                <div class="d-flex flex-column gap-3">
                                    @foreach (\App\Models\Branch::DAY_LABELS as $dayNumber => $dayLabel)
                                        @php
                                            $hours = $branch->businessHours->firstWhere('day_of_week', $dayNumber);
                                        @endphp
                                        <div class="business-hour-row rounded border p-4">
                                            <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]"
                                                value="{{ $dayNumber }}">
                                            <div class="row align-items-center g-4">
                                                <div class="col-md-3">
                                                    <div class="fw-bold text-gray-900">
                                                        {{ $dayLabel }}
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="text-muted fs-8 mb-1">
                                                        Opens
                                                    </label>
                                                    <input type="time" name="hours[{{ $loop->index }}][opens_at]"
                                                        value="{{ old("hours.{$loop->index}.opens_at", $hours?->opens_at?->format('H:i')) }}"
                                                        class="form-control">
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="text-muted fs-8 mb-1"> Closes
                                                    </label>
                                                    <input type="time" name="hours[{{ $loop->index }}][closes_at]"
                                                        value="{{ old("hours.{$loop->index}.closes_at", $hours?->closes_at?->format('H:i')) }}"
                                                        class="form-control">
                                                </div>

                                                <div class="col-md-3">
                                                    <label
                                                        class="form-check form-switch form-check-custom form-check-solid mt-5">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="hours[{{ $loop->index }}][is_closed]" value="1"
                                                            @checked(old("hours.{$loop->index}.is_closed", $hours?->is_closed))>
                                                        <span class="form-check-label fw-semibold">
                                                            Closed
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="d-flex justify-content-end mt-6">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-1"></i>
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
                                    <div class="d-flex align-items-center justify-content-between border rounded p-4">
                                        <div class="fw-semibold text-gray-900">
                                            {{ $dayLabel }}
                                        </div>
                                        @if ($hours?->is_closed)
                                            <span class="badge badge-light-danger">
                                                Closed
                                            </span>
                                        @else
                                            <span class="fw-semibold text-gray-700">
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

                {{-- Assigned Users --}}
                <div class="card border-0 shadow-sm mb-7">
                    <div class="card-header border-0 pt-7">
                        <div class="card-title d-flex align-items-center gap-3">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-warning">
                                    <i class="bi bi-calendar-event fs-2 text-warning"></i>
                                </div>
                            </div>
                            <div>
                                <h2 class="fw-bold mb-1">
                                    Special Hours & Holidays
                                </h2>
                                <div class="text-muted fs-8">
                                    Override normal hours for holidays, closures or one-off schedules.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-3">
                        @can('manageHours', $branch)
                            <form method="POST" action="{{ route('branches.special-hours.store', $branch) }}" class="border rounded p-5 mb-6">
                                @csrf
                                <div class="row g-4 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label required">Date</label>
                                        <input type="date" name="date" value="{{ old('date') }}" class="form-control" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Label</label>
                                        <input type="text" name="label" value="{{ old('label') }}" class="form-control" placeholder="Holiday">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Opens</label>
                                        <input type="time" name="opens_at" value="{{ old('opens_at') }}" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Closes</label>
                                        <input type="time" name="closes_at" value="{{ old('closes_at') }}" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-check form-switch form-check-custom form-check-solid mb-3">
                                            <input class="form-check-input" type="checkbox" name="is_closed" value="1" @checked(old('is_closed'))>
                                            <span class="form-check-label">Closed</span>
                                        </label>
                                    </div>
                                    <div class="col-12">
                                        <textarea name="note" class="form-control" rows="2" placeholder="Optional note">{{ old('note') }}</textarea>
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

                        <div class="d-flex flex-column gap-3">
                            @forelse ($branch->specialHours as $specialHour)
                                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 border rounded p-4">
                                    <div>
                                        <div class="fw-bold text-gray-900">
                                            {{ $specialHour->date->format('d M Y') }}
                                            @if ($specialHour->label)
                                                <span class="badge badge-light ms-2">{{ $specialHour->label }}</span>
                                            @endif
                                        </div>
                                        <div class="text-muted fs-7">
                                            @if ($specialHour->is_closed)
                                                Closed all day
                                            @else
                                                {{ $specialHour->opens_at?->format('H:i') }} - {{ $specialHour->closes_at?->format('H:i') }}
                                            @endif
                                            @if ($specialHour->note)
                                                · {{ $specialHour->note }}
                                            @endif
                                        </div>
                                    </div>

                                    @can('manageHours', $branch)
                                        <form method="POST" action="{{ route('branches.special-hours.destroy', [$branch, $specialHour]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger">
                                                <i class="bi bi-trash me-1"></i>
                                                Remove
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            @empty
                                <div class="text-center text-muted py-8">
                                    No upcoming special hours.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Assigned Users --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-7">
                        <div class="card-title d-flex align-items-center gap-3">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-primary">
                                    <i class="bi bi-people fs-2 text-primary"></i>
                                </div>
                            </div>
                            <div>
                                <h2 class="fw-bold mb-1">
                                    Assigned Users
                                </h2>
                                <div class="text-muted fs-8">
                                    Users currently assigned to this branch.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-5">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($branch->users as $assignedUser)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="symbol symbol-45px me-4">
                                                        <div
                                                            class="symbol-label bg-light-primary fw-bold text-primary">
                                                            {{ strtoupper(substr($assignedUser->name, 0, 1)) }}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="fw-bold text-gray-900">
                                                            {{ $assignedUser->name }}
                                                        </div>
                                                        <div class="text-muted fs-7">
                                                            {{ $assignedUser->email }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                @forelse ($assignedUser->roles as $role)
                                                    <span class="badge badge-light-primary me-1">
                                                        {{ $role->name }}
                                                    </span>
                                                @empty
                                                    <span class="text-muted">
                                                        -
                                                    </span>
                                                @endforelse
                                            </td>

                                            <td>
                                                <span
                                                    class="badge {{ ($assignedUser->status ?? 'active') === 'active' ? 'badge-light-success' : 'badge-light-danger' }}">
                                                    <span class="bullet bullet-dot me-2"></span>
                                                    {{ str($assignedUser->status ?? 'active')->headline() }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-12">
                                                <i class="bi bi-people fs-1 text-muted"></i>
                                                <div class="fw-semibold text-gray-800 mt-4">
                                                    No users assigned
                                                </div>
                                                <div class="text-muted fs-7 mt-1">
                                                    Assign managers or staff to this branch from the edit page.
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .business-hour-row {
                transition: all .2s ease;
                background: var(--bs-body-bg);
            }

            .business-hour-row:hover {
                border-color: var(--bs-primary) !important;
                background: var(--bs-gray-100);
            }

            .card {
                border-radius: 14px;
            }

            .symbol-label {
                border-radius: 12px;
            }
        </style>
    @endpush

</x-default-layout>
