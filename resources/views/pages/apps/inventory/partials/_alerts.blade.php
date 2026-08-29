@if (session('status'))
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-8">
        <div class="symbol symbol-45px me-4">
            <span class="symbol-label bg-success bg-opacity-10">
                <i class="bi bi-check-circle-fill text-success fs-2"></i>
            </span>
        </div>
        <div>
            <div class="fw-bold text-gray-900">Success</div>
            <div class="text-gray-700">{{ session('status') }}</div>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-8">
        <div class="symbol symbol-45px me-4">
            <span class="symbol-label bg-danger bg-opacity-10">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
            </span>
        </div>
        <div>
            <div class="fw-bold text-gray-900">Something went wrong</div>
            <div class="text-gray-700">{{ $errors->first() }}</div>
        </div>
    </div>
@endif
