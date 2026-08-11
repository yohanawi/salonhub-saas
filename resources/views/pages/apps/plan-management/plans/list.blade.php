<x-default-layout>

    @section('title')
        Plans
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('plan-management.plans.index') }}
    @endsection

    <div id="kt_app_content_container" class="app-container container-xxl">
        @if (session('status'))
            <div class="alert alert-success mb-6">{{ session('status') }}</div>
        @endif

        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h2 class="fw-bold mb-0">Subscription Plans</h2>
                </div>

                <div class="card-toolbar">
                    <a href="{{ route('plan-management.plans.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Add Plan
                    </a>
                </div>
            </div>

            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>Plan</th>
                                <th>Price</th>
                                <th>Limits</th>
                                <th>Features</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @foreach ($plans as $plan)
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-900 fw-bold">{{ $plan->name }}</span>
                                            <span class="text-muted fs-7">{{ $plan->slug }}</span>
                                            @if ($plan->is_recommended)
                                                <span class="badge badge-light-primary mt-2 w-fit-content">Recommended</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($plan->price > 0)
                                            LKR {{ number_format((float) $plan->price) }} / {{ $plan->billing_period }}
                                        @else
                                            Free {{ $plan->trial_days ? "({$plan->trial_days} days)" : '' }}
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge badge-light">Branches: {{ $plan->max_branches ?? 'Unlimited' }}</span>
                                            <span class="badge badge-light">Staff: {{ $plan->max_staff ?? 'Unlimited' }}</span>
                                            <span class="badge badge-light">Users: {{ $plan->max_users ?? 'Unlimited' }}</span>
                                            <span class="badge badge-light">Customers: {{ $plan->max_customers ? number_format($plan->max_customers) : 'Unlimited' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        {{ collect($plan->features ?? [])->filter(fn ($value) => $value !== false)->count() }} enabled
                                    </td>
                                    <td>
                                        <span class="badge {{ $plan->is_active ? 'badge-light-success' : 'badge-light-danger' }}">
                                            {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</x-default-layout>
