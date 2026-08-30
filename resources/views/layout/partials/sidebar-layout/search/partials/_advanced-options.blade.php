<form data-salon-search-advanced class="pt-1 d-none">
    <h3 class="fw-semibold text-gray-900 mb-7">Search Filters</h3>

    <div class="mb-7">
        <label class="form-label fw-semibold">Result type</label>
        <select class="form-select form-select-solid" data-salon-search-type>
            <option value="">Everything</option>
            @can('customer.view')
                <option value="customers">Customers</option>
            @endcan
            @can('appointments.view')
                <option value="appointments">Appointments</option>
            @endcan
            @can('staff.view')
                <option value="staff">Staff</option>
            @endcan
            @can('services.view')
                <option value="services">Services</option>
            @endcan
            @can('billing.view')
                <option value="invoices">Invoices</option>
            @endcan
            @can('product.view')
                <option value="products">Products</option>
            @endcan
            @can('branches.view')
                <option value="branches">Branches</option>
            @endcan
            @can('expenses.view')
                <option value="expenses">Expenses</option>
            @endcan
            @can('promotions.view')
                <option value="promotions">Promotions</option>
            @endcan
            @can('memberships.view')
                <option value="memberships">Memberships</option>
            @endcan
            @if (auth()->user()?->hasAnyRole(['Super Admin', 'Salon Owner', 'Salon Admin']))
                <option value="users">Users</option>
            @endif
            @if (auth()->user()?->hasRole('Super Admin'))
                <option value="tenants">Salons</option>
            @endif
        </select>
    </div>

    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5 mb-7">
        {!! getIcon('information-5', 'fs-2tx text-primary me-4') !!}
        <div class="fw-semibold text-gray-700 fs-7">
            Results follow your role permissions, tenant access, and assigned branch access.
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="reset" class="btn btn-sm btn-light fw-bold btn-active-light-primary me-2"
            data-salon-search-advanced-cancel>Done</button>
    </div>
</form>
