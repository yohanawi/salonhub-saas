<?php

namespace App\Services\Customers;

use App\Models\Customer;
use App\Services\Messaging\PhoneNumber;
use App\Services\Settings\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Finds customers that are probably the same person, matching on phone and/or email as the salon's
 * "Duplicate Customer Detection" setting says. Phones match however they were typed (077…, +9477…, spaces).
 */
class CustomerDuplicateService
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * @return Collection<int, Customer>
     */
    public function find(int $tenantId, ?string $phone, ?string $email, ?int $ignoreCustomerId = null): Collection
    {
        $mode = (string) $this->settings->get('customer.duplicate_detection', $tenantId);
        $checkPhone = in_array($mode, ['phone', 'phone_email'], true) ? PhoneNumber::normalize($phone) : null;
        $checkEmail = in_array($mode, ['email', 'phone_email'], true) && filled($email) ? strtolower(trim($email)) : null;

        if (! $checkPhone && ! $checkEmail) {
            return collect();
        }

        // Narrowed in SQL by the last 9 digits, then compared exactly once normalized.
        $phoneTail = $checkPhone ? substr($checkPhone, -9) : null;

        return Customer::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->when($ignoreCustomerId, fn (Builder $query) => $query->whereKeyNot($ignoreCustomerId))
            ->where(function (Builder $query) use ($phoneTail, $checkEmail) {
                if ($phoneTail) {
                    $query->orWhere('phone', 'like', '%' . implode('%', str_split($phoneTail)) . '%');
                }

                if ($checkEmail) {
                    $query->orWhereRaw('LOWER(email) = ?', [$checkEmail]);
                }
            })
            ->orderBy('id')
            ->get()
            ->filter(fn (Customer $customer) => ($checkPhone && PhoneNumber::normalize($customer->phone) === $checkPhone)
                || ($checkEmail && strtolower((string) $customer->email) === $checkEmail))
            ->values();
    }

    /**
     * @return array<int, string> A readable reason per matching field, for the form errors.
     */
    public function describe(Customer $existing, ?string $phone, ?string $email): array
    {
        $reasons = [];

        if (PhoneNumber::normalize($phone) && PhoneNumber::normalize($existing->phone) === PhoneNumber::normalize($phone)) {
            $reasons['phone'] = 'phone';
        }

        if (filled($email) && strtolower((string) $existing->email) === strtolower(trim($email))) {
            $reasons['email'] = 'email';
        }

        return $reasons;
    }
}
