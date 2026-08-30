<x-app-layout>
    <div class="p-4 lg:p-8 space-y-5 max-w-6xl">
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-brand-muted">{{ __('app.app_short') }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand">{{ __('app.geography.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('app.geography.subtitle') }}</p>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ __('app.geography.interim_notice') }}
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach ($regions as $region)
                <a
                    href="{{ route('districts.index', ['region_id' => $region->id, 'q' => $search ?: null]) }}"
                    class="rounded-xl border px-4 py-3 transition-colors {{ $selectedRegionId === $region->id ? 'border-brand bg-brand/5 ring-1 ring-brand/20' : 'border-gray-200 bg-white hover:border-brand/40' }}"
                >
                    <p class="text-sm font-semibold text-brand">{{ $region->localizedName() }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ __('app.geography.districts_count', ['count' => $region->districts_count]) }}</p>
                </a>
            @endforeach
        </div>

        @if ($selectedRegionId)
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="region_id" value="{{ $selectedRegionId }}">
                <div class="relative flex-1">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="{{ __('app.geography.search_district') }}"
                        class="field w-full pl-10"
                    >
                </div>
                <button class="inline-flex items-center justify-center px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                    {{ __('app.filter') }}
                </button>
            </form>

            <div class="data-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('app.geography.district') }}</th>
                                <th>{{ __('app.geography.grade') }}</th>
                                <th>{{ __('app.geography.sub_district') }}</th>
                                <th>{{ __('app.geography.map_areas') }}</th>
                                <th>{{ __('app.towers.title') }}</th>
                                <th class="text-right">{{ __('app.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($districts as $district)
                                <tr>
                                    <td>
                                        <p class="font-medium text-gray-900">{{ $district->name }}</p>
                                    </td>
                                    <td class="text-gray-600">{{ $district->grade ?: '—' }}</td>
                                    <td class="text-gray-600">{{ $district->sub_districts_count }}</td>
                                    <td class="text-gray-600">
                                        {{ __('app.geography.mapped_fraction', ['mapped' => $district->mapped_sub_districts_count, 'total' => $district->sub_districts_count]) }}
                                    </td>
                                    <td class="text-gray-600">{{ $district->towers_count }}</td>
                                    <td class="text-right">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            @can('update', $district)
                                                <a href="{{ route('districts.edit', $district) }}" class="text-sm font-semibold text-brand hover:underline">
                                                    {{ __('app.geography.manage') }}
                                                </a>
                                            @else
                                                <span class="text-sm text-gray-400">{{ __('app.view') }}</span>
                                            @endcan
                                            @can('delete', $district)
                                                <form method="POST" action="{{ route('districts.destroy', $district) }}" onsubmit="return confirm(@json(__('app.geography.confirm_delete_district')))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-semibold text-red-600 hover:underline">
                                                        {{ __('app.delete') }}
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="!py-12 text-center text-sm text-gray-500">
                                        {{ __('app.geography.no_districts_found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="data-card px-6 py-12 text-center">
                <p class="text-sm text-gray-500">{{ __('app.geography.select_region_prompt') }}</p>
            </div>
        @endif
    </div>
</x-app-layout>
