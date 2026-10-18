{{--
    Tip lines shared by quick sale and appointment checkout. Expects $tipStaff (staff who can receive a
    tip). Tips are added to what the customer pays but are not part of the sale total; pages read the
    running tip total from window.posTips.total() and listen for the "pos:tips-changed" event.
--}}
@php
    $tipRows = collect(old('tips', []))->filter(fn ($row) => is_array($row))->values()->all();
@endphp

<div class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted">Tips <span class="fs-8">(go to staff payroll)</span></span>
        <button type="button" class="btn btn-sm btn-light-primary py-1 px-3" id="add-tip-line">
            <i class="bi bi-plus-lg me-1"></i>Add Tip
        </button>
    </div>

    <div id="tip-lines">
        @foreach ($tipRows as $index => $row)
            @include('pages.apps.billing._tip-line', ['index' => $index, 'row' => $row])
        @endforeach
    </div>

    <template id="tip-line-template">
        @include('pages.apps.billing._tip-line', ['index' => '__INDEX__', 'row' => []])
    </template>

    @error('tips')
        <div class="text-danger fs-8">{{ $message }}</div>
    @enderror

    <div class="d-flex justify-content-between" id="tip-total-wrap" style="display: none !important;">
        <span class="text-muted">Tip Total</span>
        <span class="fw-semibold" id="tip-total">LKR 0.00</span>
    </div>
</div>

@push('scripts')
    <script>
        window.posTips = (function() {
            const container = document.getElementById('tip-lines');
            const template = document.getElementById('tip-line-template');
            const totalWrap = document.getElementById('tip-total-wrap');
            let nextIndex = container.querySelectorAll('[data-tip-line]').length + 100;

            function total() {
                return Array.from(container.querySelectorAll('.tip-line-amount'))
                    .reduce((sum, input) => sum + Math.max(0, Number(input.value || 0)), 0);
            }

            function changed() {
                const value = total();
                totalWrap.style.setProperty('display', value > 0 ? 'flex' : 'none', 'important');
                document.getElementById('tip-total').textContent = 'LKR ' + value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.dispatchEvent(new CustomEvent('pos:tips-changed'));
            }

            document.getElementById('add-tip-line').addEventListener('click', function() {
                container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++));
                container.lastElementChild.querySelector('.tip-line-amount')?.focus();
                changed();
            });

            container.addEventListener('input', changed);
            container.addEventListener('click', function(event) {
                if (event.target.closest('.tip-line-remove')) {
                    event.target.closest('[data-tip-line]').remove();
                    changed();
                }
            });

            changed();

            return {total};
        })();
    </script>
@endpush
