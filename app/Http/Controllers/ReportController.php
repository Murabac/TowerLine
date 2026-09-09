<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Reports\ReportCatalog;
use App\Reports\ReportDefinition;
use App\Reports\ReportQuery;
use App\Reports\ReportResult;
use App\Support\ExcelWorkbook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canTask('reports.view'), 403);

        return view('reports.index', [
            'groups' => ReportCatalog::groupedFor($request->user()),
        ]);
    }

    public function show(Request $request, string $report, ReportQuery $query): View|RedirectResponse
    {
        if (($canonical = ReportCatalog::canonicalKey($report)) !== $report) {
            return redirect()->route('reports.show', array_filter([
                'report' => $canonical,
                ...$this->filters($request),
            ]));
        }

        $definition = $this->definition($request, $report);
        $filters = $this->filters($request);
        $result = $query->run($definition, $request->user(), $filters);

        return view($definition->layout === 'snapshot' ? 'reports.snapshot' : 'reports.show', [
            ...$this->pageData($request, $definition, $result, $filters),
        ]);
    }

    public function excel(Request $request, string $report, ReportQuery $query): Response|RedirectResponse
    {
        abort_unless($request->user()->canTask('reports.export_excel'), 403);

        if (($canonical = ReportCatalog::canonicalKey($report)) !== $report) {
            return redirect()->route('reports.excel', array_filter([
                'report' => $canonical,
                ...$this->filters($request),
            ]));
        }

        $definition = $this->definition($request, $report);
        $filters = $this->filters($request);
        $result = $query->run($definition, $request->user(), $filters);

        $headers = collect($result->columns)->pluck('label')->all();
        $rows = collect($result->rows)->map(function (array $row) use ($result) {
            return collect($result->columns)->map(fn (array $column) => (string) ($row[$column['key']] ?? ''))->all();
        })->all();

        if ($result->layout === 'snapshot') {
            $headers = [__('app.reports.metric'), __('app.reports.value')];
            $rows = collect($result->kpis)->map(fn (array $kpi) => [$kpi['label'], $kpi['value']])->all();
            foreach ($result->sections as $section) {
                $rows[] = [$section['title'], ''];
                $rows[] = collect($section['columns'])->pluck('label')->all();
                foreach ($section['rows'] as $row) {
                    $rows[] = collect($section['columns'])->map(fn (array $column) => (string) ($row[$column['key']] ?? ''))->all();
                }
            }
        }

        $filename = 'towerline-'.$definition->langKey().'-'.now()->format('Ymd').'.xlsx';

        return ExcelWorkbook::download($filename, $headers ?: [__('app.reports.title')], $rows);
    }

    private function definition(Request $request, string $report): ReportDefinition
    {
        abort_unless($request->user()->canTask('reports.view'), 403);

        $definition = ReportCatalog::find($report);
        abort_unless($definition && $definition->allowedFor($request->user()), 404);

        return $definition;
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->only([
            'from', 'to', 'month', 'window', 'region_id', 'district_id', 'sub_district_id',
            'operator_id', 'status', 'health_status', 'power_source', 'sort', 'dir', 'columns',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function pageData(Request $request, ReportDefinition $definition, ReportResult $result, array $filters): array
    {
        $user = $request->user();
        $regions = Region::query()->orderBy('name_en');

        if ($user->requiresRegions()) {
            $regions->whereIn('id', $user->regionIds() ?: [0]);
        }

        $regionId = $request->integer('region_id') ?: null;
        $districtId = $request->integer('district_id') ?: null;

        return [
            'report' => $definition,
            'result' => $result,
            'filters' => $filters,
            'regions' => $regions->get(),
            'operators' => Operator::query()->orderBy('name')->get(),
            'initialDistricts' => $regionId
                ? District::query()->where('region_id', $regionId)->orderBy('name')->get()->map(fn ($d) => ['id' => $d->id, 'name' => $d->localizedName()])->values()
                : collect(),
            'initialSubDistricts' => $districtId
                ? District::query()->find($districtId)?->subDistricts()->orderBy('name')->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->localizedName()])->values() ?? collect()
                : collect(),
            'sortUrl' => fn (string $column) => route('reports.show', array_filter([
                'report' => $definition->key,
                ...$filters,
                'sort' => $column,
                'dir' => ($filters['sort'] ?? '') === $column && ($filters['dir'] ?? 'asc') === 'asc' ? 'desc' : 'asc',
            ])),
            'masterColumns' => (new ReportQuery())->masterColumns(),
        ];
    }
}
