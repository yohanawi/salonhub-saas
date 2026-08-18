@if (session('status'))
    <div class="alert alert-success border-0 shadow-sm mb-8">{{ session('status') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger border-0 shadow-sm mb-8">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-8">
        <div class="fw-bold text-gray-900 mb-1">Something went wrong</div>
        <div>{{ $errors->first() }}</div>
    </div>
@endif
