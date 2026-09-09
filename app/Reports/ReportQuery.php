<?php

namespace App\Reports;

use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\BuildApprovalLetter;
use App\Models\District;
use App\Models\FrequencyAllocation;
use App\Models\Inspection;
use App\Models\Region;
use App\Models\Tower;
use App\Models\User;
use App\Support\TowerPowerSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportQuery
{
    public function run(ReportDefinition $report, User $user, array $filters): ReportResult
    {
        $result = match ($report->key) {
            'towers.master', 'towers.new_in_period' => $this->towerRegister($user, $filters, $report),
            'towers.by_region' => $this->towersGrouped($user, $filters, fn (Tower $tower) => $tower->region?->localizedName() ?: '—'),
            'towers.by_district' => $this->towersGrouped($user, $filters, fn (Tower $tower) => ($tower->region?->localizedName() ?: '—').' / '.($tower->district?->localizedName() ?: __('app.reports.unassigned'))),
            'towers.by_sub_district' => $this->towersGrouped($user, $filters, fn (Tower $tower) => ($tower->district?->localizedName() ?: __('app.reports.unassigned')).' / '.($tower->subDistrict?->localizedName() ?: __('app.reports.unassigned'))),
            'towers.by_operator' => $this->towersByOperator($user, $filters),
            'towers.by_operator_region' => $this->towersByOperatorRegion($user, $filters),
            'towers.by_type' => $this->towersGrouped($user, $filters, fn (Tower $tower) => __('app.status.'.$tower->type)),
            'towers.by_status' => $this->towersGrouped($user, $filters, fn (Tower $tower) => __('app.status.'.$tower->status)),
            'towers.by_health' => $this->towersGrouped($user, $filters, fn (Tower $tower) => __('app.status.'.$tower->health_status)),
            'towers.by_power' => $this->towersByPower($user, $filters),
            'towers.missing_district' => $this->towerRegister($user, [...$filters, 'missing_district' => true], $report),
            'towers.decommissioned' => $this->towerRegister($user, [...$filters, 'status' => 'decommissioned'], $report),
            'operators.footprint' => $this->operatorFootprint($user, $filters),
            'operators.compliance' => $this->operatorCompliance($user, $filters),
            'geography.regional' => $this->geographySummary($user, $filters, 'region'),
            'geography.district' => $this->geographySummary($user, $filters, 'district'),
            'geography.sub_district' => $this->geographySummary($user, $filters, 'sub_district'),
            'geography.empty_districts' => $this->emptyDistricts($user, $filters),
            'geography.inspector_coverage' => $this->inspectorCoverage($user, $filters),
            'inspections.log', 'inspections.partial' => $this->inspectionLog($user, $filters, $report->key === 'inspections.partial'),
            'inspections.by_inspector' => $this->inspectionsByInspector($user, $filters),
            'inspections.by_geography' => $this->inspectionsByGeography($user, $filters),
            'inspections.overdue' => $this->overdueTowers($user, $filters),
            'inspections.pending' => $this->pendingApprovals($user, $filters, ApprovalRequest::TYPE_INSPECTION),
            'inspections.outcomes' => $this->approvalOutcomes($user, $filters, ApprovalRequest::TYPE_INSPECTION),
            'letters.issued' => $this->lettersIssued($user, $filters),
            'letters.by_operator' => $this->lettersGrouped($user, $filters, fn (BuildApprovalLetter $letter) => $letter->operator?->name ?: '—'),
            'letters.by_geography' => $this->lettersGrouped($user, $filters, fn (BuildApprovalLetter $letter) => ($letter->tower?->region?->localizedName() ?: '—').' / '.($letter->tower?->district?->localizedName() ?: '—')),
            'letters.missing' => $this->towersWithoutLetter($user, $filters),
            'frequencies.active' => $this->frequencyList($user, $filters, 'active'),
            'frequencies.expiring' => $this->frequencyList($user, $filters, 'expiring'),
            'frequencies.expired' => $this->frequencyList($user, $filters, 'expired'),
            'frequencies.history' => $this->frequencyList($user, $filters, 'history'),
            'frequencies.by_operator' => $this->frequencyByOperator($user, $filters),
            'governance.pending' => $this->pendingApprovals($user, $filters),
            'governance.turnaround' => $this->approvalTurnaround($user, $filters),
            'governance.activity' => $this->userActivity($user, $filters),
            'governance.audit' => $this->auditExtract($user, $filters),
            'executive.snapshot' => $this->executiveSnapshot($user, $filters),
            'executive.monthly' => $this->monthlyBrief($user, $filters),
            'executive.custom' => $this->customExport($user, $filters),
            default => new ReportResult(),
        };

        return $this->numberRows($this->sort($result, $filters));
    }

    private function towerRegister(User $user, array $filters, ReportDefinition $report): ReportResult
    {
        $towers = $this->filteredTowers($user, $filters, $report->dateColumn ?: 'created_at');

        if ($report->key === 'towers.new_in_period' && empty($filters['from']) && empty($filters['to'])) {
            $towers = $towers->filter(fn (Tower $tower) => $tower->created_at?->gte(now()->startOfMonth()));
        }

        return new ReportResult($this->masterColumns(), $towers->map(fn (Tower $tower) => $this->towerRow($tower))->values()->all());
    }

    private function customExport(User $user, array $filters): ReportResult
    {
        $allowed = collect($this->masterColumns())->keyBy('key');
        $selected = collect((array) ($filters['columns'] ?? []))
            ->filter(fn ($key) => $allowed->has($key))
            ->values();

        if ($selected->isEmpty()) {
            $selected = collect(['name', 'region', 'district', 'operator', 'status', 'health']);
        }

        if (! $selected->contains('no')) {
            $selected = $selected->prepend('no');
        }

        $columns = $selected->map(fn (string $key) => $allowed[$key])->all();
        $towers = $this->filteredTowers($user, $filters, 'created_at');

        return new ReportResult($columns, $towers->map(function (Tower $tower) use ($selected) {
            $row = $this->towerRow($tower);

            return $selected->mapWithKeys(fn (string $key) => [$key => $row[$key] ?? ''])->all();
        })->values()->all());
    }

    private function towersGrouped(User $user, array $filters, callable $label): ReportResult
    {
        $groups = $this->filteredTowers($user, $filters)->groupBy($label);

        $rows = $groups->map(function (Collection $group, string $name) {
            return [
                'group' => $name,
                'total' => $group->count(),
                'active' => $group->where('status', 'active')->count(),
                'under_construction' => $group->where('status', 'under_construction')->count(),
                'decommissioned' => $group->where('status', 'decommissioned')->count(),
                'good' => $group->where('health_status', 'good')->count(),
                'needs_attention' => $group->where('health_status', 'needs_attention')->count(),
                'critical' => $group->where('health_status', 'critical')->count(),
                'unknown' => $group->where('health_status', 'unknown')->count(),
            ];
        })->values()->all();

        return new ReportResult($this->groupCountColumns(), $rows);
    }

    private function towersByOperator(User $user, array $filters): ReportResult
    {
        $rows = $this->filteredTowers($user, $filters)
            ->groupBy('operator_id')
            ->map(function (Collection $group) {
                $operator = $group->first()->operator;

                return [
                    'operator' => $operator?->name ?: '—',
                    'category' => $operator ? __('app.status.'.$operator->category) : '—',
                    'total' => $group->count(),
                    'active' => $group->where('status', 'active')->count(),
                    'regions' => $group->pluck('region')->filter()->unique('id')->map->localizedName()->join(', '),
                ];
            })
            ->values()
            ->all();

        return new ReportResult([
            $this->col('operator', __('app.towers.operator')),
            $this->col('category', __('app.map.category')),
            $this->col('total', __('app.reports.total')),
            $this->col('active', __('app.status.active')),
            $this->col('regions', __('app.towers.region')),
        ], $rows);
    }

    private function towersByOperatorRegion(User $user, array $filters): ReportResult
    {
        $towers = $this->filteredTowers($user, $filters);
        $regions = $towers->pluck('region')->filter()->unique('id')->sortBy('name_en')->values();

        $columns = array_merge(
            [$this->col('operator', __('app.towers.operator'))],
            $regions->map(fn (Region $region) => $this->col('r'.$region->id, $region->localizedName()))->all(),
            [$this->col('total', __('app.reports.total'))],
        );

        $rows = $towers->groupBy('operator_id')->map(function (Collection $group) use ($regions) {
            $row = ['operator' => $group->first()->operator?->name ?: '—', 'total' => $group->count()];
            foreach ($regions as $region) {
                $row['r'.$region->id] = $group->where('region_id', $region->id)->count();
            }

            return $row;
        })->values()->all();

        return new ReportResult($columns, $rows);
    }

    private function towersByPower(User $user, array $filters): ReportResult
    {
        $towers = $this->filteredTowers($user, $filters);
        $rows = collect(TowerPowerSource::OPTIONS)->map(function (string $source) use ($towers) {
            $group = $towers->filter(fn (Tower $tower) => in_array($source, $tower->powerSourceKeys(), true));

            return [
                'group' => TowerPowerSource::label($source),
                'total' => $group->count(),
                'active' => $group->where('status', 'active')->count(),
                'under_construction' => $group->where('status', 'under_construction')->count(),
                'decommissioned' => $group->where('status', 'decommissioned')->count(),
                'good' => $group->where('health_status', 'good')->count(),
                'needs_attention' => $group->where('health_status', 'needs_attention')->count(),
                'critical' => $group->where('health_status', 'critical')->count(),
                'unknown' => $group->where('health_status', 'unknown')->count(),
            ];
        })->all();

        return new ReportResult($this->groupCountColumns(), $rows);
    }

    private function operatorFootprint(User $user, array $filters): ReportResult
    {
        $towers = $this->filteredTowers($user, $filters);

        $rows = $towers->groupBy(fn (Tower $tower) => $tower->operator_id.'-'.$tower->region_id.'-'.$tower->district_id)
            ->map(function (Collection $group) {
                $tower = $group->first();

                return [
                    'operator' => $tower->operator?->name ?: '—',
                    'region' => $tower->region?->localizedName() ?: '—',
                    'district' => $tower->district?->localizedName() ?: __('app.reports.unassigned'),
                    'towers' => $group->count(),
                ];
            })->values()->all();

        return new ReportResult([
            $this->col('operator', __('app.towers.operator')),
            $this->col('region', __('app.towers.region')),
            $this->col('district', __('app.towers.district')),
            $this->col('towers', __('app.reports.total')),
        ], $rows);
    }

    private function operatorCompliance(User $user, array $filters): ReportResult
    {
        $towers = $this->filteredTowers($user, $filters);
        $pending = ApprovalRequest::query()->visibleTo($user)->pending()->with('tower')->get();
        $letters = BuildApprovalLetter::query()->visibleTo($user)->get();

        $rows = $towers->groupBy('operator_id')->map(function (Collection $group) use ($pending, $letters) {
            $operatorId = $group->first()->operator_id;
            $inspected = $group->filter(fn (Tower $tower) => $tower->latestInspection)->count();

            return [
                'operator' => $group->first()->operator?->name ?: '—',
                'towers' => $group->count(),
                'inspection_coverage' => $group->count() ? round(($inspected / $group->count()) * 100).'%' : '0%',
                'pending' => $pending->filter(fn (ApprovalRequest $request) => $request->tower?->operator_id === $operatorId)->count(),
                'letters' => $letters->where('operator_id', $operatorId)->count(),
            ];
        })->values()->all();

        return new ReportResult([
            $this->col('operator', __('app.towers.operator')),
            $this->col('towers', __('app.reports.total')),
            $this->col('inspection_coverage', __('app.reports.inspection_coverage')),
            $this->col('pending', __('app.nav.approvals')),
            $this->col('letters', __('app.approval_letters.title')),
        ], $rows);
    }

    private function geographySummary(User $user, array $filters, string $grain): ReportResult
    {
        $towers = $this->filteredTowers($user, $filters);
        $letters = BuildApprovalLetter::query()->visibleTo($user)->with('tower')->get();
        $inspections = Inspection::query()->whereHas('tower', fn ($towersQuery) => $towersQuery->visibleTo($user))->with('tower')->get();

        $grouped = $towers->groupBy(function (Tower $tower) use ($grain) {
            return match ($grain) {
                'district' => $tower->district_id ?: 0,
                'sub_district' => $tower->sub_district_id ?: 0,
                default => $tower->region_id ?: 0,
            };
        });

        $rows = $grouped->map(function (Collection $group, $id) use ($grain, $letters, $inspections) {
            $tower = $group->first();
            $label = match ($grain) {
                'district' => ($tower->region?->localizedName() ?: '—').' / '.($tower->district?->localizedName() ?: __('app.reports.unassigned')),
                'sub_district' => ($tower->district?->localizedName() ?: __('app.reports.unassigned')).' / '.($tower->subDistrict?->localizedName() ?: __('app.reports.unassigned')),
                default => $tower->region?->localizedName() ?: __('app.reports.unassigned'),
            };

            $towerIds = $group->pluck('id');

            return [
                'place' => $label,
                'towers' => $group->count(),
                'operators' => $group->pluck('operator_id')->unique()->count(),
                'inspections' => $inspections->whereIn('tower_id', $towerIds)->count(),
                'letters' => $letters->filter(fn (BuildApprovalLetter $letter) => $towerIds->contains($letter->tower_id))->count(),
                'last_inspection' => optional($group->map(fn (Tower $item) => $item->latestInspection?->inspected_at)->filter()->sort()->last())?->toDateString() ?: '—',
            ];
        })->values()->all();

        return new ReportResult([
            $this->col('place', __('app.reports.place')),
            $this->col('towers', __('app.dashboard.total_towers')),
            $this->col('operators', __('app.towers.operator')),
            $this->col('inspections', __('app.inspections.title')),
            $this->col('letters', __('app.approval_letters.title')),
            $this->col('last_inspection', __('app.map.last_inspection')),
        ], $rows);
    }

    private function emptyDistricts(User $user, array $filters): ReportResult
    {
        $query = District::query()->with('region')->orderBy('name');

        if (! empty($filters['region_id'])) {
            $query->where('region_id', (int) $filters['region_id']);
        } elseif ($user->requiresRegions()) {
            $query->whereIn('region_id', $user->regionIds() ?: [0]);
        }

        $towerDistricts = Tower::query()->visibleTo($user)->whereNotNull('district_id')->pluck('district_id')->unique();

        $rows = $query->get()
            ->reject(fn (District $district) => $towerDistricts->contains($district->id))
            ->map(fn (District $district) => [
                'region' => $district->region?->localizedName() ?: '—',
                'district' => $district->localizedName(),
                'towers' => 0,
            ])->values()->all();

        return new ReportResult([
            $this->col('region', __('app.towers.region')),
            $this->col('district', __('app.towers.district')),
            $this->col('towers', __('app.reports.total')),
        ], $rows);
    }

    private function inspectorCoverage(User $user, array $filters): ReportResult
    {
        $cutoff = now()->subDays(Tower::INSPECTION_STALE_DAYS);
        $districts = District::query()->with('region')->orderBy('name');

        if (! empty($filters['region_id'])) {
            $districts->where('region_id', (int) $filters['region_id']);
        } elseif ($user->requiresRegions()) {
            $districts->whereIn('region_id', $user->regionIds() ?: [0]);
        }

        $towers = Tower::query()->visibleTo($user)->with('latestInspection')->get()->groupBy('district_id');

        $rows = $districts->get()->map(function (District $district) use ($towers, $cutoff) {
            $group = $towers->get($district->id, collect());
            $recent = $group->filter(fn (Tower $tower) => $tower->latestInspection?->inspected_at?->gte($cutoff))->count();

            return [
                'region' => $district->region?->localizedName() ?: '—',
                'district' => $district->localizedName(),
                'towers' => $group->count(),
                'recent' => $recent,
                'coverage' => $group->count() ? ($recent ? __('app.reports.covered') : __('app.reports.lacking')) : __('app.reports.no_towers'),
            ];
        })->values()->all();

        return new ReportResult([
            $this->col('region', __('app.towers.region')),
            $this->col('district', __('app.towers.district')),
            $this->col('towers', __('app.reports.total')),
            $this->col('recent', __('app.reports.recent_inspections')),
            $this->col('coverage', __('app.reports.coverage_status')),
        ], $rows);
    }

    private function inspectionLog(User $user, array $filters, bool $partialOnly): ReportResult
    {
        $query = Inspection::query()
            ->with(['tower.region', 'tower.district', 'tower.subDistrict', 'tower.operator', 'inspector'])
            ->whereHas('tower', fn ($towers) => $towers->visibleTo($user));

        if ($user->isInspector()) {
            $query->where('inspector_id', $user->id);
        }

        $this->applyDate($query, $filters, 'inspected_at');
        $this->applyTowerRelationFilters($query, $filters);

        $inspections = $query->latest('inspected_at')->get();

        if ($partialOnly) {
            $inspections = $inspections->filter(fn (Inspection $inspection) => ! $inspection->hasStructuredChecks());
        }

        $rows = $inspections->map(fn (Inspection $inspection) => [
            'date' => optional($inspection->inspected_at)?->toDateString() ?: '—',
            'tower' => $inspection->tower?->name ?: '—',
            'region' => $inspection->tower?->region?->localizedName() ?: '—',
            'district' => $inspection->tower?->district?->localizedName() ?: '—',
            'inspector' => $inspection->inspector?->name ?: '—',
            'power' => $inspection->powerStatusLabel(),
            'comment' => $inspection->notes ?: '—',
            'partial' => $inspection->hasStructuredChecks() ? __('app.none') : __('app.reports.partial'),
        ])->values()->all();

        return new ReportResult([
            $this->col('date', __('app.inspections.date')),
            $this->col('tower', __('app.towers.name')),
            $this->col('region', __('app.towers.region')),
            $this->col('district', __('app.towers.district')),
            $this->col('inspector', __('app.inspections.inspector')),
            $this->col('power', __('app.inspections.power')),
            $this->col('comment', __('app.inspections.notes')),
            $this->col('partial', __('app.reports.partial')),
        ], $rows);
    }

    private function inspectionsByInspector(User $user, array $filters): ReportResult
    {
        $query = Inspection::query()->with(['inspector', 'tower'])->whereHas('tower', fn ($towers) => $towers->visibleTo($user));
        $this->applyDate($query, $filters, 'inspected_at');
        $this->applyTowerRelationFilters($query, $filters);

        if ($user->isInspector()) {
            $query->where('inspector_id', $user->id);
        }

        $rows = $query->get()->groupBy('inspector_id')->map(function (Collection $group) {
            return [
                'inspector' => $group->first()->inspector?->name ?: '—',
                'visits' => $group->count(),
                'partial' => $group->filter(fn (Inspection $inspection) => ! $inspection->hasStructuredChecks())->count(),
                'towers' => $group->pluck('tower_id')->unique()->count(),
            ];
        })->values()->all();

        return new ReportResult([
            $this->col('inspector', __('app.inspections.inspector')),
            $this->col('visits', __('app.reports.visits')),
            $this->col('partial', __('app.reports.partial')),
            $this->col('towers', __('app.dashboard.total_towers')),
        ], $rows);
    }

    private function inspectionsByGeography(User $user, array $filters): ReportResult
    {
        $query = Inspection::query()->with(['tower.region', 'tower.district'])->whereHas('tower', fn ($towers) => $towers->visibleTo($user));
        $this->applyDate($query, $filters, 'inspected_at');
        $this->applyTowerRelationFilters($query, $filters);

        $rows = $query->get()->groupBy(fn (Inspection $inspection) => ($inspection->tower?->region_id ?: 0).'-'.($inspection->tower?->district_id ?: 0))
            ->map(function (Collection $group) {
                $tower = $group->first()->tower;

                return [
                    'region' => $tower?->region?->localizedName() ?: '—',
                    'district' => $tower?->district?->localizedName() ?: __('app.reports.unassigned'),
                    'visits' => $group->count(),
                ];
            })->values()->all();

        return new ReportResult([
            $this->col('region', __('app.towers.region')),
            $this->col('district', __('app.towers.district')),
            $this->col('visits', __('app.reports.visits')),
        ], $rows);
    }

    private function overdueTowers(User $user, array $filters): ReportResult
    {
        $towers = $this->filteredTowers($user, $filters)->filter(fn (Tower $tower) => $tower->isInspectionOverdue());

        return new ReportResult($this->masterColumns(), $towers->map(fn (Tower $tower) => $this->towerRow($tower))->values()->all());
    }

    private function pendingApprovals(User $user, array $filters, ?string $type = null): ReportResult
    {
        $query = ApprovalRequest::query()->visibleTo($user)->pending()->with(['tower.region', 'tower.operator', 'submitter']);

        if ($type) {
            $query->where('type', $type);
        }

        $this->applyDate($query, $filters, 'created_at');

        if ($user->isInspector()) {
            $query->where('submitted_by', $user->id);
        }

        if (! empty($filters['region_id'])) {
            $query->whereHas('tower', fn ($towers) => $towers->where('region_id', (int) $filters['region_id']));
        }

        $rows = $query->latest()->get()->map(fn (ApprovalRequest $request) => [
            'type' => __('app.approvals.types.'.$request->type),
            'tower' => $request->tower?->name ?: ($request->payload['name'] ?? '—'),
            'region' => $request->tower?->region?->localizedName() ?: '—',
            'submitter' => $request->submitter?->name ?: '—',
            'submitted' => optional($request->created_at)?->toDateTimeString() ?: '—',
            'status' => __('app.approvals.status.'.$request->status),
        ])->all();

        return new ReportResult([
            $this->col('type', __('app.approvals.type')),
            $this->col('tower', __('app.towers.name')),
            $this->col('region', __('app.towers.region')),
            $this->col('submitter', __('app.approvals.submitted_by')),
            $this->col('submitted', __('app.approvals.when')),
            $this->col('status', __('app.towers.status')),
        ], $rows);
    }

    private function approvalOutcomes(User $user, array $filters, ?string $type = null): ReportResult
    {
        $query = ApprovalRequest::query()
            ->visibleTo($user)
            ->whereIn('status', [ApprovalRequest::STATUS_APPROVED, ApprovalRequest::STATUS_REJECTED])
            ->with(['tower', 'reviewer']);

        if ($type) {
            $query->where('type', $type);
        }

        $this->applyDate($query, $filters, 'reviewed_at');

        $rows = $query->latest('reviewed_at')->get()->map(fn (ApprovalRequest $request) => [
            'type' => __('app.approvals.types.'.$request->type),
            'tower' => $request->tower?->name ?: '—',
            'outcome' => __('app.approvals.status.'.$request->status),
            'reviewer' => $request->reviewer?->name ?: '—',
            'reviewed' => optional($request->reviewed_at)?->toDateTimeString() ?: '—',
        ])->all();

        return new ReportResult([
            $this->col('type', __('app.approvals.type')),
            $this->col('tower', __('app.towers.name')),
            $this->col('outcome', __('app.reports.outcome')),
            $this->col('reviewer', __('app.approvals.reviewed_by')),
            $this->col('reviewed', __('app.approvals.reviewed_at')),
        ], $rows);
    }

    private function lettersIssued(User $user, array $filters): ReportResult
    {
        $query = BuildApprovalLetter::query()->visibleTo($user)->with(['tower.region', 'tower.district', 'operator', 'issuer']);
        $this->applyDate($query, $filters, 'issued_at');
        $this->applyOperatorFilter($query, $filters, 'operator_id');

        if (! empty($filters['region_id'])) {
            $query->whereHas('tower', fn ($towers) => $towers->where('region_id', (int) $filters['region_id']));
        }

        $rows = $query->latest('issued_at')->get()->map(fn (BuildApprovalLetter $letter) => [
            'reference' => $letter->reference_number,
            'tower' => $letter->tower?->name ?: '—',
            'operator' => $letter->operator?->name ?: '—',
            'region' => $letter->tower?->region?->localizedName() ?: '—',
            'district' => $letter->tower?->district?->localizedName() ?: '—',
            'issued' => optional($letter->issued_at)?->toDateString() ?: '—',
            'issuer' => $letter->issuer?->name ?: '—',
        ])->all();

        return new ReportResult($this->letterColumns(), $rows);
    }

    private function lettersGrouped(User $user, array $filters, callable $label): ReportResult
    {
        $query = BuildApprovalLetter::query()->visibleTo($user)->with(['tower.region', 'tower.district', 'operator']);
        $this->applyDate($query, $filters, 'issued_at');

        $rows = $query->get()->groupBy($label)->map(fn (Collection $group, string $name) => [
            'group' => $name,
            'letters' => $group->count(),
        ])->values()->all();

        return new ReportResult([
            $this->col('group', __('app.reports.place')),
            $this->col('letters', __('app.reports.total')),
        ], $rows);
    }

    private function towersWithoutLetter(User $user, array $filters): ReportResult
    {
        $towers = $this->filteredTowers($user, $filters)->filter(fn (Tower $tower) => $tower->currentApprovalLetter === null);

        return new ReportResult($this->masterColumns(), $towers->map(fn (Tower $tower) => $this->towerRow($tower))->values()->all());
    }

    private function frequencyList(User $user, array $filters, string $mode): ReportResult
    {
        $query = FrequencyAllocation::query()->visibleTo($user)->with(['operator', 'region']);
        $this->applyOperatorFilter($query, $filters, 'operator_id');

        if (! empty($filters['region_id'])) {
            $query->where(fn ($scoped) => $scoped->whereNull('region_id')->orWhere('region_id', (int) $filters['region_id']));
        }

        match ($mode) {
            'active' => $query->whereDate('expires_at', '>=', now()->toDateString()),
            'expired' => $query->expired()->whereDoesntHave('renewals'),
            'expiring' => $query->whereDate('expires_at', '>=', now()->toDateString())
                ->whereDate('expires_at', '<=', now()->addDays((int) ($filters['window'] ?? 30))->toDateString()),
            default => $this->applyDate($query, $filters, 'issued_at'),
        };

        return new ReportResult($this->frequencyColumns(), $query->orderBy('expires_at')->get()->map(fn (FrequencyAllocation $allocation) => $this->frequencyRow($allocation))->all());
    }

    private function frequencyByOperator(User $user, array $filters): ReportResult
    {
        $query = FrequencyAllocation::query()->visibleTo($user)->with(['operator', 'region']);
        $this->applyOperatorFilter($query, $filters, 'operator_id');

        $rows = $query->get()->groupBy('operator_id')->map(function (Collection $group) {
            return [
                'operator' => $group->first()->operator?->name ?: '—',
                'allocations' => $group->count(),
                'active' => $group->filter(fn (FrequencyAllocation $allocation) => $allocation->expires_at?->gte(now()->startOfDay()))->count(),
                'bands' => $group->pluck('band_label')->filter()->unique()->join(', '),
            ];
        })->values()->all();

        return new ReportResult([
            $this->col('operator', __('app.towers.operator')),
            $this->col('allocations', __('app.reports.total')),
            $this->col('active', __('app.status.active')),
            $this->col('bands', __('app.frequencies.band')),
        ], $rows);
    }

    private function approvalTurnaround(User $user, array $filters): ReportResult
    {
        $query = ApprovalRequest::query()
            ->visibleTo($user)
            ->whereNotNull('reviewed_at')
            ->with(['tower', 'submitter', 'reviewer']);
        $this->applyDate($query, $filters, 'reviewed_at');

        $rows = $query->get()->map(function (ApprovalRequest $request) {
            $hours = $request->created_at && $request->reviewed_at
                ? $request->created_at->diffInHours($request->reviewed_at)
                : null;

            return [
                'type' => __('app.approvals.types.'.$request->type),
                'tower' => $request->tower?->name ?: '—',
                'submitter' => $request->submitter?->name ?: '—',
                'reviewer' => $request->reviewer?->name ?: '—',
                'hours' => $hours ?? '—',
                'outcome' => __('app.approvals.status.'.$request->status),
            ];
        })->all();

        return new ReportResult([
            $this->col('type', __('app.approvals.type')),
            $this->col('tower', __('app.towers.name')),
            $this->col('submitter', __('app.approvals.submitted_by')),
            $this->col('reviewer', __('app.approvals.reviewed_by')),
            $this->col('hours', __('app.reports.hours')),
            $this->col('outcome', __('app.reports.outcome')),
        ], $rows);
    }

    private function userActivity(User $user, array $filters): ReportResult
    {
        $query = AuditLog::query()->with('user');
        $this->applyDate($query, $filters, 'created_at');

        $rows = $query->get()->groupBy('user_id')->map(function (Collection $group) {
            return [
                'user' => $group->first()->actorName(),
                'created' => $group->where('action', 'created')->count(),
                'updated' => $group->where('action', 'updated')->count(),
                'deleted' => $group->where('action', 'deleted')->count(),
                'total' => $group->count(),
            ];
        })->values()->all();

        return new ReportResult([
            $this->col('user', __('app.audit.user')),
            $this->col('created', __('app.audit.actions.created')),
            $this->col('updated', __('app.audit.actions.updated')),
            $this->col('deleted', __('app.audit.actions.deleted')),
            $this->col('total', __('app.reports.total')),
        ], $rows);
    }

    private function auditExtract(User $user, array $filters): ReportResult
    {
        $query = AuditLog::query()->with('user')->latest('created_at');
        $this->applyDate($query, $filters, 'created_at');

        $rows = $query->limit(2000)->get()->map(fn (AuditLog $log) => [
            'when' => optional($log->created_at)?->toDateTimeString() ?: '—',
            'user' => $log->actorName(),
            'action' => $log->actionLabel(),
            'record' => $log->modelLabel(),
            'details' => $log->sentence(),
        ])->all();

        return new ReportResult([
            $this->col('when', __('app.audit.when')),
            $this->col('user', __('app.audit.user')),
            $this->col('action', __('app.audit.action')),
            $this->col('record', __('app.audit.model')),
            $this->col('details', __('app.audit.what')),
        ], $rows);
    }

    private function executiveSnapshot(User $user, array $filters): ReportResult
    {
        $towers = $this->filteredTowers($user, $filters);
        $pending = ApprovalRequest::query()->visibleTo($user)->pending()->count();
        $expiring = FrequencyAllocation::query()->visibleTo($user)->expiringSoon()->count();

        $kpis = [
            ['label' => __('app.dashboard.total_towers'), 'value' => (string) $towers->count()],
            ['label' => __('app.status.critical'), 'value' => (string) $towers->where('health_status', 'critical')->count()],
            ['label' => __('app.nav.approvals'), 'value' => (string) $pending],
            ['label' => __('app.frequencies.title'), 'value' => (string) $expiring],
        ];

        $byRegion = $this->towersGrouped($user, $filters, fn (Tower $tower) => $tower->region?->localizedName() ?: '—');
        $byOperator = $this->towersByOperator($user, $filters);

        return new ReportResult(layout: 'snapshot', kpis: $kpis, sections: [
            ['title' => __('app.reports.items.towers_by_region'), 'columns' => $byRegion->columns, 'rows' => $byRegion->rows],
            ['title' => __('app.reports.items.towers_by_operator'), 'columns' => $byOperator->columns, 'rows' => $byOperator->rows],
        ]);
    }

    private function monthlyBrief(User $user, array $filters): ReportResult
    {
        $month = ! empty($filters['month'])
            ? Carbon::parse($filters['month'].'-01')->startOfMonth()
            : now()->startOfMonth();
        $previous = $month->copy()->subMonth();

        $currentFilters = [...$filters, 'from' => $month->toDateString(), 'to' => $month->copy()->endOfMonth()->toDateString()];
        $previousFilters = [...$filters, 'from' => $previous->toDateString(), 'to' => $previous->copy()->endOfMonth()->toDateString()];

        $current = $this->filteredTowers($user, $currentFilters, 'created_at');
        $prior = $this->filteredTowers($user, $previousFilters, 'created_at');
        $all = $this->filteredTowers($user, $filters);

        $kpis = [
            ['label' => __('app.reports.month'), 'value' => $month->translatedFormat('F Y')],
            ['label' => __('app.reports.new_this_month'), 'value' => (string) $current->count()],
            ['label' => __('app.reports.prior_month'), 'value' => (string) $prior->count()],
            ['label' => __('app.dashboard.total_towers'), 'value' => (string) $all->count()],
        ];

        $byRegion = $this->towersGrouped($user, $filters, fn (Tower $tower) => $tower->region?->localizedName() ?: '—');

        return new ReportResult(layout: 'snapshot', kpis: $kpis, sections: [
            ['title' => __('app.reports.items.towers_by_region'), 'columns' => $byRegion->columns, 'rows' => $byRegion->rows],
        ]);
    }

    /**
     * @return Collection<int, Tower>
     */
    private function filteredTowers(User $user, array $filters, ?string $dateColumn = null): Collection
    {
        $query = Tower::query()
            ->visibleTo($user)
            ->with(['operator', 'region', 'district', 'subDistrict', 'latestInspection', 'currentApprovalLetter']);

        if (! empty($filters['region_id'])) {
            $query->where('region_id', (int) $filters['region_id']);
        }
        if (! empty($filters['district_id'])) {
            $query->where('district_id', (int) $filters['district_id']);
        }
        if (! empty($filters['sub_district_id'])) {
            $query->where('sub_district_id', (int) $filters['sub_district_id']);
        }
        $this->applyOperatorFilter($query, $filters, 'operator_id');
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['health_status'])) {
            $query->where('health_status', $filters['health_status']);
        }
        if (! empty($filters['power_source'])) {
            $query->withPowerSource($filters['power_source']);
        }
        if (! empty($filters['missing_district'])) {
            $query->whereNull('district_id');
        }
        if ($dateColumn) {
            $this->applyDate($query, $filters, $dateColumn);
        }

        return $query->orderBy('name')->get();
    }

    private function applyDate($query, array $filters, string $column): void
    {
        if (! empty($filters['from'])) {
            $query->whereDate($column, '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate($column, '<=', $filters['to']);
        }
    }

    private function applyOperatorFilter($query, array $filters, string $column): void
    {
        if (! empty($filters['operator_id'])) {
            $query->where($column, (int) $filters['operator_id']);
        }
    }

    private function applyTowerRelationFilters($query, array $filters): void
    {
        $query->whereHas('tower', function ($towers) use ($filters) {
            if (! empty($filters['region_id'])) {
                $towers->where('region_id', (int) $filters['region_id']);
            }
            if (! empty($filters['district_id'])) {
                $towers->where('district_id', (int) $filters['district_id']);
            }
            if (! empty($filters['operator_id'])) {
                $towers->where('operator_id', (int) $filters['operator_id']);
            }
        });
    }

    private function towerRow(Tower $tower): array
    {
        return [
            'name' => $tower->name,
            'coords' => number_format((float) $tower->latitude, 4).', '.number_format((float) $tower->longitude, 4),
            'region' => $tower->region?->localizedName() ?: '—',
            'district' => $tower->district?->localizedName() ?: '—',
            'sub_district' => $tower->subDistrict?->localizedName() ?: '—',
            'operator' => $tower->operator?->name ?: '—',
            'type' => __('app.status.'.$tower->type),
            'height' => $tower->height_m ? rtrim(rtrim(number_format((float) $tower->height_m, 1), '0'), '.').' m' : '—',
            'capacity' => $tower->capacity ? strtoupper((string) $tower->capacity) : '—',
            'radius' => $tower->signal_radius_m ? number_format($tower->signal_radius_m / 1000, 1).' km' : '—',
            'status' => __('app.status.'.$tower->status),
            'health' => __('app.status.'.$tower->health_status),
            'power' => $tower->powerSourceLabel() ?: '—',
            'commissioned' => optional($tower->commissioned_at)?->toDateString() ?: '—',
            'last_inspection' => optional($tower->latestInspection?->inspected_at)?->toDateString() ?: '—',
        ];
    }

    private function frequencyRow(FrequencyAllocation $allocation): array
    {
        return [
            'operator' => $allocation->operator?->name ?: '—',
            'band' => $allocation->band_label,
            'range' => $allocation->frequency_range,
            'coverage' => $allocation->coverageLabel(),
            'issued' => optional($allocation->issued_at)?->toDateString() ?: '—',
            'expires' => optional($allocation->expires_at)?->toDateString() ?: '—',
            'status' => __('app.status.'.$allocation->display_status),
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function masterColumns(): array
    {
        return [
            $this->col('no', __('app.reports.no')),
            $this->col('name', __('app.towers.name')),
            $this->col('coords', __('app.reports.coords')),
            $this->col('region', __('app.towers.region')),
            $this->col('district', __('app.towers.district')),
            $this->col('sub_district', __('app.towers.sub_district')),
            $this->col('operator', __('app.towers.operator')),
            $this->col('type', __('app.towers.type')),
            $this->col('height', __('app.towers.height')),
            $this->col('capacity', __('app.towers.capacity')),
            $this->col('radius', __('app.map.signal_radius')),
            $this->col('status', __('app.towers.status')),
            $this->col('health', __('app.towers.health')),
            $this->col('power', __('app.towers.power_source')),
            $this->col('commissioned', __('app.towers.commissioned')),
            $this->col('last_inspection', __('app.map.last_inspection')),
        ];
    }

    private function groupCountColumns(): array
    {
        return [
            $this->col('group', __('app.reports.group')),
            $this->col('total', __('app.reports.total')),
            $this->col('active', __('app.status.active')),
            $this->col('under_construction', __('app.status.under_construction')),
            $this->col('decommissioned', __('app.status.decommissioned')),
            $this->col('good', __('app.status.good')),
            $this->col('needs_attention', __('app.status.needs_attention')),
            $this->col('critical', __('app.status.critical')),
            $this->col('unknown', __('app.status.unknown')),
        ];
    }

    private function letterColumns(): array
    {
        return [
            $this->col('reference', __('app.approval_letters.ref_no')),
            $this->col('tower', __('app.towers.name')),
            $this->col('operator', __('app.towers.operator')),
            $this->col('region', __('app.towers.region')),
            $this->col('district', __('app.towers.district')),
            $this->col('issued', __('app.licenses.issued')),
            $this->col('issuer', __('app.reports.issued_by')),
        ];
    }

    private function frequencyColumns(): array
    {
        return [
            $this->col('operator', __('app.towers.operator')),
            $this->col('band', __('app.frequencies.band')),
            $this->col('range', __('app.frequencies.range')),
            $this->col('coverage', __('app.frequencies.coverage')),
            $this->col('issued', __('app.licenses.issued')),
            $this->col('expires', __('app.licenses.expires')),
            $this->col('status', __('app.towers.status')),
        ];
    }

    private function col(string $key, string $label): array
    {
        return ['key' => $key, 'label' => $label];
    }

    private function sort(ReportResult $result, array $filters): ReportResult
    {
        $sort = $filters['sort'] ?? null;
        $keys = collect($result->columns)->pluck('key');

        if (! $sort || $sort === 'no' || ! $keys->contains($sort)) {
            return $result;
        }

        $dir = ($filters['dir'] ?? 'asc') === 'desc' ? -1 : 1;
        $rows = collect($result->rows)->sortBy(fn (array $row) => $row[$sort] ?? '', SORT_NATURAL | SORT_FLAG_CASE, $dir === -1)->values()->all();

        $sections = collect($result->sections)->map(function (array $section) use ($sort, $dir) {
            $sectionKeys = collect($section['columns'] ?? [])->pluck('key');
            if (! $sectionKeys->contains($sort)) {
                return $section;
            }
            $section['rows'] = collect($section['rows'] ?? [])->sortBy(fn (array $row) => $row[$sort] ?? '', SORT_NATURAL | SORT_FLAG_CASE, $dir === -1)->values()->all();

            return $section;
        })->all();

        return new ReportResult($result->columns, $rows, $result->layout, $result->kpis, $sections);
    }

    private function numberRows(ReportResult $result): ReportResult
    {
        $hasNo = collect($result->columns)->contains(fn (array $column) => $column['key'] === 'no');

        if (! $hasNo) {
            return $result;
        }

        $rows = collect($result->rows)->values()->map(function (array $row, int $index) {
            $row['no'] = $index + 1;

            return $row;
        })->all();

        $sections = collect($result->sections)->map(function (array $section) {
            $sectionHasNo = collect($section['columns'] ?? [])->contains(fn (array $column) => ($column['key'] ?? '') === 'no');
            if (! $sectionHasNo) {
                return $section;
            }

            $section['rows'] = collect($section['rows'] ?? [])->values()->map(function (array $row, int $index) {
                $row['no'] = $index + 1;

                return $row;
            })->all();

            return $section;
        })->all();

        return new ReportResult($result->columns, $rows, $result->layout, $result->kpis, $sections);
    }
}
