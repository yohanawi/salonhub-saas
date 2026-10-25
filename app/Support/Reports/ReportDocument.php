<?php

namespace App\Support\Reports;

/**
 * A report ready to export: header details, headline figures and one or more tables.
 */
class ReportDocument
{
    /** @var array<int, ReportTable> */
    public array $tables = [];

    /**
     * @param  array<string, string>  $meta  e.g. ['Period' => 'Oct 2026', 'Branch' => 'All branches']
     * @param  array<string, string>  $summary  headline figures, already formatted
     */
    public function __construct(
        public readonly string $title,
        public readonly string $salonName,
        public array $meta = [],
        public array $summary = [],
        public readonly string $currency = 'LKR',
    ) {
    }

    public function table(ReportTable $table): static
    {
        $this->tables[] = $table;

        return $this;
    }

    public function filename(string $extension): string
    {
        return str($this->title . ' ' . implode(' ', array_values($this->meta)))->slug()->limit(120, '') . '.' . $extension;
    }
}
