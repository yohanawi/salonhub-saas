<?php

namespace App\Http\Requests\Billing\Concerns;

use App\Models\Branch;
use App\Services\Billing\PosPinService;
use Illuminate\Validation\Validator;

/**
 * Confirms which cashier is taking a sale or payment, by staff PIN when the salon requires it.
 */
trait ResolvesPosCashier
{
    private ?int $resolvedCashierId = null;

    protected function posCashierRules(): array
    {
        return [
            'cashier_staff_id' => ['nullable', 'integer'],
            'cashier_pin' => ['nullable', 'string', 'max:12'],
        ];
    }

    protected function validatePosCashier(Validator $validator, Branch $branch): void
    {
        [$staff, $error] = app(PosPinService::class)->resolve(
            $branch,
            $this->user(),
            $this->input('cashier_staff_id'),
            $this->input('cashier_pin')
        );

        if ($error) {
            $validator->errors()->add('cashier_pin', $error);

            return;
        }

        $this->resolvedCashierId = $staff?->id;
    }

    public function cashierStaffId(): ?int
    {
        return $this->resolvedCashierId;
    }
}
