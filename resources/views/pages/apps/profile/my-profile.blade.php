@php
    $profileUser = auth()->user();
    $profileName =
        trim(($profileUser->first_name ?? '') . ' ' . ($profileUser->last_name ?? '')) ?: $profileUser->name ?? 'User';
    $profilePhone = $profileUser->phone ?: 'Not provided';
    $profileCompany = $profileUser->tenant?->name ?: 'Your salon';
    $profileCountry = $profileUser->tenant?->country ?: 'Sri Lanka';
    $profileWebsite = $profileUser->tenant?->website ?: '#';
    $profileCommunication = collect([$profileUser->email ? 'Email' : null, $profileUser->phone ? 'Phone' : null])
        ->filter()
        ->implode(', ');
@endphp

<x-default-layout>
    <div id="kt_app_content_container">
        @include('pages.apps.profile.partials._profile-navbar')
        <!--begin::details View-->
        <div class="card mb-5 mb-xl-10" id="kt_profile_details_view">
            <!--begin::Card header-->
            <div class="card-header cursor-pointer">
                <!--begin::Card title-->
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">Profile Details</h3>
                </div>
                <!--end::Card title-->
                <!--begin::Action-->
                <a href="{{ route('profile.settings') }}" class="btn btn-sm btn-primary align-self-center">Edit
                    Profile</a>
                <!--end::Action-->
            </div>
            <!--begin::Card header-->
            <!--begin::Card body-->
            <div class="card-body p-9">
                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Full Name</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-gray-800">{{ $profileName }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
                <!--begin::Input group-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Company</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8 fv-row">
                        <span class="fw-semibold text-gray-800 fs-6">{{ $profileCompany }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Contact Phone
                        <span class="ms-1" data-bs-toggle="tooltip" title="Phone number must be active">
                            <i class="ki-duotone ki-information fs-7">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                        </span></label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8 d-flex align-items-center">
                        <span class="fw-bold fs-6 text-gray-800 me-2">{{ $profilePhone }}</span>
                        <span class="badge badge-success">Verified</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Company Site</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <a href="{{ $profileWebsite !== '#' ? $profileWebsite : '#' }}"
                            class="fw-semibold fs-6 text-gray-800 text-hover-primary">
                            {{ $profileWebsite === '#' ? 'Website not set' : $profileWebsite }}
                        </a>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Country
                        <span class="ms-1" data-bs-toggle="tooltip" title="Country of origination">
                            <i class="ki-duotone ki-information fs-7">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                        </span></label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-gray-800">{{ $profileCountry }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Communication</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-gray-800">{{ $profileCommunication ?: 'Email, Phone' }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="row mb-10">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Allow Changes</label>
                    <!--begin::Label-->
                    <!--begin::Label-->
                    <div class="col-lg-8">
                        <span class="fw-semibold fs-6 text-gray-800">Yes</span>
                    </div>
                    <!--begin::Label-->
                </div>
                <!--end::Input group-->
                <!--begin::Notice-->
                <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6">
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
                            <h4 class="text-gray-900 fw-bold">We need your attention!</h4>
                            <div class="fs-6 text-gray-700">Your payment was declined. To start using
                                tools, please
                                <a class="fw-bold" href="{{ route('profile.billing') }}">Add Payment Method</a>.
                            </div>
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Notice-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::details View-->
        <!--begin::Row-->
        <div class="row gy-5 g-xl-10">
            <!--begin::Col-->
            <div class="col-xl-8 mb-xl-10">
                <!--begin::Chart widget 5-->
                <div class="card card-flush h-lg-100">
                    <!--begin::Header-->
                    <div class="card-header flex-nowrap pt-5">
                        <!--begin::Title-->
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Recent Activity</span>
                            <span class="text-gray-500 pt-2 fw-semibold fs-6">Your latest account activity</span>
                        </h3>
                        <!--end::Title-->
                        <!--begin::Toolbar-->
                        <div class="card-toolbar">
                            <a href="{{ route('profile.activity') }}" class="btn btn-sm btn-light-primary">View
                                all activity</a>
                        </div>
                        <!--end::Toolbar-->
                    </div>
                    <!--end::Header-->
                    <!--begin::Body-->
                    <div class="card-body pt-5 ps-6">
                        @forelse (($recentActivity ?? []) as $activityItem)
                            <div class="d-flex align-items-center mb-7">
                                <div class="symbol symbol-45px me-5">
                                    <span class="symbol-label bg-light-primary">
                                        <i class="ki-duotone ki-flash fs-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </span>
                                </div>
                                <div class="d-flex flex-column flex-grow-1">
                                    <span class="text-gray-800 fw-bold fs-6">{{ $activityItem->description ?: $activityItem->action_label }}</span>
                                    <span class="text-muted fw-semibold d-block fs-7">{{ $activityItem->module_label }} &middot; {{ $activityItem->created_at?->diffForHumans() }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted fs-6">No recent activity yet.</div>
                        @endforelse
                    </div>
                    <!--end::Body-->
                </div>
                <!--end::Chart widget 5-->
            </div>
            <!--end::Col-->
            <!--begin::Col-->
            <div class="col-xl-4 mb-5 mb-xl-10">
                <!--begin::Engage widget 1-->
                <div class="card h-md-100" dir="ltr">
                    <!--begin::Body-->
                    <div class="card-body d-flex flex-column flex-center">
                        <!--begin::Heading-->
                        <div class="mb-2">
                            <!--begin::Title-->
                            <h1 class="fw-semibold text-gray-800 text-center lh-lg">Have you tried
                                <br />new
                                <span class="fw-bolder">Mobile Application ?</span>
                            </h1>
                            <!--end::Title-->
                            <!--begin::Illustration-->
                            <div class="py-10 text-center">
                                <img src="{{ asset('assets/media/svg/illustrations/easy/1.svg') }}"
                                    class="theme-light-show w-200px" alt="" />
                                <img src="{{ asset('assets/media/svg/illustrations/easy/1-dark.svg') }}"
                                    class="theme-dark-show w-200px" alt="" />
                            </div>
                            <!--end::Illustration-->
                        </div>
                        <!--end::Heading-->
                        <!--begin::Links-->
                        <div class="text-center mb-1">
                            <!--begin::Link-->
                            <a class="btn btn-sm btn-primary me-2" data-bs-target="#kt_modal_create_app"
                                data-bs-toggle="modal">Try now</a>
                            <!--end::Link-->
                            <!--begin::Link-->
                            <a class="btn btn-sm btn-light" href="#">Learn
                                more</a>
                            <!--end::Link-->
                        </div>
                        <!--end::Links-->
                    </div>
                    <!--end::Body-->
                </div>
                <!--end::Engage widget 1-->
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->
    </div>
</x-default-layout>
