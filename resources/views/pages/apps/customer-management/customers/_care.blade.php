{{-- Segments + referral --}}
<div class="card border-0 shadow-sm mb-8">
    <div class="card-header border-0 pt-8">
        <div class="card-title">
            <div class="symbol symbol-45px me-4"><div class="symbol-label bg-light-success"><i class="bi bi-people text-success fs-3"></i></div></div>
            <div>
                <h3 class="fw-bold text-gray-900 mb-1">Segments & Referrals</h3>
                <div class="text-muted fs-8">Groups this customer is in, and who they brought in.</div>
            </div>
        </div>
    </div>
    <div class="card-body pt-4">
        <div class="d-flex flex-wrap gap-2 mb-5">
            @forelse ($customerSegments as $segment)
                @php $meta = \App\Services\Customers\CustomerSegmentService::SEGMENTS[$segment]; @endphp
                <span class="badge badge-light-{{ $meta['color'] }} px-3 py-2"><i class="bi {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}</span>
            @empty
                <span class="text-muted fs-8">Not in any segment yet.</span>
            @endforelse
        </div>

        <div class="d-flex justify-content-between align-items-center rounded-3 bg-light p-4 mb-3">
            <div>
                <div class="text-muted fs-8">Referral code</div>
                <div class="fs-3 fw-bolder text-gray-900 font-monospace">{{ $referralCode }}</div>
            </div>
            <button type="button" class="btn btn-sm btn-light-primary" onclick="navigator.clipboard.writeText('{{ $referralCode }}'); this.textContent='Copied';">Copy</button>
        </div>
        <div class="fs-7">
            @if ($customer->referrer)
                Referred by <a href="{{ route('customer-management.customers.show', $customer->referrer) }}" class="fw-semibold">{{ $customer->referrer->full_name }}</a>
                · {{ $customer->referral_rewarded_at ? 'rewards given ' . $customer->referral_rewarded_at->format('M d, Y') : 'rewards pending first paid visit' }}
            @endif
            @if ($customer->referrals->isNotEmpty())
                <div class="mt-2">Referred {{ $customer->referrals->count() }}:
                    @foreach ($customer->referrals as $friend)
                        <a href="{{ route('customer-management.customers.show', $friend) }}">{{ $friend->full_name }}</a>@if (! $loop->last), @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Consultation forms --}}
<div class="card border-0 shadow-sm mb-8">
    <div class="card-header border-0 pt-8">
        <div class="card-title">
            <div class="symbol symbol-45px me-4"><div class="symbol-label bg-light-info"><i class="bi bi-clipboard2-pulse text-info fs-3"></i></div></div>
            <div>
                <h3 class="fw-bold text-gray-900 mb-1">Consultation Forms</h3>
                <div class="text-muted fs-8">Health questions and signed consent.</div>
            </div>
        </div>
        @can('update', $customer)
            <div class="card-toolbar"><a href="{{ route('customer-management.consultations.create', $customer) }}" class="btn btn-sm btn-light-primary"><i class="bi bi-plus-lg me-1"></i>New Form</a></div>
        @endcan
    </div>
    <div class="card-body pt-4">
        @forelse ($customer->consultations->take(5) as $consultation)
            <a href="{{ route('customer-management.consultations.show', [$customer, $consultation]) }}" class="d-flex justify-content-between align-items-center py-3 border-bottom border-gray-200 text-gray-800 text-hover-primary">
                <span>{{ $consultation->created_at?->format('M d, Y') }} @if ($consultation->warnings())<i class="bi bi-exclamation-triangle text-warning ms-1" title="{{ implode(' · ', $consultation->warnings()) }}"></i>@endif</span>
                <span class="text-muted fs-8">Signed by {{ $consultation->consent_name }}</span>
            </a>
        @empty
            <div class="text-muted fs-7">No consultation forms yet.</div>
        @endforelse
    </div>
</div>

{{-- Photos --}}
<div class="card border-0 shadow-sm mb-8">
    <div class="card-header border-0 pt-8">
        <div class="card-title">
            <div class="symbol symbol-45px me-4"><div class="symbol-label bg-light-warning"><i class="bi bi-images text-warning fs-3"></i></div></div>
            <div>
                <h3 class="fw-bold text-gray-900 mb-1">Before & After</h3>
                <div class="text-muted fs-8">Private photos, only visible to staff.</div>
            </div>
        </div>
    </div>
    <div class="card-body pt-4">
        @can('update', $customer)
            <form method="POST" action="{{ route('customer-management.photos.store', $customer) }}" enctype="multipart/form-data" class="rounded-3 bg-light p-4 mb-5">
                @csrf
                <div class="row g-3">
                    <div class="col-12"><input type="file" name="photos[]" accept="image/png,image/jpeg,image/webp" multiple class="form-control form-control-sm" required></div>
                    <div class="col-6">
                        <select name="kind" class="form-select form-select-sm">
                            @foreach (\App\Models\CustomerPhoto::KINDS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6"><input type="date" name="taken_on" value="{{ now()->toDateString() }}" class="form-control form-control-sm"></div>
                    <div class="col-12"><input type="text" name="caption" class="form-control form-control-sm" placeholder="Caption, e.g. Balayage, level 8"></div>
                    <div class="col-12">
                        <select name="appointment_id" class="form-select form-select-sm">
                            <option value="">Not linked to an appointment</option>
                            @foreach ($customer->appointments->sortByDesc('starts_at')->take(10) as $appointment)
                                <option value="{{ $appointment->id }}">{{ $appointment->appointment_number }} · {{ $appointment->starts_at?->format('M d') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-upload me-1"></i>Upload</button></div>
                </div>
            </form>
        @endcan

        @if ($customer->photos->isNotEmpty())
            <div class="row g-3">
                @foreach ($customer->photos as $photo)
                    <div class="col-6">
                        <div class="position-relative">
                            <a href="{{ route('customer-management.photos.show', [$customer, $photo]) }}" target="_blank">
                                <img src="{{ route('customer-management.photos.show', [$customer, $photo]) }}" alt="{{ $photo->caption }}" class="w-100 rounded-3" style="height: 140px; object-fit: cover;" loading="lazy">
                            </a>
                            <span class="badge badge-{{ $photo->kind === 'after' ? 'success' : ($photo->kind === 'before' ? 'dark' : 'secondary') }} position-absolute top-0 start-0 m-2">{{ \App\Models\CustomerPhoto::KINDS[$photo->kind] ?? $photo->kind }}</span>
                            @can('update', $customer)
                                <form method="POST" action="{{ route('customer-management.photos.destroy', [$customer, $photo]) }}" class="position-absolute top-0 end-0 m-1" onsubmit="return confirm('Delete this photo?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-icon btn-sm btn-light-danger"><i class="bi bi-x"></i></button>
                                </form>
                            @endcan
                        </div>
                        <div class="text-muted fs-9 mt-1">{{ $photo->taken_on?->format('M d, Y') }}{{ $photo->caption ? ' · ' . $photo->caption : '' }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-muted fs-7">No photos yet.</div>
        @endif
    </div>
</div>
