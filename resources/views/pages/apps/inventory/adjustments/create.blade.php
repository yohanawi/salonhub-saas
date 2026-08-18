<x-default-layout>
    @section('title')
        New Stock Adjustment
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('inventory.adjustments.create') }}
    @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        <form method="POST" action="{{ route('inventory.adjustments.store') }}">
            @csrf
            <div class="card border-0 shadow-sm mb-8">
                <div class="card-header border-0 pt-7">
                    <h3 class="fw-bold mb-0">Adjustment Details</h3>
                </div>
                <div class="card-body">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <label class="form-label required fw-semibold">Branch</label>
                            <select name="branch_id" class="form-select form-select-solid" required>
                                <option value="">Select branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required fw-semibold">Reason</label>
                            <select name="reason" class="form-select form-select-solid" required>
                                @foreach ($reasons as $reason)
                                    <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ str($reason)->headline() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Notes</label>
                            <input name="notes" value="{{ old('notes') }}" class="form-control form-control-solid">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 py-6">
                    <h3 class="fw-bold mb-0">Products</h3>
                    <button type="button" id="add-adjustment-row" class="btn btn-light-primary btn-sm"><i class="bi bi-plus-lg me-2"></i>Add Row</button>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle gy-4" id="adjustment-items-table">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase">
                                    <th>Product</th>
                                    <th class="w-150px">Actual Qty</th>
                                    <th>Line Reason</th>
                                    <th class="w-70px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="items[0][product_id]" class="form-select form-select-solid" required>
                                            <option value="">Select product</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }} - {{ $product->sku }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" min="0" name="items[0][actual_quantity]" class="form-control form-control-solid" required></td>
                                    <td><input name="items[0][reason]" class="form-control form-control-solid"></td>
                                    <td><button type="button" class="btn btn-sm btn-icon btn-light-danger remove-row"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end gap-3 pt-6 border-top">
                        <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-light">Cancel</a>
                        <button class="btn btn-primary"><i class="bi bi-check2-circle me-2"></i>Post Adjustment</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const tableBody = document.querySelector('#adjustment-items-table tbody');
                const addButton = document.getElementById('add-adjustment-row');
                let index = 1;

                addButton?.addEventListener('click', function () {
                    const firstRow = tableBody.querySelector('tr');
                    const clone = firstRow.cloneNode(true);
                    clone.querySelectorAll('select, input').forEach(function (field) {
                        field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
                        field.value = '';
                    });
                    tableBody.appendChild(clone);
                    index++;
                });

                tableBody?.addEventListener('click', function (event) {
                    if (! event.target.closest('.remove-row')) {
                        return;
                    }

                    if (tableBody.querySelectorAll('tr').length > 1) {
                        event.target.closest('tr').remove();
                    }
                });
            });
        </script>
    @endpush
</x-default-layout>
