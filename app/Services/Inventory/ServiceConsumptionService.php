<?php

namespace App\Services\Inventory;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\PlanEntitlementService;
use App\Services\Settings\SettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Deducts the products a service uses up (its "consumables") from branch stock once the work is done.
 */
class ServiceConsumptionService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly PlanEntitlementService $entitlements,
    ) {
    }

    /**
     * @return array<int, string> Products that could not be deducted because the branch ran out of stock.
     */
    public function consumeForAppointment(Appointment $appointment, User $user): array
    {
        $appointment->loadMissing(['tenant', 'branch', 'appointmentServices.service.consumables']);

        if (! $appointment->branch || ! $this->entitlements->featureEnabled($appointment->tenant, 'inventory')) {
            return [];
        }

        // Settings > Inventory can turn this off for salons that count service products by hand.
        if (! app(SettingService::class)->get('inventory.auto_deduct_stock', $appointment->tenant_id, $appointment->branch_id)) {
            return [];
        }

        $quantities = $this->requiredQuantities($appointment);
        $shortages = [];

        DB::transaction(function () use ($appointment, $user, $quantities, &$shortages) {
            // Serialises completion and retries, so the same products are never deducted twice.
            Appointment::withoutTenantScope()->whereKey($appointment->id)->lockForUpdate()->first();

            // Only what is still owed is deducted: a retry after restocking picks up just the earlier shortfall.
            $consumed = $this->consumedQuantities($appointment);

            foreach ($quantities as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $remaining = $line['quantity'] - ($consumed[$product->id] ?? 0);

                if ($remaining <= 0) {
                    continue;
                }

                try {
                    $this->inventory->decrease(
                        $product,
                        $appointment->branch,
                        $remaining,
                        StockMovement::TYPE_SERVICE_CONSUMPTION,
                        $user,
                        $appointment,
                        'Service consumption',
                        "{$appointment->appointment_number}: " . implode(', ', array_unique($line['services']))
                    );
                } catch (ValidationException) {
                    // A finished service is never blocked by stock levels; the shortfall is recorded instead.
                    $shortages[] = ['product_id' => $product->id, 'name' => $product->name, 'quantity' => $remaining];
                }
            }

            $appointment->forceFill(['stock_shortages' => $shortages ?: null])->saveQuietly();
        });

        return array_map(fn (array $shortage) => "{$shortage['name']} (needed {$shortage['quantity']})", $shortages);
    }

    /**
     * Totals each consumable across the appointment's service lines, so one product used by two services
     * becomes a single stock movement.
     */
    private function requiredQuantities(Appointment $appointment): array
    {
        $lines = [];

        $appointment->appointmentServices
            ->reject(fn (AppointmentService $line) => in_array($line->status, [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW], true))
            ->each(function (AppointmentService $line) use (&$lines, $appointment) {
                foreach ($line->service?->consumables ?? [] as $product) {
                    if (! $product->track_inventory || (int) $product->tenant_id !== (int) $appointment->tenant_id) {
                        continue;
                    }

                    $lines[$product->id] ??= ['product' => $product, 'quantity' => 0, 'services' => []];
                    $lines[$product->id]['quantity'] += max(1, (int) $product->pivot->quantity);
                    $lines[$product->id]['services'][] = $line->service_name ?? $line->service->name;
                }
            });

        return array_values($lines);
    }

    /**
     * @return array<int, int> Units already deducted for this appointment, by product id.
     */
    private function consumedQuantities(Appointment $appointment): array
    {
        return StockMovement::withoutTenantScope()
            ->where('tenant_id', $appointment->tenant_id)
            ->where('type', StockMovement::TYPE_SERVICE_CONSUMPTION)
            ->where('reference_type', $appointment->getMorphClass())
            ->where('reference_id', $appointment->id)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(ABS(quantity)) as consumed')
            ->pluck('consumed', 'product_id')
            ->map(fn ($consumed) => (int) $consumed)
            ->all();
    }
}
