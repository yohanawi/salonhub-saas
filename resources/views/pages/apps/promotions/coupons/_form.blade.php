@csrf
@if ($isSuperAdmin)
    <div class="mb-6">
        <label class="form-label required">Tenant</label>
        <select name="tenant_id" class="form-select form-select-solid" required>
            <option value="">Select tenant</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected(old('tenant_id', $selectedTenant?->id) == $tenant->id)>{{ $tenant->name }}</option>
            @endforeach
        </select>
    </div>
@endif
<div class="row g-6">
    <div class="col-md-6"><label class="form-label required">Promotion</label><select name="promotion_id" class="form-select form-select-solid" required><option value="">Select promotion</option>@foreach ($promotions as $promotion)<option value="{{ $promotion->id }}" @selected(old('promotion_id', $coupon->promotion_id) == $promotion->id)>{{ $promotion->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label required">Coupon Code</label><input name="code" value="{{ old('code', $coupon->code) }}" class="form-control form-control-solid text-uppercase" required></div>
    <div class="col-md-3"><label class="form-label required">Status</label><select name="status" class="form-select form-select-solid">@foreach (\App\Models\PromotionCoupon::STATUSES as $status)<option value="{{ $status }}" @selected(old('status', $coupon->status) === $status)>{{ str($status)->headline() }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Starts At</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\\TH:i')) }}" class="form-control form-control-solid"></div>
    <div class="col-md-3"><label class="form-label">Expires At</label><input type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\\TH:i')) }}" class="form-control form-control-solid"></div>
    <div class="col-md-3"><label class="form-label">Usage Limit</label><input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="form-control form-control-solid"></div>
    <div class="col-md-3"><label class="form-label">Per-Customer Limit</label><input type="number" name="per_customer_limit" value="{{ old('per_customer_limit', $coupon->per_customer_limit) }}" class="form-control form-control-solid"></div>
</div>
<div class="text-end mt-8"><button class="btn btn-primary">Save Coupon</button></div>
