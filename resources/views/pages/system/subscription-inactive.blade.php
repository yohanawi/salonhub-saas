<x-system-layout>

    <!--begin::Title-->
    <h1 class="fw-bolder fs-2qx text-gray-900 mb-4">
        Subscription Inactive
    </h1>
    <!--end::Title-->

    <!--begin::Text-->
    <div class="fw-semibold fs-6 text-gray-500 mb-7">
        {{ $reason }}
    </div>
    <!--end::Text-->

    <!--begin::Details-->
    <div class="d-flex flex-column gap-2 fs-6 text-gray-700 mb-10">
        <div>Salon: <span class="fw-bold text-gray-900">{{ $tenant->name }}</span></div>
        @if ($subscription?->plan)
            <div>Plan: <span class="fw-bold text-gray-900">{{ $subscription->plan->name }}</span></div>
        @endif
        @if ($subscription?->status === \App\Models\Subscription::STATUS_TRIALING && $subscription->trial_ends_at)
            <div>Trial ended: <span class="fw-bold text-gray-900">{{ $subscription->trial_ends_at->format('d M Y') }}</span></div>
        @elseif ($subscription?->ends_at)
            <div>Ended: <span class="fw-bold text-gray-900">{{ $subscription->ends_at->format('d M Y') }}</span></div>
        @endif
    </div>
    <!--end::Details-->

    <!--begin::Actions-->
    <div class="d-flex flex-center gap-3">
        <a href="{{ route('profile.billing') }}" class="btn btn-sm btn-light">View Billing</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary">Sign Out</button>
        </form>
    </div>
    <!--end::Actions-->

</x-system-layout>
