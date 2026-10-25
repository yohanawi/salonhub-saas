<div class="mb-4">
    <label class="form-label required">Type</label>
    <select name="type" class="form-select form-select-solid">
        @foreach (\App\Models\StaffTimeOff::TYPES as $type)
            <option value="{{ $type }}" @selected(old('type', 'annual_leave') === $type)>{{ str($type)->replace('_', ' ')->headline() }}</option>
        @endforeach
    </select>
</div>
<div class="row g-3 mb-4">
    <div class="col-6">
        <label class="form-label required">From</label>
        <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control form-control-solid" required>
    </div>
    <div class="col-6">
        <label class="form-label">To</label>
        <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control form-control-solid">
    </div>
</div>
<label class="form-check form-check-custom form-check-solid mb-4">
    <input class="form-check-input" type="checkbox" name="half_day" value="1" @checked(old('half_day'))>
    <span class="form-check-label">Half day (morning)</span>
</label>
<div>
    <label class="form-label">Reason</label>
    <input type="text" name="reason" value="{{ old('reason') }}" class="form-control form-control-solid">
</div>
