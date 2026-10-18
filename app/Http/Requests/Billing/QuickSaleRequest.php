<?php

namespace App\Http\Requests\Billing;

use App\Http\Requests\Billing\Concerns\ResolvesPosCashier;
use App\Http\Requests\Billing\Concerns\ValidatesSalePayment;
use App\Http\Controllers\Billing\QuickSaleController;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\Billing\QuickSaleService;
use App\Services\BranchContext;
use App\Services\Settings\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuickSaleRequest extends FormRequest
{
    use ResolvesPosCashier;
    use ValidatesSalePayment;

    public const CUSTOMER_EXISTING = 'existing';
    public const CUSTOMER_NEW = 'new';
    public const CUSTOMER_WALK_IN = 'walk_in';

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->tenant_id !== null && $user->can('create', Invoice::class);
    }

    public function rules(): array
    {
        return $this->salePaymentRules() + $this->posCashierRules() + [
            'customer_mode' => ['required', Rule::in([self::CUSTOMER_EXISTING, self::CUSTOMER_NEW, self::CUSTOMER_WALK_IN])],
            'customer_id' => ['nullable', 'required_if:customer_mode,' . self::CUSTOMER_EXISTING, 'integer'],
            'new_customer.first_name' => ['nullable', 'required_if:customer_mode,' . self::CUSTOMER_NEW, 'string', 'max:100'],
            'new_customer.last_name' => ['nullable', 'string', 'max:100'],
            'new_customer.phone' => ['nullable', 'required_if:customer_mode,' . self::CUSTOMER_NEW, 'string', 'max:30'],
            'new_customer.email' => ['nullable', 'email', 'max:255'],
            'service_items' => ['nullable', 'array'],
            'service_items.*' => ['array'],
            'service_items.*.service_id' => ['nullable', 'integer'],
            'service_items.*.staff_id' => ['nullable', 'integer'],
            'service_items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'product_items.*.staff_id' => ['nullable', 'integer'],
            'client_uuid' => ['nullable', 'uuid'],
            'offline' => ['nullable', 'boolean'],
            'offline_reference' => ['nullable', 'string', 'max:40'],
            'offline_created_at' => ['nullable', 'date', 'before_or_equal:' . now()->addMinutes(5)->toDateTimeString()],
        ];
    }

    protected function extraAttributes(): array
    {
        return [
            'customer_id' => 'customer',
            'new_customer.first_name' => 'first name',
            'new_customer.phone' => 'phone number',
            'new_customer.email' => 'email',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tenantId = (int) $this->user()->tenant_id;
                $branch = app(BranchContext::class)->current($this->user());

                if (! $branch) {
                    $validator->errors()->add('branch', 'Select an active branch before continuing.');
                    return;
                }

                $hasItems = collect((array) $this->input('service_items', []))->contains(fn ($line) => is_array($line) && filled($line['service_id'] ?? null))
                    || collect((array) $this->input('product_items', []))->contains(fn ($line) => is_array($line) && filled($line['product_id'] ?? null));

                if (! $hasItems) {
                    $validator->errors()->add('items', 'Add at least one service or product to the sale.');
                }

                $this->validateCustomer($validator, $tenantId, $branch->id);
                $this->validateSalePayment($validator, $tenantId, $branch->id);

                // A sale taken while offline could not check a PIN at the counter; it is recorded without
                // a cashier and audited as an offline sale instead.
                if (! $this->boolean('offline')) {
                    $this->validatePosCashier($validator, $branch);
                }
            },
        ];
    }

    /**
     * A sale that was already saved (the response was lost, or an offline sale synced twice) can fail
     * validation the second time, e.g. when its stock is now sold. Answer with the saved invoice instead.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $uuid = (string) $this->input('client_uuid');
        $existing = Str::isUuid($uuid) ? app(QuickSaleService::class)->existing((int) $this->user()->tenant_id, $uuid) : null;

        if ($existing) {
            throw new HttpResponseException(QuickSaleController::saleResponse($this, $existing, true));
        }

        parent::failedValidation($validator);
    }

    private function validateCustomer(Validator $validator, int $tenantId, int $branchId): void
    {
        $mode = $this->input('customer_mode');

        if ($mode === self::CUSTOMER_EXISTING && $this->filled('customer_id')) {
            $customer = Customer::withoutTenantScope()->where('tenant_id', $tenantId)->find($this->integer('customer_id'));

            if (! $customer) {
                $validator->errors()->add('customer_id', 'The selected customer does not belong to this salon.');
            } elseif ($customer->status === Customer::STATUS_BLOCKED) {
                $validator->errors()->add('customer_id', 'Blocked customers cannot be billed.');
            }
        }

        if ($mode === self::CUSTOMER_NEW && ! $this->user()->can('create', Customer::class)) {
            $validator->errors()->add('customer_mode', 'You do not have permission to add customers.');
        }

        if ($mode === self::CUSTOMER_WALK_IN && ! app(SettingService::class)->get('booking.allow_walk_ins', $tenantId, $branchId)) {
            $validator->errors()->add('customer_mode', 'Walk-in sales without a customer are disabled. Select or add a customer.');
        }

        if ($mode !== self::CUSTOMER_EXISTING && (int) $this->input('loyalty_points_to_redeem', 0) > 0) {
            $validator->errors()->add('loyalty_points_to_redeem', 'Loyalty points can only be redeemed for a registered customer.');
        }
    }
}
