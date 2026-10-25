{{-- Usage: @include('partials.reports._export-buttons', ['route' => 'route.name', 'params' => [...]]) --}}
@php
    $exportParams = array_filter(array_merge(request()->except('page', 'format'), $params ?? []), fn ($value) => $value !== null && $value !== '');
@endphp
<div class="btn-group" role="group" aria-label="Export">
    <button type="button" class="btn btn-sm btn-light-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-download me-1"></i>Export
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="{{ route($route, $exportParams + ['format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel text-success me-2"></i>Excel (.xlsx)</a></li>
        <li><a class="dropdown-item" href="{{ route($route, $exportParams + ['format' => 'pdf']) }}"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>PDF</a></li>
        <li><a class="dropdown-item" href="{{ route($route, $exportParams + ['format' => 'csv']) }}"><i class="bi bi-filetype-csv text-primary me-2"></i>CSV</a></li>
    </ul>
</div>
