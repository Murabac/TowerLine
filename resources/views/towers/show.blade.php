<x-app-layout>
    <div class="p-4 lg:p-8 space-y-5">
        <div>
            <a href="{{ route('towers.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                {{ __('app.towers.title') }}
            </a>
        </div>

        @if ($tower->pendingApprovalRequests->isNotEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">{{ __('app.approvals.tower_pending_title') }}</p>
                <ul class="mt-1 space-y-1">
                    @foreach ($tower->pendingApprovalRequests as $pending)
                        <li>
                            {{ $pending->typeLabel() }} · {{ $pending->submitter?->name }}
                            @can('update', $pending)
                                · <a href="{{ route('approvals.edit', $pending) }}" class="font-semibold underline">{{ __('app.approvals.correct') }}</a>
                            @elsecan('view', $pending)
                                · <a href="{{ route('approvals.show', $pending) }}" class="font-semibold underline">{{ __('app.approvals.review') }}</a>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

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
                        @if ($tower->district)
                            <span class="text-gray-300">·</span>
                            <span>{{ $tower->district->localizedName() }}</span>
                        @endif
                        @if ($tower->subDistrict)
                            <span class="text-gray-300">·</span>
                            <span>{{ $tower->subDistrict->localizedName() }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @can('create', [App\Models\Inspection::class, $tower])
                        <a href="{{ route('towers.inspections.create', $tower) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-sm font-semibold rounded-xl hover:border-brand hover:text-brand">
                            {{ __('app.towers.new_inspection') }}
                        </a>
                    @endcan
                    <a href="{{ route('towers.approval-letter.show', $tower) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-sm font-semibold rounded-xl hover:border-brand hover:text-brand">
                        {{ __('app.approval_letters.title') }}
                    </a>
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

            <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3 p-5">
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
                    <p class="stat-label">{{ __('app.towers.health') }}</p>
                    <p class="stat-value"><x-status-badge :value="$tower->health_status" /></p>
                </div>
                <div class="stat-tile">
                    <p class="stat-label">{{ __('app.towers.power_source') }}</p>
                    <p class="stat-value text-sm leading-snug">{{ $tower->powerSourceLabel() }}</p>
                </div>
                <div class="stat-tile">
                    <p class="stat-label">{{ __('app.towers.commissioned') }}</p>
                    <p class="stat-value">{{ $tower->commissioned_at?->format('d M Y') ?: '—' }}</p>
                </div>
                <div class="stat-tile">
                    <p class="stat-label">{{ __('app.approval_letters.title') }}</p>
                    <p class="stat-value text-sm leading-snug">
                        @if ($tower->currentApprovalLetter)
                            <span class="font-semibold text-brand">{{ $tower->currentApprovalLetter->reference_number }}</span>
                            <span class="block text-xs font-normal text-gray-500 mt-0.5">{{ $tower->currentApprovalLetter->issued_at->format('d M Y') }}</span>
                        @else
                            <span class="text-sm font-medium text-gray-400">{{ __('app.approval_letters.none_short') }}</span>
                        @endif
                    </p>
                </div>
                <div class="stat-tile">
                    <p class="stat-label">{{ __('app.licenses.state') }}</p>
                    <p class="stat-value">
                        @if ($tower->licenses->first())
                            <x-status-badge :value="$tower->licenses->first()->display_status" />
                        @else
                            <span class="text-sm font-medium text-gray-400">{{ __('app.map.no_license') }}</span>
                        @endif
                    </p>
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
                        [__('app.towers.district'), $tower->district?->localizedName() ?? '—'],
                        [__('app.towers.sub_district'), $tower->subDistrict?->localizedName() ?? '—'],
                        [__('app.towers.form_fields.city'), $tower->city ?: '—'],
                        [__('app.towers.power_source'), $tower->powerSourceLabel()],
                        [__('app.towers.form_fields.land_area'), $tower->land_area ?: '—'],
                        [__('app.towers.form_fields.nearest_school_m'), $tower->proximityLabel($tower->nearest_school_name, $tower->nearest_school_m)],
                        [__('app.towers.form_fields.nearest_hospital_m'), $tower->proximityLabel($tower->nearest_hospital_name, $tower->nearest_hospital_m)],
                        [__('app.towers.form_fields.nearest_house_m'), $tower->proximityLabel($tower->nearest_house_name, $tower->nearest_house_m)],
                        [__('app.towers.form_fields.fence_distance_m'), $tower->fence_distance_m !== null ? rtrim(rtrim(number_format($tower->fence_distance_m, 1), '0'), '.').' m' : '—'],
                        [__('app.towers.form_fields.other_towers_nearby'), 'nearby'],
                        [__('app.towers.form_fields.site_map_notes'), $tower->site_map_notes ?: '—'],
                        [__('app.towers.form_fields.site_map_file'), $tower->site_map_path ? 'file' : '—'],
                        [__('app.towers.capacity'), $tower->capacityLabel()],
                        [__('app.towers.status'), null],
                        [__('app.towers.coordinates'), number_format($tower->latitude, 6).', '.number_format($tower->longitude, 6)],
                        [__('app.towers.form_fields.application_date'), $tower->application_date?->format('d M Y') ?: '—'],
                        [__('app.towers.form_fields.inspector_notes'), $tower->registration_inspector_notes ?: '—'],
                        [__('app.towers.form_fields.director_notes'), $tower->registration_director_notes ?: '—'],
                    ] as [$label, $value])
                        <div class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                            <dt class="text-sm text-gray-500 shrink-0">{{ $label }}</dt>
                            <dd class="text-sm font-medium text-gray-900 text-right">
                                @if ($label === __('app.towers.status'))
                                    <x-status-badge :value="$tower->status" />
                                @elseif ($value === 'nearby')
                                    @php $nearbyTowers = $tower->nearbyTowers(); @endphp
                                    @if ($nearbyTowers->isNotEmpty())
                                        <ul class="space-y-1 text-right">
                                            @foreach ($nearbyTowers as $item)
                                                <li>
                                                    <a href="{{ route('towers.show', $item['tower']) }}" class="text-brand hover:underline">{{ $item['tower']->name }}</a>
                                                    <span class="block text-xs text-gray-500">{{ $item['tower']->operator->name }} · {{ number_format($item['distance_m']) }} m</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @elseif ($tower->other_towers_nearby)
                                        {{ $tower->other_towers_nearby }}
                                    @else
                                        —
                                    @endif
                                @elseif ($value === 'file')
                                    @if ($tower->site_map_path)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($tower->site_map_path) }}" class="text-brand hover:underline" target="_blank" rel="noopener">{{ __('app.towers.view_site_map') }}</a>
                                    @else
                                        —
                                    @endif
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

        <div class="data-card">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-brand">{{ __('app.towers.history_inspections') }}</h2>
                @if ($tower->isInspectionOverdue())
                    <span class="text-xs font-medium text-amber-700">{{ __('app.inspections.stale', ['days' => \App\Models\Tower::INSPECTION_STALE_DAYS]) }}</span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="data-table min-w-0">
                    <thead>
                        <tr>
                            <th>{{ __('app.inspections.date') }}</th>
                            <th>{{ __('app.inspections.inspector') }}</th>
                            <th>{{ __('app.inspections.power') }}</th>
                            <th>{{ __('app.inspections.physical') }}</th>
                            <th>{{ __('app.inspections.notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tower->inspections as $inspection)
                            <tr>
                                <td class="whitespace-nowrap">{{ $inspection->inspected_at->format('d M Y') }}</td>
                                <td>{{ $inspection->inspector?->name ?: '—' }}</td>
                                <td>{{ $inspection->powerStatusLabel() }}</td>
                                <td>{{ $inspection->physicalConditionLabel() }}</td>
                                <td class="max-w-xs">
                                    <p class="truncate text-gray-500">{{ $inspection->notes ?: '—' }}</p>
                                    @if ($inspection->photoUrls())
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @foreach ($inspection->photoUrls() as $url)
                                                <a href="{{ $url }}" target="_blank" rel="noopener" class="block h-10 w-10 overflow-hidden rounded-md border border-gray-200">
                                                    <img src="{{ $url }}" alt="" class="h-full w-full object-cover">
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="!py-10 text-center text-sm text-gray-500">{{ __('app.inspections.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="data-card">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-brand">{{ __('app.towers.history_licenses') }}</h2>
                @can('create', App\Models\License::class)
                    <a href="{{ route('licenses.create', ['tower_id' => $tower->id]) }}" class="text-sm font-medium text-brand hover:underline">{{ __('app.licenses.create') }}</a>
                @endcan
            </div>
            <p class="px-5 pt-3 text-xs text-gray-500">{{ __('app.approval_letters.legacy_license_note') }}</p>
            <div class="overflow-x-auto">
                <table class="data-table min-w-0">
                    <thead>
                        <tr>
                            <th>{{ __('app.licenses.type') }}</th>
                            <th>{{ __('app.licenses.issued') }}</th>
                            <th>{{ __('app.licenses.expires') }}</th>
                            <th>{{ __('app.licenses.state') }}</th>
                            <th>{{ __('app.licenses.documents') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tower->licenses as $license)
                            <tr>
                                <td><x-status-badge :value="$license->license_type" /></td>
                                <td>{{ $license->issued_at->format('d M Y') }}</td>
                                <td>{{ $license->expires_at->format('d M Y') }}</td>
                                <td><x-status-badge :value="$license->display_status" /></td>
                                <td>
                                    @if ($license->documentList())
                                        <div class="flex flex-col gap-1">
                                            @foreach ($license->documentList() as $document)
                                                <a href="{{ $document['url'] }}" target="_blank" rel="noopener" class="truncate text-sm font-medium text-brand hover:underline">{{ $document['name'] }}</a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="!py-10 text-center text-sm text-gray-500">{{ __('app.licenses.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
            const map = L.map('detail-map').setView([lat, lng], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19,
            }).addTo(map);
            const circle = L.circle([lat, lng], {
                radius: {{ (int) $tower->signal_radius_m }},
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

            function fitCoverage() {
                const size = map.getSize();
                if (size.x < 50 || size.y < 50) {
                    return false;
                }
                map.invalidateSize();
                map.fitBounds(circle.getBounds(), {
                    padding: [28, 28],
                    maxZoom: 15,
                    animate: false,
                });
                return true;
            }

            map.whenReady(() => {
                if (! fitCoverage()) {
                    setTimeout(fitCoverage, 150);
                }
            });
            window.addEventListener('load', () => setTimeout(fitCoverage, 50));
        </script>
    @endpush
</x-app-layout>
