<form data-salon-search-form class="w-100 position-relative mb-3" autocomplete="off">
    {!! getIcon('magnifier', 'fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-0') !!}

    <input type="text" class="search-input form-control form-control-flush ps-10 pe-10" name="search" value=""
        placeholder="Search customers, bookings, invoices..." data-salon-search-input />

    <span class="search-spinner position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-1"
        data-salon-search-spinner>
        <span class="spinner-border h-15px w-15px align-middle text-gray-500"></span>
    </span>

    <button type="button"
        class="search-reset btn btn-flush btn-active-color-primary position-absolute top-50 end-0 translate-middle-y lh-0 d-none"
        data-salon-search-clear aria-label="Clear search">
        {!! getIcon('cross', 'fs-2 fs-lg-1 me-0') !!}
    </button>

    <div class="position-absolute top-50 end-0 translate-middle-y" data-salon-search-toolbar>
        <button type="button" data-salon-search-advanced-toggle
            class="btn btn-icon w-20px btn-sm btn-active-color-primary" data-bs-toggle="tooltip"
            title="Filter search results">
            {!! getIcon('filter-search', 'fs-2') !!}
        </button>
    </div>
</form>

<div class="separator border-gray-200 mb-6"></div>
