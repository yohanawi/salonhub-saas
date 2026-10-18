<?php

namespace App\Services\Billing;

use App\Models\Branch;
use App\Models\Invoice;
use App\Services\Settings\SettingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * Reserves the next per-salon invoice number. Call inside the transaction that creates the
     * invoice: the sequence row stays locked until commit, and a rollback releases the number,
     * so numbers stay gap-free and unique under concurrent checkouts.
     */
    public function next(int $tenantId, ?Branch $branch = null, ?Carbon $issuedAt = null): string
    {
        return DB::transaction(function () use ($tenantId, $branch, $issuedAt) {
            DB::table('invoice_sequences')->insertOrIgnore([
                'tenant_id' => $tenantId,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('invoice_sequences')->where('tenant_id', $tenantId)->lockForUpdate()->first();

            // "Next Invoice Number" in settings acts as a floor, so owners can start from or jump to a number.
            $number = max((int) $sequence->last_number + 1, (int) $this->settings->get('invoice.next_number', $tenantId));

            do {
                $invoiceNumber = $this->format($number, $tenantId, $branch, $issuedAt ?? now());
                $taken = Invoice::withoutTenantScope()->where('tenant_id', $tenantId)->where('invoice_number', $invoiceNumber)->exists();
            } while ($taken && ++$number);

            DB::table('invoice_sequences')->where('id', $sequence->id)->update([
                'last_number' => $number,
                'updated_at' => now(),
            ]);

            return $invoiceNumber;
        });
    }

    private function format(int $number, int $tenantId, ?Branch $branch, Carbon $issuedAt): string
    {
        $format = (string) ($this->settings->get('invoice.number_format', $tenantId) ?: '{PREFIX}-{YEAR}-{NUMBER}');

        if (! str_contains($format, '{NUMBER}')) {
            $format .= '-{NUMBER}';
        }

        return strtr($format, [
            '{PREFIX}' => (string) ($this->settings->get('invoice.prefix', $tenantId) ?: 'INV'),
            '{BRANCH}' => (string) ($branch?->invoice_prefix ?: $branch?->code ?: ''),
            '{YEAR}' => $issuedAt->format('Y'),
            '{MONTH}' => $issuedAt->format('m'),
            '{NUMBER}' => str_pad((string) $number, 6, '0', STR_PAD_LEFT),
        ]);
    }
}
