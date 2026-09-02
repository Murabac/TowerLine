<?php

namespace App\Reports;

class ReportResult
{
    /**
     * @param  list<array{key: string, label: string}>  $columns
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array{label: string, value: string}>  $kpis
     * @param  list<array{title: string, columns: list<array{key: string, label: string}>, rows: list<array<string, mixed>>}>  $sections
     */
    public function __construct(
        public readonly array $columns = [],
        public readonly array $rows = [],
        public readonly string $layout = 'table',
        public readonly array $kpis = [],
        public readonly array $sections = [],
    ) {}
}
