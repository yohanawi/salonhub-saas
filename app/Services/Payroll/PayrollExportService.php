<?php

namespace App\Services\Payroll;

use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\Settings\SettingService;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollExportService
{
    /**
     * Rows for the bank's bulk salary transfer upload: one per paid staff member with an account.
     * Staff without bank details are listed at the end with a blank account so they are not missed.
     */
    public function bankTransferRows(PayrollRun $run): Collection
    {
        // The account salaries are paid from (Settings > Team > Payroll), which bank bulk-transfer files ask for.
        $debitAccount = (string) app(SettingService::class)->get('payroll.employer_bank_account', $run->tenant_id);

        return $run->items()
            ->with(['staff', 'salaryStructure'])
            ->whereIn('status', [PayrollItem::STATUS_APPROVED, PayrollItem::STATUS_PAID])
            ->where('net_pay', '>', 0)
            ->get()
            ->map(function (PayrollItem $item) use ($run, $debitAccount) {
                $structure = $item->salaryStructure;
                $amount = (float) $item->balance_amount > 0 ? (float) $item->balance_amount : (float) $item->net_pay;

                return [
                    'debit_account' => $debitAccount,
                    'employee_code' => $item->staff?->employee_code,
                    'employee_name' => $item->staff?->full_name,
                    'account_name' => $structure?->bank_account_name ?: $item->staff?->full_name,
                    'account_number' => $structure?->bank_account_number,
                    'bank_name' => $structure?->bank_name,
                    'bank_code' => $structure?->bank_code,
                    'branch_code' => $structure?->bank_branch_code,
                    'amount' => number_format($amount, 2, '.', ''),
                    'reference' => $run->run_number,
                    'missing_details' => blank($structure?->bank_account_number) || blank($structure?->bank_code),
                ];
            })
            ->sortBy('missing_details')
            ->values();
    }

    /**
     * EPF (C-form style) and ETF contributions for every approved or paid payroll item in the period.
     */
    public function statutoryRows(PayrollPeriod $period): Collection
    {
        return PayrollItem::withoutTenantScope()
            ->with(['staff', 'salaryStructure'])
            ->where('tenant_id', $period->tenant_id)
            ->where('payroll_period_id', $period->id)
            ->whereIn('status', [PayrollItem::STATUS_APPROVED, PayrollItem::STATUS_PAID])
            ->where(fn ($query) => $query->where('epf_employee_amount', '>', 0)->orWhere('epf_employer_amount', '>', 0)->orWhere('etf_employer_amount', '>', 0))
            ->get()
            ->sortBy(fn (PayrollItem $item) => $item->staff?->full_name)
            ->values()
            ->map(fn (PayrollItem $item) => [
                'employee_name' => $item->staff?->full_name,
                'employee_code' => $item->staff?->employee_code,
                'epf_number' => $item->salaryStructure?->epf_number,
                'earnings' => (float) $item->statutory_earnings,
                'epf_employee' => (float) $item->epf_employee_amount,
                'epf_employer' => (float) $item->epf_employer_amount,
                'epf_total' => round((float) $item->epf_employee_amount + (float) $item->epf_employer_amount, 2),
                'etf' => (float) $item->etf_employer_amount,
            ]);
    }

    public function csv(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headings);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
