<?php

namespace App\Services\Appointment;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;

class AppointmentPricingService
{
    public function snapshot(Service $service, Branch $branch, ?Staff $staff = null, float $discount = 0): array
    {
        $service->loadMissing(['branches', 'staff']);

        $staffPivot = $staff
            ? $service->staff->firstWhere('id', $staff->id)?->pivot
            : null;

        $duration = (int) ($staffPivot?->custom_duration_minutes ?: $service->effectiveDurationFor($branch));
        $unitPrice = (float) ($staffPivot?->custom_price ?: $service->effectivePriceFor($branch));
        $discount = max(0, $discount);

        return [
            'service_name' => $service->name,
            'duration_minutes' => max(5, $duration),
            'unit_price' => $unitPrice,
            'discount_amount' => min($discount, $unitPrice),
            'total_price' => max(0, $unitPrice - $discount),
        ];
    }

    public function totals(array $serviceSnapshots, float $appointmentDiscount = 0, float $taxAmount = 0): array
    {
        $subtotal = collect($serviceSnapshots)->sum(fn (array $snapshot) => (float) $snapshot['unit_price']);
        $lineDiscount = collect($serviceSnapshots)->sum(fn (array $snapshot) => (float) $snapshot['discount_amount']);
        $appointmentDiscount = min(max(0, $appointmentDiscount), max(0, $subtotal - $lineDiscount));
        $discount = min($subtotal, $lineDiscount + $appointmentDiscount);
        $taxAmount = max(0, $taxAmount);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $taxAmount,
            'total_amount' => max(0, $subtotal - $discount + $taxAmount),
        ];
    }
}
