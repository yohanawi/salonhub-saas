{{-- "Paid from cash drawer" switch for an expense payment; shown only while a cash method is selected. --}}
<div class="mb-5" data-drawer-toggle style="display: none;">
    <input type="hidden" name="from_cash_drawer" value="0">
    <label class="form-check form-switch form-check-custom form-check-solid">
        <input class="form-check-input" type="checkbox" name="from_cash_drawer" value="1" @checked(old('from_cash_drawer', '1') === '1')>
        <span class="form-check-label fw-semibold text-gray-700">Paid from the cash register drawer</span>
    </label>
    <div class="text-muted fs-8 mt-1">Leave on when the cash is taken from the counter, so the shift's expected cash is reduced.</div>
    @error('from_cash_drawer')
        <div class="text-danger fs-8 mt-1">{{ $message }}</div>
    @enderror
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('[data-drawer-method]').forEach(function(select) {
                    const toggle = select.closest('form')?.querySelector('[data-drawer-toggle]');

                    function refresh() {
                        const isCash = select.selectedOptions[0]?.dataset.type === 'cash';
                        toggle.style.display = isCash ? '' : 'none';
                        toggle.querySelector('input[type="checkbox"]').disabled = ! isCash;
                    }

                    select.addEventListener('change', refresh);
                    refresh();
                });
            });
        </script>
    @endpush
@endonce
