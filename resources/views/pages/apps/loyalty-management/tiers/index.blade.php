<x-default-layout>
    @section('title') Loyalty Tiers @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('loyalty-management.tiers.index') }} @endsection

    <div id="kt_app_content_container">
        @include('pages/apps.loyalty-management.partials._alerts')

        @if (! $program)
            <div class="alert alert-warning border-0 shadow-sm">Create and activate a loyalty program first, then add tiers to it.</div>
        @else
            <div class="row g-5">
                @can('update', $program)
                    <div class="col-xl-4">
                        <form method="POST" action="{{ route('loyalty-management.tiers.store') }}" class="card border-0 shadow-sm">
                            @csrf
                            <div class="card-header border-0 pt-6">
                                <div class="card-title flex-column">
                                    <h3 class="fw-bold text-gray-900 mb-1"><i class="bi bi-award text-warning me-2"></i>Add Tier</h3>
                                    <div class="text-muted fs-8">Customers move up automatically as their lifetime points grow.</div>
                                </div>
                            </div>
                            <div class="card-body pt-2">
                                <div class="mb-5">
                                    <label class="form-label required">Name</label>
                                    <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-solid" placeholder="e.g. Gold" required>
                                </div>
                                <div class="row g-4 mb-5">
                                    <div class="col-6">
                                        <label class="form-label required">From (lifetime points)</label>
                                        <input type="number" min="0" name="min_points" value="{{ old('min_points', $tiers->isEmpty() ? 0 : '') }}" class="form-control form-control-solid" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label required">Points Multiplier</label>
                                        <input type="number" step="0.05" min="1" max="10" name="points_multiplier" value="{{ old('points_multiplier', 1) }}" class="form-control form-control-solid" required>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="form-label required">Badge Colour</label>
                                    <select name="color" class="form-select form-select-solid">
                                        @foreach ($colors as $color)
                                            <option value="{{ $color }}" @selected(old('color') === $color)>{{ ucfirst($color) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Benefits</label>
                                    <textarea name="benefits" rows="3" class="form-control form-control-solid" placeholder="Shown to customers on their rewards page">{{ old('benefits') }}</textarea>
                                </div>
                            </div>
                            <div class="card-footer"><button class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Add Tier</button></div>
                        </form>
                    </div>
                @endcan

                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title flex-column">
                                <h3 class="fw-bold text-gray-900 mb-1">{{ $program->name }} Tiers</h3>
                                <div class="text-muted fs-8">A 1.5× multiplier means Gold members earn 50% more points on every visit.</div>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            @forelse ($tiers as $tier)
                                <form method="POST" action="{{ route('loyalty-management.tiers.update', $tier) }}" class="border border-gray-200 rounded-3 p-4 mb-4">
                                    @csrf
                                    @method('PUT')
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge badge-{{ $tier->color }} fs-7 px-4 py-2">{{ $tier->name }}</span>
                                            <span class="text-muted fs-8">{{ number_format($tier->min_points) }}+ lifetime points · {{ rtrim(rtrim(number_format((float) $tier->points_multiplier, 2), '0'), '.') }}× points</span>
                                        </div>
                                        <span class="badge badge-light">{{ number_format($tier->accounts_count) }} {{ str('member')->plural($tier->accounts_count) }}</span>
                                    </div>
                                    @can('update', $program)
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-3"><label class="form-label fs-8">Name</label><input type="text" name="name" value="{{ $tier->name }}" class="form-control form-control-solid form-control-sm"></div>
                                            <div class="col-md-2"><label class="form-label fs-8">From</label><input type="number" min="0" name="min_points" value="{{ $tier->min_points }}" class="form-control form-control-solid form-control-sm"></div>
                                            <div class="col-md-2"><label class="form-label fs-8">Multiplier</label><input type="number" step="0.05" min="1" max="10" name="points_multiplier" value="{{ $tier->points_multiplier }}" class="form-control form-control-solid form-control-sm"></div>
                                            <div class="col-md-2">
                                                <label class="form-label fs-8">Colour</label>
                                                <select name="color" class="form-select form-select-solid form-select-sm">
                                                    @foreach ($colors as $color)
                                                        <option value="{{ $color }}" @selected($tier->color === $color)>{{ ucfirst($color) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3 d-flex gap-2">
                                                <button class="btn btn-sm btn-light-primary flex-grow-1">Save</button>
                                                <button type="submit" form="delete-tier-{{ $tier->id }}" class="btn btn-sm btn-icon btn-light-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                            </div>
                                            <div class="col-12"><input type="text" name="benefits" value="{{ $tier->benefits }}" class="form-control form-control-solid form-control-sm" placeholder="Benefits"></div>
                                        </div>
                                    @else
                                        <div class="text-muted fs-7">{{ $tier->benefits }}</div>
                                    @endcan
                                </form>
                                @can('update', $program)
                                    <form id="delete-tier-{{ $tier->id }}" method="POST" action="{{ route('loyalty-management.tiers.destroy', $tier) }}" onsubmit="return confirm('Delete the {{ $tier->name }} tier?');">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endcan
                            @empty
                                <div class="text-center text-muted py-15">No tiers yet. Start with a base tier from 0 points (e.g. Silver), then Gold and Platinum.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-default-layout>
