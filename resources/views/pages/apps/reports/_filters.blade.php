{{-- Period + branch filter used by the business reports. Expects $from, $to and optionally $branches/$selectedBranch. --}}
<form method="GET" class="d-flex flex-wrap align-items-end gap-3">
    <div>
        <label class="form-label fs-8 mb-1">From</label>
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-solid form-control-sm w-150px">
    </div>
    <div>
        <label class="form-label fs-8 mb-1">To</label>
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-solid form-control-sm w-150px">
    </div>
    @isset($branches)
        <div>
            <label class="form-label fs-8 mb-1">Branch</label>
            <select name="branch_id" class="form-select form-select-solid form-select-sm w-175px">
                <option value="">All branches</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected($selectedBranch?->id === $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
    @endisset
    <button class="btn btn-sm btn-light-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
    <div class="btn-group btn-group-sm">
        @php $tz = $from->getTimezone(); $today = \Carbon\CarbonImmutable::now($tz); @endphp
        <a class="btn btn-light" href="{{ request()->fullUrlWithQuery(['from' => $today->startOfMonth()->toDateString(), 'to' => $today->toDateString()]) }}">This month</a>
        <a class="btn btn-light" href="{{ request()->fullUrlWithQuery(['from' => $today->subMonthNoOverflow()->startOfMonth()->toDateString(), 'to' => $today->subMonthNoOverflow()->endOfMonth()->toDateString()]) }}">Last month</a>
        <a class="btn btn-light" href="{{ request()->fullUrlWithQuery(['from' => $today->startOfYear()->toDateString(), 'to' => $today->toDateString()]) }}">This year</a>
    </div>
</form>
