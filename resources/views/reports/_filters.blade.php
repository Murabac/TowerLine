@php
    $needs = fn (string $filter) => in_array($filter, $report->filters, true);
@endphp

<form method="GET" action="{{ route('reports.show', $report->key) }}" class="data-toolbar no-print">
        <input type="hidden" name="sort" value="{{ $filters['sort'] ?? '' }}">
        <input type="hidden" name="dir" value="{{ $filters['dir'] ?? '' }}">
        @if ($needs('dates'))
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="field lg:w-40" title="{{ __('app.reports.from') }}">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="field lg:w-40" title="{{ __('app.reports.to') }}">
    @endif
    @if ($needs('month'))
        <input type="month" name="month" value="{{ $filters['month'] ?? now()->format('Y-m') }}" class="field lg:w-44">
    @endif
    @if ($needs('window'))
        <select name="window" class="field lg:w-40">
            @foreach ([30, 60, 90] as $days)
                <option value="{{ $days }}" @selected((int) ($filters['window'] ?? 30) === $days)>{{ __('app.reports.window', ['days' => $days]) }}</option>
            @endforeach
        </select>
    @endif
    @if ($needs('geography'))
        <x-geography-filters
            :regions="$regions"
            :initial-districts="$initialDistricts ?? []"
            :initial-sub-districts="$initialSubDistricts ?? []"
        />
    @endif
    @if ($needs('operator'))
        <select name="operator_id" class="field lg:w-44">
            <option value="">{{ __('app.towers.operator') }}</option>
            @foreach ($operators as $operator)
                <option value="{{ $operator->id }}" @selected(($filters['operator_id'] ?? '') == $operator->id)>{{ $operator->displayName() }}</option>
            @endforeach
        </select>
    @endif
    @if ($needs('status'))
        <select name="status" class="field lg:w-44">
            <option value="">{{ __('app.towers.status') }}</option>
            @foreach (['active', 'under_construction', 'decommissioned'] as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ __('app.status.'.$status) }}</option>
            @endforeach
        </select>
    @endif
    @if ($needs('health'))
        <select name="health_status" class="field lg:w-44">
            <option value="">{{ __('app.map.health') }}</option>
            @foreach (['good', 'needs_attention', 'critical', 'unknown'] as $health)
                <option value="{{ $health }}" @selected(($filters['health_status'] ?? '') === $health)>{{ __('app.status.'.$health) }}</option>
            @endforeach
        </select>
    @endif
    @if ($needs('power'))
        <select name="power_source" class="field lg:w-44">
            <option value="">{{ __('app.towers.power_source') }}</option>
            @foreach (\App\Support\TowerPowerSource::OPTIONS as $source)
                <option value="{{ $source }}" @selected(($filters['power_source'] ?? '') === $source)>{{ __('app.towers.power_sources.'.$source) }}</option>
            @endforeach
        </select>
    @endif
    @if ($needs('columns'))
        <details class="lg:col-span-2">
            <summary class="text-sm font-medium text-gray-700 cursor-pointer">{{ __('app.reports.columns') }}</summary>
            <div class="mt-2 grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
                @foreach ($masterColumns as $column)
                    @if ($column['key'] === 'no')
                        @continue
                    @endif
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="columns[]" value="{{ $column['key'] }}" class="rounded border-gray-300 text-brand" @checked(in_array($column['key'], (array) ($filters['columns'] ?? ['name', 'region', 'district', 'operator', 'status', 'health']), true))>
                        {{ $column['label'] }}
                    </label>
                @endforeach
            </div>
        </details>
    @endif
    <div class="flex items-center gap-2">
        <button class="inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand-dark">{{ __('app.filter') }}</button>
        <a href="{{ route('reports.show', $report->key) }}" class="text-sm text-gray-500 hover:text-brand">{{ __('app.reset') }}</a>
    </div>
</form>
