<x-default-layout>

    @section('title')
        Add Plan
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('plan-management.plans.create') }}
    @endsection

    <div id="kt_app_content_container" class="app-container container-xxl">
        <form method="POST" action="{{ route('plan-management.plans.store') }}">
            @csrf

            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <h2 class="fw-bold mb-0">Add Subscription Plan</h2>
                    </div>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="row g-6 mb-8">
                        <div class="col-md-6">
                            <label class="form-label required">Plan name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug') }}" class="form-control" placeholder="Auto generated when empty">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label required">Price</label>
                            <input type="number" name="price" value="{{ old('price', 0) }}" class="form-control" min="0" step="0.01" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label required">Billing period</label>
                            <select name="billing_period" class="form-select" required>
                                @foreach (['trial' => 'Trial', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('billing_period', 'monthly') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Trial days</label>
                            <input type="number" name="trial_days" value="{{ old('trial_days', 0) }}" class="form-control" min="0">
                        </div>
                    </div>

                    <div class="row g-6 mb-8">
                        <div class="col-md-3">
                            <label class="form-label">Max branches</label>
                            <input type="number" name="max_branches" value="{{ old('max_branches') }}" class="form-control" min="1" placeholder="Unlimited">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Max staff</label>
                            <input type="number" name="max_staff" value="{{ old('max_staff') }}" class="form-control" min="1" placeholder="Unlimited">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Max users</label>
                            <input type="number" name="max_users" value="{{ old('max_users') }}" class="form-control" min="1" placeholder="Unlimited">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Max customers</label>
                            <input type="number" name="max_customers" value="{{ old('max_customers') }}" class="form-control" min="1" placeholder="Unlimited">
                        </div>
                    </div>

                    <div class="mb-8">
                        <label class="form-label">Features</label>

                        <div class="row g-4">
                            @foreach ($featureOptions as $key => $label)
                                <div class="col-md-4">
                                    <label class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="{{ $key }}" @checked(in_array($key, old('features', []), true))>
                                        <span class="form-check-label">{{ $label }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="row g-6">
                        <div class="col-md-4">
                            <label class="form-label">Sort order</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control" min="0">
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_recommended" value="1" @checked(old('is_recommended'))>
                                <span class="form-check-label">Recommended</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end gap-3">
                    <a href="{{ route('plan-management.plans.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Plan</button>
                </div>
            </div>
        </form>
    </div>

</x-default-layout>
