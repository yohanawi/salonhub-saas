@if (session('status'))
    <div class="alert alert-success border-0 shadow-sm mb-8">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-8">
        <div class="fw-bold text-gray-900 mb-1">Please check the form</div>
        <div class="text-gray-700">{{ $errors->first() }}</div>
    </div>
@endif
