<?php

namespace App\Reports;

use App\Models\User;
use Illuminate\Support\Collection;

class ReportCatalog
{
    public const GROUPS = [
        'towers',
        'operators',
        'geography',
        'inspections',
        'letters',
        'frequencies',
        'governance',
        'executive',
    ];

    public const ALIASES = [
        'towers.map_tabular' => 'towers.master',
        'operators.tower_list' => 'towers.master',
        'operators.summary' => 'towers.by_operator',
        'operators.frequencies' => 'frequencies.history',
    ];

    /**
     * @return list<ReportDefinition>
     */
    public static function all(): array
    {
        return [
            new ReportDefinition('towers.master', 'towers', ['dates', 'geography', 'operator', 'status', 'health', 'power'], inspector: true, operatorViewer: true, dateColumn: 'created_at'),
            new ReportDefinition('towers.by_region', 'towers', ['dates', 'geography', 'operator', 'status', 'health', 'power']),
            new ReportDefinition('towers.by_district', 'towers', ['dates', 'geography', 'operator', 'status', 'health', 'power'], inspector: true),
            new ReportDefinition('towers.by_sub_district', 'towers', ['dates', 'geography', 'operator', 'status', 'health', 'power'], inspector: true),
            new ReportDefinition('towers.by_operator', 'towers', ['dates', 'geography', 'operator', 'status', 'health', 'power'], operatorViewer: true),
            new ReportDefinition('towers.by_operator_region', 'towers', ['dates', 'geography', 'operator', 'status']),
            new ReportDefinition('towers.by_type', 'towers', ['dates', 'geography', 'operator', 'status']),
            new ReportDefinition('towers.by_status', 'towers', ['dates', 'geography', 'operator']),
            new ReportDefinition('towers.by_health', 'towers', ['dates', 'geography', 'operator', 'status']),
            new ReportDefinition('towers.by_power', 'towers', ['dates', 'geography', 'operator', 'status', 'power']),
            new ReportDefinition('towers.missing_district', 'towers', ['geography', 'operator', 'status']),
            new ReportDefinition('towers.new_in_period', 'towers', ['dates', 'geography', 'operator', 'status'], dateColumn: 'created_at'),
            new ReportDefinition('towers.decommissioned', 'towers', ['dates', 'geography', 'operator'], dateColumn: 'updated_at'),

            new ReportDefinition('operators.footprint', 'operators', ['geography', 'operator']),
            new ReportDefinition('operators.compliance', 'operators', ['dates', 'geography', 'operator']),

            new ReportDefinition('geography.regional', 'geography', ['dates', 'geography']),
            new ReportDefinition('geography.district', 'geography', ['dates', 'geography']),
            new ReportDefinition('geography.sub_district', 'geography', ['dates', 'geography']),
            new ReportDefinition('geography.empty_districts', 'geography', ['geography'], inspector: true),
            new ReportDefinition('geography.inspector_coverage', 'geography', ['geography']),

            new ReportDefinition('inspections.log', 'inspections', ['dates', 'geography', 'operator'], inspector: true, dateColumn: 'inspected_at'),
            new ReportDefinition('inspections.by_inspector', 'inspections', ['dates', 'geography'], dateColumn: 'inspected_at'),
            new ReportDefinition('inspections.by_geography', 'inspections', ['dates', 'geography'], dateColumn: 'inspected_at'),
            new ReportDefinition('inspections.partial', 'inspections', ['dates', 'geography'], dateColumn: 'inspected_at'),
            new ReportDefinition('inspections.overdue', 'inspections', ['geography', 'operator', 'status'], inspector: true),
            new ReportDefinition('inspections.pending', 'inspections', ['dates', 'geography'], inspector: true),
            new ReportDefinition('inspections.outcomes', 'inspections', ['dates', 'geography']),

            new ReportDefinition('letters.issued', 'letters', ['dates', 'geography', 'operator'], dateColumn: 'issued_at'),
            new ReportDefinition('letters.by_operator', 'letters', ['dates', 'geography', 'operator'], dateColumn: 'issued_at'),
            new ReportDefinition('letters.by_geography', 'letters', ['dates', 'geography', 'operator'], dateColumn: 'issued_at'),
            new ReportDefinition('letters.missing', 'letters', ['geography', 'operator', 'status']),

            new ReportDefinition('frequencies.active', 'frequencies', ['dates', 'geography', 'operator'], dateColumn: 'issued_at'),
            new ReportDefinition('frequencies.expiring', 'frequencies', ['window', 'geography', 'operator']),
            new ReportDefinition('frequencies.expired', 'frequencies', ['dates', 'geography', 'operator'], dateColumn: 'expires_at'),
            new ReportDefinition('frequencies.history', 'frequencies', ['dates', 'geography', 'operator'], dateColumn: 'issued_at'),
            new ReportDefinition('frequencies.by_operator', 'frequencies', ['operator']),

            new ReportDefinition('governance.pending', 'governance', ['geography']),
            new ReportDefinition('governance.turnaround', 'governance', ['dates', 'geography']),
            new ReportDefinition('governance.activity', 'governance', ['dates']),
            new ReportDefinition('governance.audit', 'governance', ['dates'], requiresAudit: true),

            new ReportDefinition('executive.snapshot', 'executive', ['geography', 'operator'], layout: 'snapshot'),
            new ReportDefinition('executive.monthly', 'executive', ['month', 'geography'], layout: 'snapshot'),
            new ReportDefinition('executive.custom', 'executive', ['dates', 'geography', 'operator', 'status', 'health', 'power', 'columns']),
        ];
    }

    public static function canonicalKey(string $key): string
    {
        return self::ALIASES[$key] ?? $key;
    }

    public static function find(string $key): ?ReportDefinition
    {
        $key = self::canonicalKey($key);

        foreach (self::all() as $report) {
            if ($report->key === $key) {
                return $report;
            }
        }

        return null;
    }

    /**
     * @return Collection<string, Collection<int, ReportDefinition>>
     */
    public static function groupedFor(User $user): Collection
    {
        return collect(self::all())
            ->unique('key')
            ->filter(fn (ReportDefinition $report) => $report->allowedFor($user))
            ->unique(fn (ReportDefinition $report) => mb_strtolower($report->title()))
            ->groupBy(fn (ReportDefinition $report) => $report->group);
    }
}
