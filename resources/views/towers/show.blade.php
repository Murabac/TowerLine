<x-app-layout>
    <div class="p-4 lg:p-8 space-y-5">
        <div>
            <a href="{{ route('towers.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                {{ __('app.towers.title') }}
            </a>
        </div>

        <div class="data-card">
            <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-5 border-b border-gray-100">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <x-status-badge :value="$tower->status" />
                        <x-status-badge :value="$tower->operator->category" />
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight text-brand">{{ $tower->name }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $tower->operator->color }}"></span>
                            {{ $tower->operator->name }}
                        </span>
                        <span class="text-gray-300">·</span>
                        <span>{{ $tower->region->localizedName() }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @can('update', $tower)
                        <a href="{{ route('towers.edit', $tower) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L8.193 18.46a4.5 4.5 0 0 1-1.897 1.13L4.5 20.25l.66-1.796a4.5 4.5 0 0 1 1.13-1.897Z"/></svg>
                            {{ __('app.edit') }}
                        </a>
                    @endcan
                    @can('delete', $tower)
                        <form method="POST" action="{{ route('towers.destroy', $tower) }}" onsubmit="return confirm(@json(__('app.towers.confirm_delete')))">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center px-4 py-2.5 text-sm font-semibold rounded-xl text-red-700 bg-red-50 hover:bg-red-100">{{ __('app.delete') }}</button>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3 p-5">
                <div class="stat-tile">
                    <p class="stat-label">{{ __('app.towers.type') }}</p>
                    <p class="stat-value">{{ __('app.status.'.$tower->type) }}</p>
                </div>
                <div class="stat-tile">
                    <p class="stat-label">{{ __('app.towers.height') }}</p>
                    <p class="stat-value tabular-nums">{{ rtrim(rtrim(number_format($tower->height_m, 1), '0'), '.') }} <span class="text-sm font-medium text-gray-400">m</span></p>
                </div>
                <div class="stat-tile">
                    <p class="stat-label">{{ __('app.towers.signal_radius') }}</p>
                    <p class="stat-value tabular-nums">{{ number_format($tower->signal_radius_m) }} <span class="text-sm font-medium text-gray-400">m</span></p>
                </div>
                <div class="stat-tile">
                    <p class="stat-label">{{ __('app.towers.commissioned') }}</p>
                    <p class="stat-value">{{ $tower->commissioned_at?->format('d M Y') ?: '—' }}</p>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-5 gap-5">
            <div class="lg:col-span-2 data-card p-5">
                <h2 class="text-sm font-semibold text-brand mb-4">{{ __('app.towers.overview') }}</h2>
                <dl class="space-y-0 divide-y divide-gray-100">
                    @foreach ([
                        [__('app.towers.name'), $tower->name],
                        [__('app.towers.operator'), $tower->operator->name],
                        [__('app.towers.region'), $tower->region->localizedName()],
                        [__('app.towers.capacity'), $tower->capacity ?: '—'],
                        [__('app.towers.status'), null],
                        [__('app.towers.coordinates'), number_format($tower->latitude, 6).', '.number_format($tower->longitude, 6)],
                    ] as [$label, $value])
                        <div class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                            <dt class="text-sm text-gray-500 shrink-0">{{ $label }}</dt>
                            <dd class="text-sm font-medium text-gray-900 text-right">
                                @if ($label === __('app.towers.status'))
                                    <x-status-badge :value="$tower->status" />
                                @elseif ($label === __('app.towers.coordinates'))
                                    <span class="font-mono text-xs text-gray-600">{{ $value }}</span>
                                @else
                                    {{ $value }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div class="lg:col-span-3 data-card overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-sm font-semibold text-brand">{{ __('app.towers.location') }}</h2>
                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ number_format($tower->latitude, 6) }}, {{ number_format($tower->longitude, 6) }}</p>
                    </div>
                    <p class="text-xs text-gray-400">{{ __('app.map.coverage') }} · {{ number_format($tower->signal_radius_m / 1000, 1) }} km</p>
                </div>
                <div id="detail-map" class="h-[22rem]"></div>
            </div>
        </div>
    </div>

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            const lat = {{ $tower->latitude }};
            const lng = {{ $tower->longitude }};
            const color = @json($tower->operator->color);
            const map = L.map('detail-map').setView([lat, lng], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
            L.circle([lat, lng], {
                radius: {{ $tower->signal_radius_m }},
                color,
                fillColor: color,
                fillOpacity: 0.12,
                weight: 1.5,
            }).addTo(map);
            const icon = L.divIcon({
                className: '',
                html: `<span style="display:block;width:18px;height:18px;border-radius:9999px;background:${color};border:3px solid white;box-shadow:0 1px 6px rgba(0,0,0,.25)"></span>`,
                iconSize: [18, 18],
                iconAnchor: [9, 9],
            });
            L.marker([lat, lng], { icon }).addTo(map).bindPopup(@json($tower->name));
        </script>
    @endpush
</x-app-layout>
