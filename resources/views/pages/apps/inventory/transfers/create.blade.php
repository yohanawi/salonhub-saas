<x-default-layout>
    @section('title') New Stock Transfer @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('inventory.transfers.create') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.inventory.partials._alerts')

        <form method="POST" action="{{ route('inventory.transfers.store') }}">
            @csrf
            <div class="row g-5">
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title"><h3 class="fw-bold mb-0">Products</h3></div>
                            <div class="card-toolbar"><button type="button" id="transfer_add" class="btn btn-sm btn-light-primary"><i class="bi bi-plus-lg me-1"></i>Add Product</button></div>
                        </div>
                        <div class="card-body pt-2">
                            <table class="table align-middle gy-3">
                                <thead>
                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                        <th>Product</th>
                                        <th class="w-125px text-center">In stock</th>
                                        <th class="w-125px">Quantity</th>
                                        <th class="w-50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="transfer_rows"></tbody>
                            </table>
                            <template id="transfer_template">
                                <tr>
                                    <td>
                                        <select name="items[__INDEX__][product_id]" class="form-select form-select-solid form-select-sm transfer-product">
                                            <option value="">Select product</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? " ({$product->sku})" : '' }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center transfer-stock text-muted">—</td>
                                    <td><input type="number" min="1" name="items[__INDEX__][quantity]" value="1" class="form-control form-control-solid form-control-sm"></td>
                                    <td><button type="button" class="btn btn-sm btn-icon btn-light-danger transfer-remove"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="mb-5">
                                <label class="form-label required">From Branch</label>
                                <select name="from_branch_id" id="transfer_from" class="form-select form-select-solid" required>
                                    @foreach ($fromBranches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('from_branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-5">
                                <label class="form-label required">To Branch</label>
                                <select name="to_branch_id" class="form-select form-select-solid" required>
                                    <option value="">Select branch</option>
                                    @foreach ($toBranches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('to_branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="3" class="form-control form-control-solid">{{ old('notes') }}</textarea>
                            </div>
                            <label class="form-check form-check-custom form-check-solid mb-6">
                                <input class="form-check-input" type="checkbox" name="send_now" value="1" checked>
                                <span class="form-check-label">Send now (take stock out of the sending branch)</span>
                            </label>
                            <button class="btn btn-primary w-100"><i class="bi bi-arrow-left-right me-1"></i>Create Transfer</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const stock = @json($stock);
                const rows = document.getElementById('transfer_rows');
                const template = document.getElementById('transfer_template');
                const from = document.getElementById('transfer_from');
                let index = 0;

                const refreshStock = () => rows.querySelectorAll('tr').forEach(function(row) {
                    const productId = row.querySelector('.transfer-product').value;
                    const onHand = productId ? ((stock[from.value] || {})[productId] ?? 0) : null;
                    row.querySelector('.transfer-stock').textContent = onHand === null ? '—' : onHand;
                });

                const addRow = () => {
                    rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index++));
                };

                document.getElementById('transfer_add').addEventListener('click', addRow);
                rows.addEventListener('click', (event) => event.target.closest('.transfer-remove')?.closest('tr').remove());
                rows.addEventListener('change', refreshStock);
                from.addEventListener('change', refreshStock);
                addRow();
            });
        </script>
    @endpush
</x-default-layout>
