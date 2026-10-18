<?php

namespace App\Services\Billing;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerPackage;
use App\Models\CustomerPackageItem;
use App\Models\CustomerPackageUsage;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Prepaid service bundles (for example "5 facials for Rs. 20,000"). Buying one gives the customer a
 * credit per service; at checkout a covered service line is discounted in full and a credit is used.
 */
class PackageService
{
    public function __construct(private readonly StandaloneSaleService $sales)
    {
    }

    public function sell(Branch $branch, Customer $customer, ServicePackage $package, array $data, User $user, ?int $cashierStaffId = null): CustomerPackage
    {
        $package->loadMissing('items.service');

        if ((int) $package->tenant_id !== (int) $branch->tenant_id || (int) $customer->tenant_id !== (int) $branch->tenant_id) {
            throw ValidationException::withMessages(['service_package_id' => 'Select a package and customer from this salon.']);
        }

        if (! $package->is_active || $package->items->isEmpty()) {
            throw ValidationException::withMessages(['service_package_id' => 'Select an active package that includes at least one service.']);
        }

        return DB::transaction(function () use ($branch, $customer, $package, $data, $user, $cashierStaffId) {
            $purchasedAt = now();

            $customerPackage = CustomerPackage::create([
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'service_package_id' => $package->id,
                'name' => $package->name,
                'price_paid' => $package->price,
                'purchased_at' => $purchasedAt,
                'expires_at' => $package->validity_days ? $purchasedAt->copy()->addDays($package->validity_days)->toDateString() : null,
                'status' => CustomerPackage::STATUS_ACTIVE,
                'created_by' => $user->id,
            ]);

            $customerPackage->update(['package_number' => 'PKG-' . $purchasedAt->format('Y') . '-' . str_pad((string) $customerPackage->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($package->items as $item) {
                $customerPackage->items()->create([
                    'tenant_id' => $branch->tenant_id,
                    'service_id' => $item->service_id,
                    'service_name' => $item->service?->name ?? 'Service',
                    'total_quantity' => $item->quantity,
                    'used_quantity' => 0,
                ]);
            }

            $invoice = $this->sales->sell($branch, $customer, [
                'item_type' => 'package',
                'item_id' => $package->id,
                'item_name' => $package->name,
                'description' => $package->summary,
                'amount' => (float) $package->price,
            ], $data, $user, $cashierStaffId);

            $customerPackage->update(['invoice_id' => $invoice->id]);

            return $customerPackage->fresh(['items', 'invoice', 'customer']);
        });
    }

    /**
     * Remaining credits per service for the customer's usable packages.
     *
     * @return Collection<int, int> service_id => remaining credits
     */
    public function availableCredits(?Customer $customer): Collection
    {
        if (! $customer) {
            return collect();
        }

        return $this->usableItems($customer)
            ->groupBy('service_id')
            ->map(fn (Collection $items) => $items->sum(fn (CustomerPackageItem $item) => $item->remainingQuantity()))
            ->filter(fn (int $remaining) => $remaining > 0);
    }

    /**
     * Remaining credits for a list of customers, for the POS to estimate covered services.
     *
     * @return array<int, array<int, int>> customer_id => [service_id => remaining credits]
     */
    public function creditsByCustomer(int $tenantId, Collection $customerIds): array
    {
        if ($customerIds->isEmpty()) {
            return [];
        }

        return CustomerPackageItem::withoutTenantScope()
            ->join('customer_packages', 'customer_packages.id', '=', 'customer_package_items.customer_package_id')
            ->where('customer_package_items.tenant_id', $tenantId)
            ->whereIn('customer_packages.customer_id', $customerIds)
            ->where('customer_packages.status', CustomerPackage::STATUS_ACTIVE)
            ->where(fn ($query) => $query->whereNull('customer_packages.expires_at')->orWhereDate('customer_packages.expires_at', '>=', today()))
            ->whereColumn('customer_package_items.used_quantity', '<', 'customer_package_items.total_quantity')
            ->get(['customer_packages.customer_id', 'customer_package_items.service_id', 'customer_package_items.total_quantity', 'customer_package_items.used_quantity'])
            ->groupBy('customer_id')
            ->map(fn (Collection $rows) => $rows->groupBy('service_id')->map(fn (Collection $items) => (int) $items->sum(fn ($item) => $item->total_quantity - $item->used_quantity))->all())
            ->all();
    }

    /**
     * Discounts service lines covered by the customer's package credits. Runs before membership and
     * promotion discounts, which then only see what the package did not cover.
     *
     * @return array{items: Collection, discount: float}
     */
    public function applyToItemSnapshots(?Customer $customer, Collection $items, bool $enabled = true): array
    {
        if (! $customer || ! $enabled) {
            return ['items' => $items, 'discount' => 0.0];
        }

        // Locked so two checkouts for the same customer cannot spend the same last credit.
        $available = $this->usableItems($customer, lock: true)
            ->mapWithKeys(fn (CustomerPackageItem $item) => [$item->id => ['item' => $item, 'remaining' => $item->remainingQuantity()]])
            ->all();

        $discountTotal = 0.0;

        $items = $items->map(function (array $line) use (&$available, &$discountTotal) {
            if (($line['item_type'] ?? null) !== 'service' || blank($line['service_id'] ?? null)) {
                return $line;
            }

            $quantity = max(1, (int) ($line['quantity'] ?? 1));

            foreach ($available as $id => $credit) {
                if ((int) $credit['item']->service_id !== (int) $line['service_id'] || $credit['remaining'] <= 0) {
                    continue;
                }

                $covered = min($quantity, $credit['remaining']);
                $available[$id]['remaining'] -= $covered;
                $discount = round(min((float) $line['gross_amount'], (float) $line['unit_price'] * $covered), 2);
                $discountTotal += $discount;
                $totalDiscount = round((float) ($line['discount_amount'] ?? 0) + $discount, 2);
                $net = max(0, round((float) $line['gross_amount'] - $totalDiscount, 2));

                // One package line per invoice item keeps the usage record simple.
                return array_merge($line, [
                    'customer_package_item_id' => $id,
                    'package_quantity' => $covered,
                    'package_discount_amount' => $discount,
                    'discount' => $totalDiscount,
                    'discount_amount' => $totalDiscount,
                    'net_amount' => $net,
                    'total' => $net,
                    'total_amount' => $net,
                ]);
            }

            return $line;
        });

        return ['items' => $items, 'discount' => round($discountTotal, 2)];
    }

    public function recordUsage(Invoice $invoice): void
    {
        $invoice->items()
            ->whereNotNull('customer_package_item_id')
            ->where('package_quantity', '>', 0)
            ->get()
            ->each(function (InvoiceItem $item) use ($invoice) {
                $credit = CustomerPackageItem::withoutTenantScope()->whereKey($item->customer_package_item_id)->lockForUpdate()->first();

                if (! $credit || $credit->remainingQuantity() < $item->package_quantity) {
                    throw ValidationException::withMessages(['items' => 'The customer no longer has enough package credits for ' . $item->item_name . '.']);
                }

                $credit->increment('used_quantity', $item->package_quantity);

                CustomerPackageUsage::create([
                    'tenant_id' => $invoice->tenant_id,
                    'customer_package_item_id' => $credit->id,
                    'invoice_id' => $invoice->id,
                    'invoice_item_id' => $item->id,
                    'quantity' => $item->package_quantity,
                    'value' => $item->package_discount_amount,
                ]);

                $this->refreshStatus($credit->customerPackage);
            });
    }

    /**
     * Voiding an invoice gives back the package credits it used.
     */
    public function reverseUsageForInvoice(Invoice $invoice): void
    {
        CustomerPackageUsage::withoutTenantScope()
            ->where('invoice_id', $invoice->id)
            ->whereNull('reversed_at')
            ->get()
            ->each(function (CustomerPackageUsage $usage) {
                $credit = CustomerPackageItem::withoutTenantScope()->whereKey($usage->customer_package_item_id)->lockForUpdate()->first();

                if ($credit) {
                    $credit->update(['used_quantity' => max(0, $credit->used_quantity - $usage->quantity)]);
                    $this->refreshStatus($credit->customerPackage);
                }

                $usage->update(['reversed_at' => now()]);
            });
    }

    /**
     * Voiding the invoice that sold a package cancels it, which is only safe while no credit is used.
     */
    public function cancelSoldOnInvoice(Invoice $invoice): void
    {
        CustomerPackage::withoutTenantScope()
            ->where('invoice_id', $invoice->id)
            ->with('items')
            ->lockForUpdate()
            ->get()
            ->each(function (CustomerPackage $package) {
                if ($package->items->sum('used_quantity') > 0) {
                    throw ValidationException::withMessages([
                        'invoice' => "Package {$package->package_number} has already been used, so this sale cannot be voided.",
                    ]);
                }

                $package->update(['status' => CustomerPackage::STATUS_CANCELLED]);
            });
    }

    private function usableItems(Customer $customer, bool $lock = false): Collection
    {
        return CustomerPackageItem::withoutTenantScope()
            ->where('tenant_id', $customer->tenant_id)
            ->whereHas('customerPackage', fn ($query) => $query->withoutGlobalScopes()->where('customer_id', $customer->id)->usable())
            ->whereColumn('used_quantity', '<', 'total_quantity')
            ->with(['customerPackage' => fn ($query) => $query->withoutGlobalScopes()])
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->get()
            // Spend the credits that expire first.
            ->sortBy(fn (CustomerPackageItem $item) => [$item->customerPackage->expires_at?->timestamp ?? PHP_INT_MAX, $item->id])
            ->values();
    }

    private function refreshStatus(?CustomerPackage $package): void
    {
        if (! $package || $package->status === CustomerPackage::STATUS_CANCELLED) {
            return;
        }

        $remaining = $package->items()->get()->sum(fn (CustomerPackageItem $item) => $item->remainingQuantity());

        $package->update(['status' => $remaining > 0 ? CustomerPackage::STATUS_ACTIVE : CustomerPackage::STATUS_COMPLETED]);
    }
}
