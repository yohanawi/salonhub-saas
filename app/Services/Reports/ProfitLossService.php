<?php

namespace App\Services\Reports;

use App\Models\CommissionPayout;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PayrollItem;
use App\Models\Refund;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Support\Reports\ReportDocument;
use App\Support\Reports\ReportTable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Profit & loss on an accrual basis:
 *   Revenue (invoiced, after discounts, before tax) − refunds − cost of goods (product cost of sales and
 *   service consumables) = gross profit; − expenses − staff cost (payroll + employer EPF/ETF + commission
 *   paid outside payroll) = net profit. Tips are excluded on both sides: they belong to staff.
 */
class ProfitLossService
{
    /** Invoice line types that are income. Gift cards are prepaid value, earned when redeemed. */
    public const INCOME_TYPES = [
        'service' => 'Services',
        'product' => 'Retail products',
        'package' => 'Packages',
        'membership' => 'Memberships',
    ];

    /**
     * @param  array<int>  $branchIds
     */
    public function build(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $income = $this->income($tenant, $branchIds, $from, $to);
        $refunds = $this->refunds($tenant, $branchIds, $from, $to);
        $cogs = $this->costOfGoods($tenant, $branchIds, $from, $to);
        $expenses = $this->expenses($tenant, $branchIds, $from, $to);
        $staff = $this->staffCost($tenant, $branchIds, $from, $to);

        $revenue = round(array_sum(array_column($income, 'amount')), 2);
        $netRevenue = round($revenue - $refunds, 2);
        $grossProfit = round($netRevenue - $cogs['total'], 2);
        $expenseTotal = round(array_sum(array_column($expenses, 'amount')), 2);
        $operatingCost = round($expenseTotal + $staff['total'], 2);
        $netProfit = round($grossProfit - $operatingCost, 2);

        return [
            'from' => $from,
            'to' => $to,
            'income' => $income,
            'revenue' => $revenue,
            'refunds' => $refunds,
            'net_revenue' => $netRevenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_margin' => $netRevenue > 0 ? round($grossProfit / $netRevenue * 100, 1) : 0.0,
            'expenses' => $expenses,
            'expense_total' => $expenseTotal,
            'staff' => $staff,
            'operating_cost' => $operatingCost,
            'net_profit' => $netProfit,
            'net_margin' => $netRevenue > 0 ? round($netProfit / $netRevenue * 100, 1) : 0.0,
            'tax_collected' => $this->taxCollected($tenant, $branchIds, $from, $to),
        ];
    }

    /**
     * The same report for the period of equal length just before, for comparison.
     */
    public function previous(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = $from->diffInDays($to) + 1;

        return $this->build($tenant, $branchIds, $from->subDays((int) round($days))->startOfDay(), $from->subDay()->endOfDay());
    }

    public function document(Tenant $tenant, array $report, string $branchLabel): ReportDocument
    {
        $currency = $tenant->currency ?: 'LKR';
        $money = fn ($value) => $currency . ' ' . number_format((float) $value, 2);

        $document = new ReportDocument('Profit & Loss', $tenant->name, [
            'Period' => $report['from']->format('M d, Y') . ' – ' . $report['to']->format('M d, Y'),
            'Branch' => $branchLabel,
        ], [
            'Net revenue' => $money($report['net_revenue']),
            'Gross profit' => $money($report['gross_profit']) . " ({$report['gross_margin']}%)",
            'Operating costs' => $money($report['operating_cost']),
            'Net profit' => $money($report['net_profit']) . " ({$report['net_margin']}%)",
        ], $currency);

        $rows = [];
        foreach ($report['income'] as $line) {
            $rows[] = ['Revenue', $line['label'], $line['amount']];
        }
        $rows[] = ['Revenue', 'Less: refunds', -$report['refunds']];
        $rows[] = ['', 'Net revenue', $report['net_revenue']];
        $rows[] = ['Cost of sales', 'Retail products sold (at cost)', -$report['cogs']['products']];
        $rows[] = ['Cost of sales', 'Service consumables used', -$report['cogs']['consumables']];
        $rows[] = ['', 'Gross profit', $report['gross_profit']];
        foreach ($report['expenses'] as $line) {
            $rows[] = ['Expenses', $line['label'], -$line['amount']];
        }
        $rows[] = ['Staff', 'Salaries & wages (incl. commission)', -$report['staff']['payroll']];
        $rows[] = ['Staff', 'Employer EPF / ETF', -$report['staff']['statutory']];
        $rows[] = ['Staff', 'Commission paid outside payroll', -$report['staff']['commission_payouts']];

        return $document->table(new ReportTable('Profit & Loss Statement', ['Section', 'Line', 'Amount'], $rows, [2], ['', 'Net profit', $report['net_profit']]));
    }

    private function income(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $totals = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.tenant_id', $tenant->id)
            ->when($branchIds !== [], fn ($query) => $query->whereIn('sales.branch_id', $branchIds))
            ->where('sales.status', Invoice::STATUS_ISSUED)
            ->whereBetween('sales.issued_at', [$from, $to])
            ->whereIn('sale_items.item_type', array_keys(self::INCOME_TYPES))
            ->groupBy('sale_items.item_type')
            ->selectRaw('sale_items.item_type, SUM(sale_items.gross_amount - sale_items.discount_amount) as amount')
            ->pluck('amount', 'item_type');

        return collect(self::INCOME_TYPES)
            ->map(fn (string $label, string $type) => ['type' => $type, 'label' => $label, 'amount' => round((float) ($totals[$type] ?? 0), 2)])
            ->values()
            ->all();
    }

    private function refunds(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): float
    {
        return round((float) Refund::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->when($branchIds !== [], fn ($query) => $query->whereIn('branch_id', $branchIds))
            ->where('status', Refund::STATUS_COMPLETED)
            ->whereBetween('processed_at', [$from, $to])
            ->sum('amount'), 2);
    }

    private function costOfGoods(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $costs = StockMovement::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->when($branchIds !== [], fn ($query) => $query->whereIn('branch_id', $branchIds))
            ->whereIn('type', [StockMovement::TYPE_POS_SALE, StockMovement::TYPE_SALE_RETURN, StockMovement::TYPE_SERVICE_CONSUMPTION])
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('type')
            ->selectRaw('type, SUM(total_cost) as cost')
            ->pluck('cost', 'type');

        $products = round((float) ($costs[StockMovement::TYPE_POS_SALE] ?? 0) - (float) ($costs[StockMovement::TYPE_SALE_RETURN] ?? 0), 2);
        $consumables = round((float) ($costs[StockMovement::TYPE_SERVICE_CONSUMPTION] ?? 0), 2);

        return ['products' => $products, 'consumables' => $consumables, 'total' => round($products + $consumables, 2)];
    }

    private function expenses(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return Expense::withoutTenantScope()
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->where('expenses.tenant_id', $tenant->id)
            ->when($branchIds !== [], fn ($query) => $query->whereIn('expenses.branch_id', $branchIds))
            ->where('expenses.expense_status', Expense::STATUS_CONFIRMED)
            ->whereBetween('expenses.expense_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('expense_categories.name')
            ->selectRaw("COALESCE(expense_categories.name, 'Uncategorised') as label, SUM(expenses.total_amount) as amount")
            ->orderByDesc('amount')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'amount' => round((float) $row->amount, 2)])
            ->all();
    }

    /**
     * Payroll is counted in the period its pay period ends in, once approved.
     */
    private function staffCost(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $payroll = PayrollItem::withoutTenantScope()
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_items.payroll_period_id')
            ->where('payroll_items.tenant_id', $tenant->id)
            ->when($branchIds !== [], fn ($query) => $query->whereIn('payroll_items.branch_id', $branchIds))
            ->whereIn('payroll_items.status', [PayrollItem::STATUS_APPROVED, PayrollItem::STATUS_PAID])
            ->whereBetween('payroll_periods.end_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('SUM(payroll_items.gross_pay - payroll_items.tip_amount) as pay, SUM(payroll_items.epf_employer_amount + payroll_items.etf_employer_amount) as statutory')
            ->first();

        $payouts = (float) CommissionPayout::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->when($branchIds !== [], fn ($query) => $query->whereIn('branch_id', $branchIds))
            ->where('status', CommissionPayout::STATUS_PAID)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('net_payable');

        $pay = round((float) ($payroll?->pay ?? 0), 2);
        $statutory = round((float) ($payroll?->statutory ?? 0), 2);

        return [
            'payroll' => $pay,
            'statutory' => $statutory,
            'commission_payouts' => round($payouts, 2),
            'total' => round($pay + $statutory + $payouts, 2),
        ];
    }

    private function taxCollected(Tenant $tenant, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): float
    {
        return round((float) DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.tenant_id', $tenant->id)
            ->when($branchIds !== [], fn ($query) => $query->whereIn('sales.branch_id', $branchIds))
            ->where('sales.status', Invoice::STATUS_ISSUED)
            ->whereBetween('sales.issued_at', [$from, $to])
            ->sum('sale_items.tax_amount'), 2);
    }
}
