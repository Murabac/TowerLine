<x-app-layout>
    <div class="p-4 lg:p-8">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-5">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-brand-muted">{{ __('app.app_short') }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand">{{ __('app.towers.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ number_format($towers->total()) }} {{ strtolower(__('app.towers.title')) }}</p>
            </div>
            @can('create', App\Models\Tower::class)
                <a href="{{ route('towers.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl shadow-sm hover:bg-brand-dark">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    {{ __('app.towers.create') }}
                </a>
            @endcan
        </div>

        <div class="data-card">
            <form method="GET" class="data-toolbar">
                <div class="relative flex-1 min-w-[14rem]">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                    </svg>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('app.search') }}…" class="field pl-10">
                </div>
                <x-geography-filters
                    :regions="$regions"
                    :initial-districts="$initialDistricts ?? []"
                    :initial-sub-districts="$initialSubDistricts ?? []"
                />
                <select name="operator_id" class="field lg:w-44">
                    <option value="">{{ __('app.towers.operator') }}</option>
                    @foreach ($operators as $operator)
                        <option value="{{ $operator->id }}" @selected(request('operator_id') == $operator->id)>{{ $operator->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="field lg:w-44">
                    <option value="">{{ __('app.towers.status') }}</option>
                    @foreach (['active', 'under_construction', 'decommissioned'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('app.status.'.$status) }}</option>
                    @endforeach
                </select>
                <select name="power_source" class="field lg:w-44">
                    <option value="">{{ __('app.towers.power_source') }}</option>
                    @foreach (\App\Support\TowerPowerSource::OPTIONS as $source)
                        <option value="{{ $source }}" @selected(request('power_source') === $source)>{{ __('app.towers.power_sources.'.$source) }}</option>
                    @endforeach
                </select>
                <div class="flex items-center gap-2">
                    <button class="inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand-dark">{{ __('app.filter') }}</button>
                    @if (request()->hasAny(['q', 'region_id', 'district_id', 'sub_district_id', 'operator_id', 'status', 'power_source']))
                        <a href="{{ route('towers.index') }}" class="text-sm text-gray-500 hover:text-brand">{{ __('app.reset') }}</a>
                    @endif
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.towers.name') }}</th>
                            <th>{{ __('app.towers.region') }}</th>
                            <th>{{ __('app.towers.district') }}</th>
                            <th>{{ __('app.towers.operator') }}</th>
                            <th>{{ __('app.towers.type') }}</th>
                            <th>{{ __('app.towers.height') }}</th>
                            <th>{{ __('app.towers.status') }}</th>
                            <th class="text-right">{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($towers as $tower)
                            <tr>
                                <td>
                                    <a href="{{ route('towers.show', $tower) }}" class="font-semibold text-gray-900 hover:text-brand">{{ $tower->name }}</a>
                                    <p class="mt-0.5 text-xs text-gray-400 font-mono">{{ number_format($tower->latitude, 4) }}, {{ number_format($tower->longitude, 4) }}</p>
                                </td>
                                <td class="text-gray-600">{{ $tower->region->localizedName() }}</td>
                                <td class="text-gray-600">
                                    {{ $tower->district?->localizedName() ?? '—' }}
                                    @if ($tower->subDistrict)
                                        <p class="text-[11px] text-gray-400">{{ $tower->subDistrict->localizedName() }}</p>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $tower->operator->color }}"></span>
                                        <div>
                                            <p class="font-medium text-gray-800">{{ $tower->operator->name }}</p>
                                            <p class="text-[11px] text-gray-400">{{ __('app.status.'.$tower->operator->category) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-gray-600">{{ __('app.status.'.$tower->type) }}</td>
                                <td class="tabular-nums text-gray-600">{{ rtrim(rtrim(number_format($tower->height_m, 1), '0'), '.') }} m</td>
                                <td><x-status-badge :value="$tower->status" /></td>
                                <td class="actions">
                                    <div class="inline-flex items-center gap-0.5">
                                        <a href="{{ route('towers.show', $tower) }}" class="icon-btn" title="{{ __('app.view') }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                                        </a>
                                        @can('update', $tower)
                                            <a href="{{ route('towers.edit', $tower) }}" class="icon-btn" title="{{ __('app.edit') }}">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L8.193 18.46a4.5 4.5 0 0 1-1.897 1.13L4.5 20.25l.66-1.796a4.5 4.5 0 0 1 1.13-1.897Z"/></svg>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="!py-16 text-center">
                                    <p class="text-sm font-medium text-gray-700">{{ __('app.none') }}</p>
                                    <p class="mt-1 text-sm text-gray-400">{{ __('app.towers.title') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($towers->total())
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-gray-100 bg-gray-50/50">
                    <p class="text-sm text-gray-500">
                        {{ $towers->firstItem() }}–{{ $towers->lastItem() }}
                        <span class="text-gray-400">/</span>
                        {{ $towers->total() }}
                    </p>
                    {{ $towers->onEachSide(1)->links('pagination.towerline') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
