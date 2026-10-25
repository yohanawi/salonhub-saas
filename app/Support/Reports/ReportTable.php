<?php

namespace App\Support\Reports;

/**
 * One table of an exported report. Columns listed in $moneyColumns are formatted as amounts.
 */
class ReportTable
{
    /**
     * @param  array<int, string>  $headings
     * @param  iterable<array<int, mixed>>  $rows
     * @param  array<int, int>  $moneyColumns  zero-based column indexes
     * @param  array<int, mixed>|null  $totals  optional final row
     */
    public function __construct(
        public readonly string $title,
        public readonly array $headings,
        public iterable $rows,
        public readonly array $moneyColumns = [],
        public readonly ?array $totals = null,
    ) {
        $this->rows = collect($rows)->map(fn ($row) => array_values((array) $row))->all();
    }
}
