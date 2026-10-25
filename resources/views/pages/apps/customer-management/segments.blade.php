<x-default-layout>
    @section('title') Customer Segments @endsection
    @section('breadcrumbs') {{ Breadcrumbs::render('customer-management.segments.index') }} @endsection

    <div id="kt_app_content_container">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm mb-5">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-5">{{ $errors->first() }}</div>
        @endif

        <div class="row g-5 mb-5">
            @foreach ($segments as $segment)
                <div class="col-md-6 col-xl">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-5">
                            <div class="d-flex align-items-center mb-3">
                                <div class="symbol symbol-40px me-3"><div class="symbol-label bg-light-{{ $segment['color'] }}"><i class="bi {{ $segment['icon'] }} text-{{ $segment['color'] }} fs-3"></i></div></div>
                                <div class="fw-bold text-gray-900">{{ $segment['label'] }}</div>
                            </div>
                            <div class="fs-2hx fw-bolder text-gray-900">{{ number_format($segment['count']) }}</div>
                            <div class="text-muted fs-8 mb-3">{{ $segment['description'] }}</div>
                            <div class="text-muted fs-8 mb-4">{{ number_format($segment['reachable']) }} can be messaged (marketing consent + phone)</div>
                            <a href="{{ route('customer-management.customers.index', ['segment' => $segment['key']]) }}" class="btn btn-sm btn-light-{{ $segment['color'] }} w-100">View customers</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-5">
            <div class="col-xl-5">
                @if ($canSend)
                    <form method="POST" action="{{ route('customer-management.segments.campaign') }}" class="card border-0 shadow-sm">
                        @csrf
                        <div class="card-header border-0 pt-6">
                            <div class="card-title flex-column">
                                <h3 class="fw-bold mb-1"><i class="bi bi-megaphone text-primary me-2"></i>Send a Campaign</h3>
                                <div class="text-muted fs-8">SMS/WhatsApp to customers in a segment who agreed to marketing messages.</div>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            <div class="mb-4">
                                <label class="form-label required">Segment</label>
                                <select name="segment" class="form-select form-select-solid">
                                    @foreach ($segments as $segment)
                                        <option value="{{ $segment['key'] }}" @selected(old('segment') === $segment['key'])>{{ $segment['label'] }} ({{ number_format($segment['reachable']) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label required">Campaign name</label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-solid" placeholder="e.g. We miss you – 20% off" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label required">Message</label>
                                <textarea name="message" rows="4" maxlength="640" class="form-control form-control-solid" placeholder="Hi {customer_name}, we miss you at {salon_name}! Enjoy 20% off your next visit this month." required>{{ old('message') }}</textarea>
                                <div class="text-muted fs-8 mt-1">Use {customer_name} and {salon_name}. Keep it under 160 characters for a single SMS.</div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary w-100" onclick="return confirm('Send this message to everyone in the segment?');"><i class="bi bi-send me-1"></i>Send Campaign</button>
                        </div>
                    </form>
                @endif

                <div class="card border-0 shadow-sm mt-5">
                    <div class="card-header border-0 pt-6"><div class="card-title"><h3 class="fw-bold mb-0">Top Referrers</h3></div></div>
                    <div class="card-body pt-2">
                        @forelse ($topReferrers as $referrer)
                            <div class="d-flex justify-content-between py-2 border-bottom border-gray-200">
                                <a href="{{ route('customer-management.customers.show', $referrer) }}" class="fw-semibold text-gray-800 text-hover-primary">{{ $referrer->full_name }}</a>
                                <span class="badge badge-light-success">{{ $referrer->referrals_count }} referred</span>
                            </div>
                        @empty
                            <div class="text-muted fs-7">No referrals yet. Every customer has a referral code on their profile.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-6"><div class="card-title"><h3 class="fw-bold mb-0">Recent Campaigns</h3></div></div>
                    <div class="card-body pt-2">
                        @forelse ($campaigns as $campaign)
                            <div class="border border-gray-200 rounded-3 p-4 mb-3">
                                <div class="d-flex justify-content-between flex-wrap gap-2">
                                    <div class="fw-bold text-gray-900">{{ $campaign->name }}</div>
                                    <div class="text-muted fs-8">{{ $campaign->sent_at?->format('M d, Y H:i') }} · {{ $campaign->createdBy?->name }}</div>
                                </div>
                                <div class="text-muted fs-8 mb-2">{{ \App\Services\Customers\CustomerSegmentService::SEGMENTS[$campaign->segment]['label'] ?? $campaign->segment }} · {{ number_format($campaign->queued_count) }} of {{ number_format($campaign->recipients_count) }} queued</div>
                                <div class="text-gray-700 fs-7" style="white-space: pre-line;">{{ $campaign->message }}</div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-10">No campaigns sent yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
