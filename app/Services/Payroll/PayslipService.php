<?php

namespace App\Services\Payroll;

use App\Models\PayrollItem;

class PayslipService
{
    public function data(PayrollItem $item): array
    {
        $item->loadMissing(['tenant', 'branch', 'staff', 'period', 'run', 'lines', 'payments']);

        return [
            'item' => $item,
            'earnings' => $item->lines->where('line_type', 'earning'),
            'deductions' => $item->lines->where('line_type', 'deduction'),
        ];
    }
}
