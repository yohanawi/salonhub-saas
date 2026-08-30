<?php

namespace App\Services\Promotions;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\Invoice;
use App\Models\Promotion;
use App\Models\PromotionCoupon;
use App\Models\PromotionUsage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    public function applyToItemSnapshots(Tenant $tenant, ?Customer $customer, Branch $branch, Collection $items, array $data): array
    {
        $promotion = null;
        $coupon = null;

        if (filled($data['coupon_code'] ?? null)) {
            $coupon = PromotionCoupon::withoutTenantScope()
                ->with('promotion')
                ->where('tenant_id', $tenant->id)
                ->whereRaw('UPPER(code) = ?', [strtoupper(trim((string) $data['coupon_code']))])
                ->first();

            if (! $coupon) {
                throw ValidationException::withMessages(['coupon_code' => 'Coupon code was not found for this salon.']);
            }

            $promotion = $coupon->promotion;
        } elseif (filled($data['promotion_id'] ?? null)) {
            $promotion = Promotion::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->findOrFail((int) $data['promotion_id']);
        } else {
            $promotion = $this->eligiblePromotions($tenant, $customer, $branch, $items)
                ->first();
        }

        if (! $promotion) {
            return [
                'items' => $items,
                'promotion' => null,
                'coupon' => null,
                'discount' => 0.0,
                'message' => null,
            ];
        }

        $result = $this->checkEligibility($promotion, $customer, $branch, $items, $coupon);

        if ($result->failed()) {
            $field = $coupon ? 'coupon_code' : 'promotion_id';

            throw ValidationException::withMessages([$field => $result->message]);
        }

        return [
            'items' => $this->spreadDiscountAcrossItems($items, $promotion, $result->discount),
            'promotion' => $promotion,
            'coupon' => $coupon,
            'discount' => $result->discount,
            'message' => $result->message,
        ];
    }

    public function eligiblePromotions(Tenant $tenant, ?Customer $customer, Branch $branch, Collection $items): Collection
    {
        return Promotion::withoutTenantScope()
            ->with(['branches', 'services', 'products', 'customers', 'membershipPlans'])
            ->where('tenant_id', $tenant->id)
            ->where('application_type', Promotion::APPLICATION_AUTOMATIC)
            ->active()
            ->orderByDesc('priority')
            ->get()
            ->map(fn (Promotion $promotion) => $this->checkEligibility($promotion, $customer, $branch, $items))
            ->filter->eligible
            ->sortByDesc(fn (PromotionApplicationResult $result) => [$result->discount, $result->promotion?->priority ?? 0])
            ->values()
            ->map(fn (PromotionApplicationResult $result) => $result->promotion);
    }

    public function checkEligibility(Promotion $promotion, ?Customer $customer, Branch $branch, Collection $items, ?PromotionCoupon $coupon = null): PromotionApplicationResult
    {
        $promotion->loadMissing(['branches', 'services', 'products', 'customers', 'membershipPlans']);

        if ((int) $promotion->tenant_id !== (int) $branch->tenant_id) {
            return $this->ineligible('Promotion is not available for this salon.', $promotion, $coupon);
        }

        if ($customer && (int) $customer->tenant_id !== (int) $promotion->tenant_id) {
            return $this->ineligible('Customer does not belong to this salon.', $promotion, $coupon);
        }

        if ($promotion->status !== Promotion::STATUS_ACTIVE) {
            return $this->ineligible('Promotion is not active.', $promotion, $coupon);
        }

        if ($promotion->starts_at && now()->lt($promotion->starts_at)) {
            return $this->ineligible('Promotion has not started yet.', $promotion, $coupon);
        }

        if ($promotion->ends_at && now()->gt($promotion->ends_at)) {
            return $this->ineligible('Promotion has expired.', $promotion, $coupon);
        }

        if ($promotion->usage_limit !== null && $promotion->usage_count >= $promotion->usage_limit) {
            return $this->ineligible('Promotion usage limit has been reached.', $promotion, $coupon);
        }

        if ($promotion->branch_scope === Promotion::BRANCH_SELECTED && ! $promotion->branches->contains('id', $branch->id)) {
            return $this->ineligible('Promotion is not available for this branch.', $promotion, $coupon);
        }

        if ($coupon) {
            $couponResult = $this->checkCoupon($promotion, $coupon, $customer);

            if ($couponResult) {
                return $this->ineligible($couponResult, $promotion, $coupon);
            }
        } elseif ($promotion->coupon_required || $promotion->application_type === Promotion::APPLICATION_COUPON) {
            return $this->ineligible('A coupon code is required for this promotion.', $promotion, null);
        }

        $customerResult = $this->checkCustomerScope($promotion, $customer);

        if ($customerResult) {
            return $this->ineligible($customerResult, $promotion, $coupon);
        }

        [$eligibleSubtotal, $eligibleQuantity] = $this->eligibleBasis($promotion, $items);

        if ($eligibleQuantity <= 0 || $eligibleSubtotal <= 0) {
            return $this->ineligible('No eligible invoice items were found for this promotion.', $promotion, $coupon);
        }

        if ((float) $promotion->minimum_spend > 0 && $eligibleSubtotal < (float) $promotion->minimum_spend) {
            return $this->ineligible('Minimum spend of LKR ' . number_format((float) $promotion->minimum_spend, 2) . ' is required.', $promotion, $coupon);
        }

        if ($promotion->minimum_quantity !== null && $eligibleQuantity < $promotion->minimum_quantity) {
            return $this->ineligible('Minimum quantity of ' . $promotion->minimum_quantity . ' is required.', $promotion, $coupon);
        }

        $discount = $this->calculateDiscount($promotion, $eligibleSubtotal);

        if ($discount <= 0) {
            return $this->ineligible('Promotion does not produce a discount for this invoice.', $promotion, $coupon);
        }

        return new PromotionApplicationResult(true, $discount, 'Promotion successfully applied.', $promotion, $coupon, $eligibleSubtotal, $eligibleQuantity);
    }

    public function recordUsageForInvoice(Invoice $invoice, ?User $user = null): ?PromotionUsage
    {
        if (! $invoice->promotion_id || (float) $invoice->promotion_discount_amount <= 0 || $invoice->status !== Invoice::STATUS_ISSUED || $invoice->payment_status !== Invoice::PAYMENT_PAID) {
            return null;
        }

        return DB::transaction(function () use ($invoice, $user) {
            $existing = PromotionUsage::withoutTenantScope()
                ->where('tenant_id', $invoice->tenant_id)
                ->where('invoice_id', $invoice->id)
                ->where('promotion_id', $invoice->promotion_id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $usage = PromotionUsage::create([
                'tenant_id' => $invoice->tenant_id,
                'promotion_id' => $invoice->promotion_id,
                'promotion_coupon_id' => $invoice->promotion_coupon_id,
                'customer_id' => $invoice->customer_id,
                'branch_id' => $invoice->branch_id,
                'appointment_id' => $invoice->appointment_id,
                'invoice_id' => $invoice->id,
                'discount_amount' => $invoice->promotion_discount_amount,
                'status' => PromotionUsage::STATUS_USED,
                'used_at' => $invoice->paid_at ?: now(),
                'created_by' => $user?->id,
            ]);

            Promotion::withoutTenantScope()->whereKey($invoice->promotion_id)->increment('usage_count');

            if ($invoice->promotion_coupon_id) {
                PromotionCoupon::withoutTenantScope()->whereKey($invoice->promotion_coupon_id)->increment('usage_count');
            }

            return $usage;
        });
    }

    public function reverseForInvoice(Invoice $invoice, User $user, ?string $reason = null): void
    {
        DB::transaction(function () use ($invoice, $user, $reason) {
            PromotionUsage::withoutTenantScope()
                ->where('tenant_id', $invoice->tenant_id)
                ->where('invoice_id', $invoice->id)
                ->where('status', PromotionUsage::STATUS_USED)
                ->get()
                ->each(function (PromotionUsage $usage) use ($user, $reason) {
                    $usage->update([
                        'status' => PromotionUsage::STATUS_REVERSED,
                        'reversed_at' => now(),
                        'reversed_by' => $user->id,
                        'reversal_reason' => $reason,
                    ]);

                    Promotion::withoutTenantScope()->whereKey($usage->promotion_id)->where('usage_count', '>', 0)->decrement('usage_count');

                    if ($usage->promotion_coupon_id) {
                        PromotionCoupon::withoutTenantScope()->whereKey($usage->promotion_coupon_id)->where('usage_count', '>', 0)->decrement('usage_count');
                    }
                });
        });
    }

    public function syncTargets(Promotion $promotion, array $data): void
    {
        $tenantId = $promotion->tenant_id;
        $withTenant = fn (array $ids) => collect($ids)->filter()->unique()->mapWithKeys(fn ($id) => [(int) $id => ['tenant_id' => $tenantId]])->all();

        $promotion->branches()->sync($withTenant($data['branch_ids'] ?? []));
        $promotion->services()->sync($withTenant($data['service_ids'] ?? []));
        $promotion->products()->sync($withTenant($data['product_ids'] ?? []));
        $promotion->customers()->sync($withTenant($data['customer_ids'] ?? []));
        $promotion->membershipPlans()->sync($withTenant($data['membership_plan_ids'] ?? []));
    }

    private function checkCoupon(Promotion $promotion, PromotionCoupon $coupon, ?Customer $customer): ?string
    {
        if ((int) $coupon->tenant_id !== (int) $promotion->tenant_id || (int) $coupon->promotion_id !== (int) $promotion->id) {
            return 'Coupon does not belong to this promotion.';
        }

        if ($coupon->status !== PromotionCoupon::STATUS_ACTIVE) {
            return 'Coupon is not active.';
        }

        if ($coupon->starts_at && now()->lt($coupon->starts_at)) {
            return 'Coupon has not started yet.';
        }

        if ($coupon->expires_at && now()->gt($coupon->expires_at)) {
            return 'Coupon expired on ' . $coupon->expires_at->format('M d, Y') . '.';
        }

        if ($coupon->usage_limit !== null && $coupon->usage_count >= $coupon->usage_limit) {
            return 'Coupon usage limit has been reached.';
        }

        if ($customer && $coupon->per_customer_limit !== null) {
            $uses = PromotionUsage::withoutTenantScope()
                ->where('tenant_id', $coupon->tenant_id)
                ->where('promotion_coupon_id', $coupon->id)
                ->where('customer_id', $customer->id)
                ->where('status', PromotionUsage::STATUS_USED)
                ->count();

            if ($uses >= $coupon->per_customer_limit) {
                return 'Customer coupon usage limit has been reached.';
            }
        }

        return null;
    }

    private function checkCustomerScope(Promotion $promotion, ?Customer $customer): ?string
    {
        if ($promotion->customer_scope === Promotion::CUSTOMER_ALL) {
            return null;
        }

        if (! $customer) {
            return 'A customer is required for this promotion.';
        }

        if ($promotion->per_customer_limit !== null) {
            $uses = PromotionUsage::withoutTenantScope()
                ->where('tenant_id', $promotion->tenant_id)
                ->where('promotion_id', $promotion->id)
                ->where('customer_id', $customer->id)
                ->where('status', PromotionUsage::STATUS_USED)
                ->count();

            if ($uses >= $promotion->per_customer_limit) {
                return 'Customer promotion usage limit has been reached.';
            }
        }

        return match ($promotion->customer_scope) {
            Promotion::CUSTOMER_SELECTED => $promotion->customers->contains('id', $customer->id) ? null : 'Customer is not eligible for this promotion.',
            Promotion::CUSTOMER_NEW => $this->hasCompletedBusiness($customer) ? 'Promotion is only available for first-visit customers.' : null,
            Promotion::CUSTOMER_EXISTING => $this->hasCompletedBusiness($customer) ? null : 'Promotion is only available for existing customers.',
            Promotion::CUSTOMER_MEMBERS => $this->hasActiveMembership($promotion, $customer) ? null : 'Promotion is only available for members.',
            default => null,
        };
    }

    private function eligibleBasis(Promotion $promotion, Collection $items): array
    {
        $serviceIds = $promotion->services->pluck('id')->all();
        $productIds = $promotion->products->pluck('id')->all();

        $eligible = $items->filter(function (array $item) use ($promotion, $serviceIds, $productIds) {
            return match ($promotion->target_scope) {
                Promotion::TARGET_SERVICES => filled($item['service_id'] ?? null) && in_array((int) $item['service_id'], $serviceIds, true),
                Promotion::TARGET_PRODUCTS => filled($item['product_id'] ?? null) && in_array((int) $item['product_id'], $productIds, true),
                default => true,
            };
        });

        $subtotal = $eligible->sum(fn (array $item) => max(0, (float) ($item['gross_amount'] ?? 0) - (float) ($item['discount_amount'] ?? 0)));
        $quantity = $eligible->sum(fn (array $item) => (int) ($item['quantity'] ?? 1));

        return [round($subtotal, 2), $quantity];
    }

    private function calculateDiscount(Promotion $promotion, float $eligibleSubtotal): float
    {
        $discount = $promotion->discount_type === Promotion::DISCOUNT_PERCENTAGE
            ? $eligibleSubtotal * ((float) $promotion->discount_value / 100)
            : (float) $promotion->discount_value;

        if ($promotion->maximum_discount_amount !== null) {
            $discount = min($discount, (float) $promotion->maximum_discount_amount);
        }

        return round(min($discount, $eligibleSubtotal), 2);
    }

    private function spreadDiscountAcrossItems(Collection $items, Promotion $promotion, float $discount): Collection
    {
        if ($discount <= 0) {
            return $items;
        }

        [$eligibleSubtotal] = $this->eligibleBasis($promotion, $items);
        $remaining = $discount;
        $originalDiscount = $discount;
        $eligibleIndexes = $items->keys()->filter(function ($key) use ($items, $promotion) {
            return $this->eligibleBasis($promotion, collect([$items[$key]]))[1] > 0;
        })->values();

        return $items->map(function (array $item, $index) use ($promotion, $eligibleIndexes, $eligibleSubtotal, $originalDiscount, &$remaining) {
            if (! $eligibleIndexes->contains($index)) {
                return $item;
            }

            $basis = max(0, (float) $item['gross_amount'] - (float) ($item['discount_amount'] ?? 0));
            $isLast = $eligibleIndexes->last() === $index;
            $lineDiscount = $isLast ? $remaining : round($basis / max($eligibleSubtotal, 0.01) * $originalDiscount, 2);
            $lineDiscount = min($lineDiscount, $basis);
            $remaining = round($remaining - $lineDiscount, 2);

            $item['promotion_id'] = $promotion->id;
            $item['promotion_discount_amount'] = round((float) ($item['promotion_discount_amount'] ?? 0) + $lineDiscount, 2);
            $item['discount'] = round((float) ($item['discount'] ?? 0) + $lineDiscount, 2);
            $item['discount_amount'] = round((float) ($item['discount_amount'] ?? 0) + $lineDiscount, 2);
            $item['net_amount'] = max(0, round((float) ($item['gross_amount'] ?? 0) - (float) ($item['discount_amount'] ?? 0) + (float) ($item['tax_amount'] ?? 0), 2));
            $item['total'] = $item['net_amount'];
            $item['total_amount'] = $item['net_amount'];

            return $item;
        });
    }

    private function hasCompletedBusiness(Customer $customer): bool
    {
        return Invoice::withoutTenantScope()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->where('status', Invoice::STATUS_ISSUED)
            ->whereIn('payment_status', [Invoice::PAYMENT_PAID, Invoice::PAYMENT_PARTIAL])
            ->exists()
            || $customer->appointments()
                ->where('status', 'completed')
                ->exists();
    }

    private function hasActiveMembership(Promotion $promotion, Customer $customer): bool
    {
        $query = CustomerMembership::withoutTenantScope()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->where('status', CustomerMembership::STATUS_ACTIVE)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today());

        $allowedPlanIds = $promotion->membershipPlans->pluck('id');

        if ($allowedPlanIds->isNotEmpty()) {
            $query->whereIn('membership_plan_id', $allowedPlanIds);
        }

        return $query->exists();
    }

    private function ineligible(string $message, Promotion $promotion, ?PromotionCoupon $coupon): PromotionApplicationResult
    {
        return new PromotionApplicationResult(false, 0.0, $message, $promotion, $coupon);
    }
}
