{{-- One tip line. $index is numeric for re-rendered lines and "__INDEX__" inside the JS template. --}}
<div class="d-flex gap-2 mb-3" data-tip-line>
    <select name="tips[{{ $index }}][staff_id]" class="form-select form-select-solid form-select-sm @error("tips.{$index}.staff_id") is-invalid @enderror" aria-label="Tip for">
        <option value="">Tip for...</option>
        @foreach ($tipStaff as $member)
            <option value="{{ $member->id }}" @selected((string) ($row['staff_id'] ?? '') === (string) $member->id)>{{ $member->full_name }}</option>
        @endforeach
    </select>
    <input type="number" name="tips[{{ $index }}][amount]" min="0" step="0.01" value="{{ $row['amount'] ?? '' }}" placeholder="LKR"
        class="form-control form-control-solid form-control-sm tip-line-amount @error("tips.{$index}.amount") is-invalid @enderror" style="max-width: 110px;" aria-label="Tip amount">
    <button type="button" class="btn btn-sm btn-icon btn-light-danger tip-line-remove" aria-label="Remove tip">
        <i class="bi bi-x-lg"></i>
    </button>
</div>
@error("tips.{$index}.staff_id")
    <div class="text-danger fs-8 mb-2">{{ $message }}</div>
@enderror
