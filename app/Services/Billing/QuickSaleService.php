<?php

namespace App\Services\Billing;

use App\Http\Requests\Billing\QuickSaleRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\CustomerManagementService;
use App\Services\Customers\CustomerDuplicateService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates a quick sale from validated form data, for the normal form and for sales the POS saved
 * offline and syncs later. The page's client_uuid makes a repeated submit return the first invoice.
 */
class QuickSaleService
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly CustomerManagementService $customers,
        private readonly PlanEntitlementService $entitlements,
        private readonly AuditLogService $audit
    ) {
    }

    public function existing(int $tenantId, ?string $clientUuid): ?Invoice
    {
        return filled($clientUuid)
            ? Invoice::withoutTenantScope()->where('tenant_id', $tenantId)->where('client_uuid', $clientUuid)->first()
            : null;
    }

    public function create(Branch $branch, array $data, User $user): Invoice
    {
        $tenant = $user->tenant;

        if ($existing = $this->existing($tenant->id, $data['client_uuid'] ?? null)) {
            return $existing;
        }

        $existingCustomer = null;

        if ($data['customer_mode'] === QuickSaleRequest::CUSTOMER_NEW) {
            if (! $this->entitlements->featureEnabled($tenant, 'customer_management')) {
                throw ValidationException::withMessages([
                    'customer_mode' => 'Your subscription plan does not include customer management.',
                ]);
            }

            // At the till there is no form to warn on, so a phone/email already on file reuses that customer.
            $existingCustomer = app(CustomerDuplicateService::class)
                ->find($tenant->id, $data['new_customer']['phone'] ?? null, $data['new_customer']['email'] ?? null)
                ->first();

            if (! $existingCustomer) {
                $this->entitlements->ensureCanCreate($tenant, 'max_customers', 'Your subscription customer limit has been reached.');
            }
        }

        try {
            // The new customer and the sale commit together, so a failed payment leaves no orphan customer.
            $invoice = DB::transaction(function () use ($data, $tenant, $branch, $user, $existingCustomer) {
                $customer = match ($data['customer_mode']) {
                    QuickSaleRequest::CUSTOMER_EXISTING => Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->findOrFail($data['customer_id']),
                    QuickSaleRequest::CUSTOMER_NEW => $existingCustomer ?? $this->customers->create($tenant, [
                        'branch_id' => $branch->id,
                        'first_name' => $data['new_customer']['first_name'],
                        'last_name' => $data['new_customer']['last_name'] ?? null,
                        'phone' => $data['new_customer']['phone'],
                        'email' => $data['new_customer']['email'] ?? null,
                        'status' => Customer::STATUS_ACTIVE,
                    ], $user),
                    default => null,
                };

                $invoice = $this->checkout->quickSale($branch, $customer, $data, $user);

                $invoice->forceFill([
                    'client_uuid' => $data['client_uuid'] ?? null,
                    'offline_reference' => ! empty($data['offline']) ? ($data['offline_reference'] ?? null) : null,
                    'offline_created_at' => ! empty($data['offline']) ? ($data['offline_created_at'] ?? null) : null,
                ])->save();

                return $invoice;
            });
        } catch (UniqueConstraintViolationException $exception) {
            // Two submits of the same sale raced; the other one won.
            if ($existing = $this->existing($tenant->id, $data['client_uuid'] ?? null)) {
                return $existing;
            }

            throw $exception;
        }

        if (! empty($data['offline'])) {
            $this->audit->log([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'user' => $user,
                'action' => 'offline_sale_synced',
                'event' => 'billing.offline_sale_synced',
                'module' => 'billing',
                'auditable' => $invoice,
                'description' => 'Sale ' . ($data['offline_reference'] ?? '') . ' taken offline and synced as ' . $invoice->invoice_number,
                'metadata' => ['offline_created_at' => $data['offline_created_at'] ?? null, 'source' => 'offline_pos'],
            ]);
        }

        return $invoice;
    }
}
