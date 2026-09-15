<x-default-layout>
    @include('pages.apps.profile.partials._profile-navbar')

    <!--begin::Billing Summary-->
    <div class="card mb-5 mb-xl-10">
        <!--begin::Card body-->
        <div class="card-body">
            @if (! $subscription)
                <!--begin::Notice-->
                <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-12 p-6">
                    <!--begin::Icon-->
                    <i class="ki-duotone ki-information fs-2tx text-warning me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <!--end::Icon-->
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-stack flex-grow-1">
                        <!--begin::Content-->
                        <div class="fw-semibold">
                            <h4 class="text-gray-900 fw-bold">No active subscription</h4>
                            <div class="fs-6 text-gray-700">This account does not have an active plan yet.</div>
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Notice-->
            @else
                <!--begin::Row-->
                <div class="row">
                    <!--begin::Col-->
                    <div class="col-lg-7">
                        <!--begin::Heading-->
                        <h3 class="mb-2">
                            {{ $subscription->ends_at ? 'Active until ' . $subscription->ends_at->format('M d, Y') : 'Active subscription' }}
                        </h3>
                        <p class="fs-6 text-gray-600 fw-semibold mb-6 mb-lg-15">We will send you a notification upon
                            Subscription expiration</p>
                        <!--end::Heading-->
                        <!--begin::Info-->
                        <div class="fs-5 mb-2">
                            <span class="text-gray-800 fw-bold me-1">${{ number_format((float) ($subscription->price ?? $subscription->plan?->price ?? 0), 2) }}</span>
                            <span class="text-gray-600 fw-semibold">Per {{ ucfirst($subscription->plan?->billing_period ?? 'Month') }}</span>
                        </div>
                        <!--end::Info-->
                        <!--begin::Notice-->
                        <div class="fs-6 text-gray-600 fw-semibold">
                            {{ $subscription->plan?->name ?? 'Plan' }}.
                            @if ($subscription->plan?->max_branches)
                                Up to {{ $subscription->plan->max_branches }} branches
                            @endif
                            @if ($subscription->plan?->max_staff)
                                &amp; {{ $subscription->plan->max_staff }} staff
                            @endif
                        </div>
                        <!--end::Notice-->
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-lg-5">
                        <!--begin::Heading-->
                        <div class="d-flex text-muted fw-bold fs-5 mb-3">
                            <span class="flex-grow-1 text-gray-800">Users</span>
                            <span class="text-gray-800">{{ $seatUsage['used'] }}{{ $seatUsage['limit'] ? ' of ' . $seatUsage['limit'] . ' Used' : ' active' }}</span>
                        </div>
                        <!--end::Heading-->
                        @if ($seatUsage['limit'])
                            @php $seatPercent = min(100, (int) round(($seatUsage['used'] / max($seatUsage['limit'], 1)) * 100)); @endphp
                            <!--begin::Progress-->
                            <div class="progress h-8px bg-light-primary mb-2">
                                <div class="progress-bar bg-primary" role="progressbar"
                                    style="width: {{ $seatPercent }}%" aria-valuenow="{{ $seatPercent }}"
                                    aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <!--end::Progress-->
                            <!--begin::Description-->
                            <div class="fs-6 text-gray-600 fw-semibold mb-10">
                                {{ max($seatUsage['limit'] - $seatUsage['used'], 0) }} users remaining on this plan
                            </div>
                            <!--end::Description-->
                        @endif
                        <!--begin::Action-->
                        <div class="d-flex justify-content-end pb-0 px-0">
                            <a href="#" class="btn btn-light btn-active-light-primary me-2"
                                id="kt_account_billing_cancel_subscription_btn">Cancel Subscription</a>
                            <button class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#kt_modal_upgrade_plan">Upgrade Plan</button>
                        </div>
                        <!--end::Action-->
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
            @endif
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Billing Summary-->
    <!--begin::Payment methods-->
    <div class="card mb-5 mb-xl-10">
        <!--begin::Card header-->
        <div class="card-header card-header-stretch pb-0">
            <!--begin::Title-->
            <div class="card-title">
                <h3 class="m-0">Payment Methods</h3>
            </div>
            <!--end::Title-->
            <!--begin::Toolbar-->
            <div class="card-toolbar m-0">
                <!--begin::Tab nav-->
                <ul class="nav nav-stretch nav-line-tabs border-transparent" role="tablist">
                    <!--begin::Tab item-->
                    <li class="nav-item" role="presentation">
                        <a id="kt_billing_creditcard_tab" class="nav-link fs-5 fw-bold me-5 active" data-bs-toggle="tab"
                            role="tab" href="#kt_billing_creditcard">Credit / Debit Card</a>
                    </li>
                    <!--end::Tab item-->
                    <!--begin::Tab item-->
                    <li class="nav-item" role="presentation">
                        <a id="kt_billing_paypal_tab" class="nav-link fs-5 fw-bold" data-bs-toggle="tab" role="tab"
                            href="#kt_billing_paypal">Paypal</a>
                    </li>
                    <!--end::Tab item-->
                </ul>
                <!--end::Tab nav-->
            </div>
            <!--end::Toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Tab content-->
        <div id="kt_billing_payment_tab_content" class="card-body tab-content">
            <!--begin::Tab panel-->
            <div id="kt_billing_creditcard" class="tab-pane fade show active" role="tabpanel">
                <!--begin::Title-->
                <h3 class="mb-5">My Cards</h3>
                <!--end::Title-->
                <!--begin::Row-->
                <div class="row gx-9 gy-6">
                    @if (! $hasPaymentMethod)
                        <!--begin::Col-->
                        <div class="col-xl-6" data-kt-billing-element="card">
                            <!--begin::Card-->
                            <div class="card card-dashed h-xl-100 flex-row flex-stack flex-wrap p-6">
                                <!--begin::Info-->
                                <div class="d-flex flex-column py-2">
                                    <div class="d-flex align-items-center fs-4 fw-bold mb-2">No payment method on file
                                    </div>
                                    <div class="fs-6 fw-semibold text-gray-500">Add a card to enable automatic
                                        renewal.</div>
                                </div>
                                <!--end::Info-->
                            </div>
                            <!--end::Card-->
                        </div>
                        <!--end::Col-->
                    @endif
                    <!--begin::Col-->
                    <div class="col-xl-6">
                        <!--begin::Notice-->
                        <div
                            class="notice d-flex bg-light-primary rounded border-primary border border-dashed h-lg-100 p-6">
                            <!--begin::Wrapper-->
                            <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
                                <!--begin::Content-->
                                <div class="mb-3 mb-md-0 fw-semibold">
                                    <h4 class="text-gray-900 fw-bold">Important Note!</h4>
                                    <div class="fs-6 text-gray-700 pe-7">Please carefully read
                                        <a href="#" class="fw-bold me-1">Product Terms</a>adding
                                        <br />your new payment card
                                    </div>
                                </div>
                                <!--end::Content-->
                                <!--begin::Action-->
                                <span class="d-inline-block align-self-center" tabindex="0" data-bs-toggle="tooltip"
                                    title="Card payments aren't available yet — coming soon.">
                                    <a href="#" class="btn btn-primary px-6 text-nowrap disabled" aria-disabled="true"
                                        style="pointer-events: none;">Add Card</a>
                                </span>
                                <!--end::Action-->
                            </div>
                            <!--end::Wrapper-->
                        </div>
                        <!--end::Notice-->
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
            </div>
            <!--end::Tab panel-->
            <!--begin::Tab panel-->
            <div id="kt_billing_paypal" class="tab-pane fade" role="tabpanel"
                aria-labelledby="kt_billing_paypal_tab">
                <!--begin::Title-->
                <h3 class="mb-5">My Paypal</h3>
                <!--end::Title-->
                <!--begin::Description-->
                <div class="text-gray-600 fs-6 fw-semibold mb-5">To use PayPal as your payment method, you will need to
                    make pre-payments each month before your bill is due.</div>
                <!--end::Description-->
                <!--begin::Form-->
                <form class="form">
                    <!--begin::Input group-->
                    <div class="mb-7 mw-350px">
                        <select name="timezone" data-control="select2" data-placeholder="Select an option"
                            data-hide-search="true"
                            class="form-select form-select-solid form-select-lg fw-semibold fs-6 text-gray-700">
                            <option>Select an option</option>
                            <option value="25">US $25.00</option>
                            <option value="50">US $50.00</option>
                            <option value="100">US $100.00</option>
                            <option value="125">US $125.00</option>
                            <option value="150">US $150.00</option>
                        </select>
                    </div>
                    <!--end::Input group-->
                    <button type="submit" class="btn btn-primary">Pay with Paypal</button>
                </form>
                <!--end::Form-->
            </div>
            <!--end::Tab panel-->
        </div>
        <!--end::Tab content-->
    </div>
    <!--end::Payment methods-->
    <!--begin::Billing Address-->
    <div class="card mb-5 mb-xl-10">
        <!--begin::Card header-->
        <div class="card-header">
            <!--begin::Title-->
            <div class="card-title">
                <h3>Billing Address</h3>
            </div>
            <!--end::Title-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body">
            <!--begin::Addresses-->
            <div class="row gx-9 gy-6">
                @if ($address)
                    <!--begin::Col-->
                    <div class="col-xl-6" data-kt-billing-element="address">
                        <!--begin::Address-->
                        <div class="card card-dashed h-xl-100 flex-row flex-stack flex-wrap p-6">
                            <!--begin::Details-->
                            <div class="d-flex flex-column py-2">
                                <div class="d-flex align-items-center fs-5 fw-bold mb-5">Billing Address
                                    <span class="badge badge-light-success fs-7 ms-2">Primary</span>
                                </div>
                                <div class="fs-6 fw-semibold text-gray-600">{{ $address->address_line_1 }}
                                    @if ($address->address_line_2)
                                        <br />{{ $address->address_line_2 }}
                                    @endif
                                    <br />{{ $address->city }} {{ $address->postal_code }}
                                    <br />{{ $address->country }}
                                </div>
                            </div>
                            <!--end::Details-->
                        </div>
                        <!--end::Address-->
                    </div>
                    <!--end::Col-->
                @else
                    <!--begin::Col-->
                    <div class="col-xl-6" data-kt-billing-element="address">
                        <!--begin::Address-->
                        <div class="card card-dashed h-xl-100 flex-row flex-stack flex-wrap p-6">
                            <!--begin::Details-->
                            <div class="d-flex flex-column py-2">
                                <div class="d-flex align-items-center fs-5 fw-bold mb-3">No billing address on file
                                </div>
                            </div>
                            <!--end::Details-->
                        </div>
                        <!--end::Address-->
                    </div>
                    <!--end::Col-->
                @endif
                <!--begin::Col-->
                <div class="col-xl-6">
                    <!--begin::Notice-->
                    <div
                        class="notice d-flex bg-light-primary rounded border-primary border border-dashed flex-stack h-xl-100 mb-10 p-6">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
                            <!--begin::Content-->
                            <div class="mb-3 mb-md-0 fw-semibold">
                                <h4 class="text-gray-900 fw-bold">Billing address</h4>
                                <div class="fs-6 text-gray-700 pe-7">Add a billing address so it can appear on your
                                    invoices and statements.</div>
                            </div>
                            <!--end::Content-->
                            <!--begin::Action-->
                            <span class="d-inline-block align-self-center" tabindex="0" data-bs-toggle="tooltip"
                                title="Address management isn't available yet — coming soon.">
                                <a href="#" class="btn btn-primary px-6 text-nowrap disabled" aria-disabled="true"
                                    style="pointer-events: none;">New Address</a>
                            </span>
                            <!--end::Action-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Notice-->
                </div>
                <!--end::Col-->
            </div>
            <!--end::Addresses-->
            <!--begin::Tax info-->
            <div class="mt-10">
                <h3 class="mb-3">Tax Location</h3>
                <div class="fw-semibold text-gray-600 fs-6">{{ $tenant?->country ?: 'Not set' }}
                    <br />
                    <a class="fw-bold" href="#">More Info</a>
                </div>
            </div>
            <!--end::Tax info-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Billing Address-->
    <!--begin::Billing History-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header card-header-stretch border-bottom border-gray-200">
            <!--begin::Title-->
            <div class="card-title">
                <h3 class="fw-bold m-0">Billing History</h3>
            </div>
            <!--end::Title-->
        </div>
        <!--end::Card header-->
        <!--begin::Table container-->
        <div class="table-responsive">
            <!--begin::Table-->
            <table class="table table-row-bordered align-middle gy-4 gs-9">
                <thead class="border-bottom border-gray-200 fs-6 text-gray-600 fw-bold bg-light bg-opacity-75">
                    <tr>
                        <td class="min-w-150px">Date</td>
                        <td class="min-w-250px">Plan</td>
                        <td class="min-w-150px">Amount</td>
                        <td class="min-w-150px">Status</td>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @forelse ($subscriptions ?? [] as $historySubscription)
                        <!--begin::Table row-->
                        <tr>
                            <td>{{ $historySubscription->starts_at?->format('M d, Y') ?? 'N/A' }}</td>
                            <td>{{ $historySubscription->plan?->name ?? 'Plan' }}</td>
                            <td>${{ number_format((float) ($historySubscription->price ?? $historySubscription->plan?->price ?? 0), 2) }}</td>
                            <td>
                                <span class="badge badge-light-{{ $historySubscription->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($historySubscription->status) }}
                                </span>
                            </td>
                        </tr>
                        <!--end::Table row-->
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-10">No billing history yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <!--end::Table-->
        </div>
        <!--end::Table container-->
    </div>
    <!--end::Billing History-->
</x-default-layout>
