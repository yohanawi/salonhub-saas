@csrf

@if ($isSuperAdmin)
    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5 mb-8">
        <i class="bi bi-building text-primary fs-2hx me-4"></i>
        <div class="flex-grow-1">
            <label class="form-label required fw-bold text-gray-900">Tenant</label>
            <select name="tenant_id" class="form-select form-select-solid" required>
                <option value="">Select tenant</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(old('tenant_id', $selectedTenant?->id) == $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
@endif

<div class="row g-8">
    <div class="col-xl-7">
        <div class="rounded border border-gray-200 p-6 h-100">
            <div class="d-flex align-items-center mb-6">
                <div class="symbol symbol-40px me-4">
                    <div class="symbol-label bg-light-warning">
                        <i class="bi bi-ticket-perforated text-warning fs-3"></i>
                    </div>
                </div>
                <div>
                    <h4 class="fw-bold text-gray-900 mb-0">Coupon Details</h4>
                    <div class="text-muted fs-7">Choose the promotion and redeemable code</div>
                </div>
            </div>
            <div class="row g-6">
                <div class="col-md-8">
                    <label class="form-label required">Promotion</label>
                    <select name="promotion_id" class="form-select form-select-solid" required>
                        <option value="">Select promotion</option>
                        @foreach ($promotions as $promotion)
                            <option value="{{ $promotion->id }}" @selected(old('promotion_id', $coupon->promotion_id) == $promotion->id)>{{ $promotion->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label required">Status</label>
                    <select name="status" class="form-select form-select-solid">
                        @foreach (\App\Models\PromotionCoupon::STATUSES as $status)
                            <option value="{{ $status }}" @selected(old('status', $coupon->status) === $status)>{{ str($status)->headline() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label required">Coupon Code</label>
                    <div class="input-group input-group-solid">
                        <span class="input-group-text">
                            <i class="bi bi-upc-scan text-muted"></i>
                        </span>
                        <input name="code" value="{{ old('code', $coupon->code) }}" class="form-control form-control-solid text-uppercase" required>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="rounded border border-gray-200 p-6 h-100">
            <div class="d-flex align-items-center mb-6">
                <div class="symbol symbol-40px me-4">
                    <div class="symbol-label bg-light-info">
                        <i class="bi bi-calendar-range text-info fs-3"></i>
                    </div>
                </div>
                <div>
                    <h4 class="fw-bold text-gray-900 mb-0">Schedule & Limits</h4>
                    <div class="text-muted fs-7">Control coupon availability</div>
                </div>
            </div>
            <div class="row g-6">
                <div class="col-md-6">
                    <label class="form-label">Starts At</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}" class="form-control form-control-solid">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Expires At</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}" class="form-control form-control-solid">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Usage Limit</label>
                    <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="form-control form-control-solid">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Per-Customer Limit</label>
                    <input type="number" name="per_customer_limit" value="{{ old('per_customer_limit', $coupon->per_customer_limit) }}" class="form-control form-control-solid">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-3 mt-8">
    <a href="{{ route('promotions.coupons.index') }}" class="btn btn-light">Cancel</a>
    <button class="btn btn-primary">
        <i class="bi bi-check2 me-2"></i>Save Coupon
    </button>
</div>
