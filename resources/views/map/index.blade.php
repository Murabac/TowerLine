<x-app-layout :map="true">
    <div
        class="h-full min-h-0 flex"
        x-data="towerMap(@js([
            'endpoint' => route('map.towers'),
            'complaintsEndpoint' => $complaintsEndpoint,
            'districtsUrl' => route('geography.districts'),
            'subDistrictsUrl' => route('geography.sub-districts'),
            'operators' => $operatorOptions,
            'legendOperators' => $legendOperators,
            'filters' => $filters,
            'districts' => $districts->map(fn ($d) => ['id' => $d->id, 'name' => $d->localizedName()])->values(),
            'subDistricts' => $subDistricts->map(fn ($s) => ['id' => $s->id, 'name' => $s->localizedName()])->values(),
            'labels' => [
                'none' => __('app.none'),
                'view_details' => __('app.map.view_details'),
                'last_inspection' => __('app.map.last_inspection'),
                'license_expiry' => __('app.map.license_expiry'),
                'signal_radius' => __('app.map.signal_radius'),
                'power_source' => __('app.towers.power_source'),
                'no_license' => __('app.map.no_license'),
                'shown' => __('app.map.shown'),
                'overdue' => __('app.inspections.stale', ['days' => \App\Models\Tower::INSPECTION_STALE_DAYS]),
                'view_complaint' => __('app.complaints.notify.open'),
            ],
        ]))"
        x-init="init()"
    >
        <div
            x-show="filtersOpen"
            x-cloak
            class="md:hidden fixed inset-0 z-[1190] bg-black/40"
            @click="filtersOpen = false"
        ></div>

        <aside
            class="no-print flex-col border-gray-200 bg-white overflow-y-auto md:flex md:w-72 md:shrink-0 md:border-r md:relative"
            :class="filtersOpen
                ? 'fixed inset-x-0 bottom-0 z-[1200] flex max-h-[min(85vh,40rem)] rounded-t-2xl shadow-[0_-8px_30px_rgba(0,0,0,.18)] md:static md:inset-auto md:max-h-none md:rounded-none md:shadow-none'
                : 'hidden md:flex'"
        >
            <div class="md:hidden flex justify-center pt-2 pb-1">
                <span class="h-1 w-10 rounded-full bg-gray-300"></span>
            </div>
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <div>
                    <h1 class="text-sm font-semibold text-brand">{{ __('app.map.title') }}</h1>
                    <p class="text-xs text-gray-400" x-text="countLabel"></p>
                </div>
                <button type="button" class="md:hidden text-sm font-medium text-gray-500 px-2 py-1" @click="filtersOpen = false">{{ __('app.map.close') }}</button>
            </div>

            <form class="p-4 space-y-3" @submit.prevent="applyFilters()" @change="applyFilters()">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ __('app.map.filters') }}</p>

                <select name="region_id" x-model="filters.region_id" class="field" @change="onRegionFilterChange()">
                    <option value="">{{ __('app.towers.region') }}</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}">{{ $region->localizedName() }}</option>
                    @endforeach
                </select>

                <select name="district_id" x-model="filters.district_id" class="field" @change="onDistrictFilterChange()">
                    <option value="">{{ __('app.geography.select_district') }}</option>
                    <template x-for="district in districtOptions" :key="district.id">
                        <option :value="district.id" x-text="district.name"></option>
                    </template>
                </select>

                <select name="sub_district_id" x-model="filters.sub_district_id" class="field">
                    <option value="">{{ __('app.geography.select_sub_district') }}</option>
                    <template x-for="subDistrict in subDistrictOptions" :key="subDistrict.id">
                        <option :value="subDistrict.id" x-text="subDistrict.name"></option>
                    </template>
                </select>

                <select name="operator_id" x-model="filters.operator_id" class="field">
                    <option value="">{{ __('app.towers.operator') }}</option>
                    <template x-for="operator in operatorOptions" :key="operator.id">
                        <option :value="operator.id" x-text="operator.name"></option>
                    </template>
                </select>

                <select name="category" x-model="filters.category" class="field">
                    <option value="">{{ __('app.map.category') }}</option>
                    <option value="telecom">{{ __('app.status.telecom') }}</option>
                    <option value="broadcast">{{ __('app.status.broadcast') }}</option>
                </select>

                <select name="status" x-model="filters.status" class="field">
                    <option value="">{{ __('app.towers.status') }}</option>
                    @foreach (['active', 'under_construction', 'decommissioned'] as $status)
                        <option value="{{ $status }}">{{ __('app.status.'.$status) }}</option>
                    @endforeach
                </select>

                <select name="health_status" x-model="filters.health_status" class="field">
                    <option value="">{{ __('app.map.health') }}</option>
                    @foreach (['good', 'needs_attention', 'critical', 'unknown'] as $health)
                        <option value="{{ $health }}">{{ __('app.status.'.$health) }}</option>
                    @endforeach
                </select>

                <select name="power_source" x-model="filters.power_source" class="field">
                    <option value="">{{ __('app.towers.power_source') }}</option>
                    @foreach (\App\Support\TowerPowerSource::OPTIONS as $source)
                        <option value="{{ $source }}">{{ __('app.towers.power_sources.'.$source) }}</option>
                    @endforeach
                </select>

                <select name="license_state" x-model="filters.license_state" class="field">
                    <option value="">{{ __('app.map.license_state') }}</option>
                    <option value="active">{{ __('app.status.active') }}</option>
                    <option value="expiring_soon">{{ __('app.status.expiring_soon') }}</option>
                    <option value="expired">{{ __('app.status.expired') }}</option>
                    <option value="none">{{ __('app.map.no_license') }}</option>
                </select>

                <div class="flex items-center gap-3 pt-1">
                    <button class="px-3 py-1.5 bg-brand text-white text-sm font-semibold rounded-lg">{{ __('app.filter') }}</button>
                    <button type="button" class="text-sm text-gray-500 hover:text-brand" @click="resetFilters()">{{ __('app.reset') }}</button>
                </div>
            </form>

            <label class="flex items-center gap-2 text-sm text-gray-700 px-4 pb-3">
                <input type="checkbox" x-model="showCoverage" class="rounded border-gray-300 text-brand focus:ring-brand/30">
                {{ __('app.map.coverage') }}
            </label>
            @if ($canViewComplaints)
                <label class="flex items-center gap-2 text-sm text-gray-700 px-4 pb-3">
                    <input type="checkbox" x-model="showComplaints" class="rounded border-gray-300 text-brand focus:ring-brand/30">
                    {{ __('app.map.complaints_layer') }}
                </label>
            @endif
            <div class="px-4 pb-4 space-y-2">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ __('app.map.layers') }}</p>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 pt-1">{{ __('app.map.color_pins_by') }}</p>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="radio" value="operator" x-model="pinColorMode" class="border-gray-300 text-brand focus:ring-brand/30">
                    {{ __('app.map.color_by_operator') }}
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="radio" value="status" x-model="pinColorMode" class="border-gray-300 text-brand focus:ring-brand/30">
                    {{ __('app.map.color_by_status') }}
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 pt-1">
                    <input type="checkbox" x-model="onlyFlagged" class="rounded border-gray-300 text-brand focus:ring-brand/30">
                    {{ __('app.map.flagged') }}
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox"
                           class="rounded border-gray-300 text-brand focus:ring-brand/30"
                           :checked="filters.overdue === '1'"
                           @change="filters.overdue = $event.target.checked ? '1' : ''; applyFilters()">
                    {{ __('app.inspections.stale', ['days' => \App\Models\Tower::INSPECTION_STALE_DAYS]) }}
                </label>
                <p class="text-[11px] text-gray-400">{{ __('app.map.cluster_hint') }}</p>
            </div>

            <div class="mt-auto px-4 py-4 border-t border-gray-100 space-y-4">
                <div x-show="pinColorMode === 'operator'" x-cloak>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-2">{{ __('app.map.operator_legend') }}</p>
                    <ul class="space-y-1.5 text-xs text-gray-600">
                        @forelse ($operators as $operator)
                            <li class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $operator->color }}"></span>
                                {{ $operator->displayName() }}
                            </li>
                        @empty
                            <li class="text-gray-400">{{ __('app.none') }}</li>
                        @endforelse
                    </ul>
                </div>
                <div x-show="pinColorMode === 'status'" x-cloak>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-2">{{ __('app.map.status_legend') }}</p>
                    <ul class="space-y-1.5 text-xs text-gray-600">
                        <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-health-good"></span> {{ __('app.status.good') }}</li>
                        <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-health-attention"></span> {{ __('app.status.needs_attention') }}</li>
                        <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-health-critical"></span> {{ __('app.status.critical') }}</li>
                        <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-health-construction"></span> {{ __('app.status.under_construction') }}</li>
                        <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background:#94A3B8"></span> {{ __('app.status.unknown') }}</li>
                        <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-health-unknown"></span> {{ __('app.status.decommissioned') }}</li>
                    </ul>
                </div>
            </div>
        </aside>

        <div class="relative flex-1 min-w-0">
            <div class="no-print absolute top-3 right-3 z-[1100]">
                <button type="button" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium shadow-sm" @click="printMap()">{{ __('app.print_snapshot') }}</button>
            </div>

            <button
                type="button"
                class="md:hidden no-print fixed z-[1100] bottom-5 left-4 inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-3 text-sm font-semibold text-white shadow-lg"
                x-show="! filtersOpen"
                x-cloak
                @click="selected = null; filtersOpen = true"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M6 12h12M10 18h4"/>
                </svg>
                {{ __('app.map.toggle_filters') }}
                <span
                    x-show="activeFilterCount"
                    x-cloak
                    class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-white/20 px-1.5 text-[11px]"
                    x-text="activeFilterCount"
                ></span>
            </button>

            <div id="tower-map" class="absolute inset-0 bg-gray-100"></div>

            <div class="map-print-legend" aria-hidden="true">
                <div x-show="pinColorMode === 'operator'">
                    <p class="map-print-legend__title">{{ __('app.map.operator_legend') }}</p>
                    <ul class="map-print-legend__list">
                        <template x-for="operator in printLegendOperators" :key="operator.id || operator.name">
                            <li>
                                <span class="map-print-legend__swatch" :style="`background:${operator.color}`"></span>
                                <span x-text="operator.name"></span>
                            </li>
                        </template>
                    </ul>
                </div>
                <div x-show="pinColorMode === 'status'">
                    <p class="map-print-legend__title">{{ __('app.map.status_legend') }}</p>
                    <ul class="map-print-legend__list">
                        <li><span class="map-print-legend__swatch" style="background:#22C55E"></span> {{ __('app.status.good') }}</li>
                        <li><span class="map-print-legend__swatch" style="background:#F59E0B"></span> {{ __('app.status.needs_attention') }}</li>
                        <li><span class="map-print-legend__swatch" style="background:#DC2626"></span> {{ __('app.status.critical') }}</li>
                        <li><span class="map-print-legend__swatch" style="background:#2563EB"></span> {{ __('app.status.under_construction') }}</li>
                        <li><span class="map-print-legend__swatch" style="background:#94A3B8"></span> {{ __('app.status.unknown') }}</li>
                        <li><span class="map-print-legend__swatch" style="background:#6B7280"></span> {{ __('app.status.decommissioned') }}</li>
                    </ul>
                </div>
            </div>

            <div
                class="absolute z-[1050] top-16 left-3 right-3 md:left-auto md:right-3 md:w-80 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden"
                x-show="selected"
                x-cloak
            >
                <template x-if="selected">
                    <div>
                        <div class="flex items-start justify-between gap-3 px-4 py-3 border-b border-gray-100">
                            <div class="min-w-0">
                                <p class="font-semibold text-brand truncate" x-text="selected.name"></p>
                                <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1.5 min-w-0">
                                    <span
                                        x-show="pinColorMode === 'operator'"
                                        class="h-2 w-2 rounded-full shrink-0"
                                        :style="`background:${selected.operator.color}`"
                                    ></span>
                                    <span class="truncate" x-text="selected.operator.name + ' · ' + selected.region"></span>
                                    <template x-if="selected.district">
                                        <span x-text="' · ' + selected.district"></span>
                                    </template>
                                </p>
                            </div>
                            <button type="button" class="text-gray-400 hover:text-gray-700" @click="selected = null">{{ __('app.map.close') }}</button>
                        </div>
                        <dl class="px-4 py-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-400">{{ __('app.towers.status') }}</dt>
                                <dd class="font-medium" x-text="statusLabel(selected.status)"></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-400">{{ __('app.towers.health') }}</dt>
                                <dd class="font-medium" x-text="healthLabel(selected)"></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-400" x-text="labels.last_inspection"></dt>
                                <dd x-text="selected.last_inspection_at || labels.none"></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-400" x-text="labels.license_expiry"></dt>
                                <dd x-text="selected.license ? (selected.license.status_label + ' · ' + selected.license.expires_at) : labels.no_license"></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-400" x-text="labels.power_source"></dt>
                                <dd class="text-right" x-text="selected.power_sources_label || labels.none"></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-400" x-text="labels.signal_radius"></dt>
                                <dd x-text="(selected.signal_radius_m / 1000).toFixed(1) + ' km'"></dd>
                            </div>
                        </dl>
                        <a :href="selected.url" class="block px-4 py-3 text-sm font-semibold text-brand border-t border-gray-100 hover:bg-gray-50" x-text="labels.view_details"></a>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @php
        $statusLabels = [
            'active' => __('app.status.active'),
            'under_construction' => __('app.status.under_construction'),
            'decommissioned' => __('app.status.decommissioned'),
        ];
        $healthLabels = [
            'good' => __('app.status.good'),
            'needs_attention' => __('app.status.needs_attention'),
            'critical' => __('app.status.critical'),
            'unknown' => __('app.status.unknown'),
        ];
    @endphp

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
        <style>
            [x-cloak]{display:none !important}
            .map-print-legend { display: none; }
            @media print {
                .map-print-legend {
                    display: block !important;
                    position: absolute;
                    left: 12px;
                    bottom: 12px;
                    z-index: 1200;
                    background: #fff;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    padding: 10px 12px;
                    min-width: 10rem;
                    max-width: 16rem;
                    box-shadow: 0 1px 4px rgba(0,0,0,.12);
                }
                .map-print-legend__title {
                    margin: 0 0 6px;
                    font-size: 10px;
                    font-weight: 700;
                    letter-spacing: .08em;
                    text-transform: uppercase;
                    color: #6b7280;
                }
                .map-print-legend__list {
                    margin: 0;
                    padding: 0;
                    list-style: none;
                    display: grid;
                    gap: 4px;
                    font-size: 11px;
                    color: #374151;
                }
                .map-print-legend__list li {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }
                .map-print-legend__swatch {
                    width: 10px;
                    height: 10px;
                    border-radius: 9999px;
                    flex-shrink: 0;
                    border: 1px solid rgba(0,0,0,.12);
                }
            }
        </style>
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
        <script>
            const statusLabels = @json($statusLabels);
            const healthLabels = @json($healthLabels);

            function towerMap(config) {
                return {
                    endpoint: config.endpoint,
                    complaintsEndpoint: config.complaintsEndpoint,
                    districtsUrl: config.districtsUrl,
                    subDistrictsUrl: config.subDistrictsUrl,
                    labels: config.labels,
                    filters: {
                        region_id: config.filters.region_id || '',
                        district_id: config.filters.district_id || '',
                        sub_district_id: config.filters.sub_district_id || '',
                        operator_id: config.filters.operator_id || '',
                        category: config.filters.category || '',
                        status: config.filters.status || '',
                        health_status: config.filters.health_status || '',
                        power_source: config.filters.power_source || '',
                        license_state: config.filters.license_state || '',
                        overdue: config.filters.overdue ? '1' : '',
                    },
                    districtOptions: config.districts || [],
                    subDistrictOptions: config.subDistricts || [],
                    operatorOptions: config.operators || [],
                    showCoverage: true,
                    showComplaints: Boolean(config.complaintsEndpoint),
                    pinColorMode: localStorage.getItem('towerline-pin-color') || 'operator',
                    onlyFlagged: false,
                    onlyLicenseAlert: false,
                    filtersOpen: false,
                    selected: null,
                    count: 0,
                    allTowers: [],
                    mapBounds: null,
                    map: null,
                    markers: null,
                    circles: null,
                    complaintLayer: null,
                    get countLabel() {
                        return this.labels.shown.replace(':count', this.count);
                    },
                    get printLegendOperators() {
                        const seen = new Map();
                        this.visibleTowers().forEach((tower) => {
                            const key = String(tower.operator?.id || tower.operator?.name);
                            if (! seen.has(key)) {
                                seen.set(key, {
                                    id: tower.operator?.id || key,
                                    name: tower.operator?.name,
                                    color: tower.operator?.color,
                                });
                            }
                        });
                        return Array.from(seen.values());
                    },
                    get activeFilterCount() {
                        return Object.values(this.filters).filter(Boolean).length
                            + (this.onlyFlagged ? 1 : 0)
                            + (this.onlyLicenseAlert ? 1 : 0);
                    },
                    statusLabel(status) {
                        return statusLabels[status] || status;
                    },
                    healthLabel(tower) {
                        if (tower.overdue) {
                            return this.labels.overdue;
                        }
                        return healthLabels[tower.health_status] || tower.health_status;
                    },
                    pinColor(tower) {
                        if (this.pinColorMode === 'operator') {
                            return tower.operator?.color || tower.color;
                        }
                        return tower.status_color || tower.color;
                    },
                    init() {
                        this.map = L.map('tower-map', { zoomControl: true }).setView([9.56, 44.06], 7);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap',
                            maxZoom: 19,
                        }).addTo(this.map);
                        this.map.createPane('coverage');
                        this.map.getPane('coverage').style.zIndex = 350;
                        this.circles = L.layerGroup().addTo(this.map);
                        this.markers = L.markerClusterGroup({
                            showCoverageOnHover: false,
                            maxClusterRadius: 48,
                            disableClusteringAtZoom: 12,
                        });
                        this.map.addLayer(this.markers);
                        this.complaintLayer = L.layerGroup();
                        if (this.showComplaints && this.complaintsEndpoint) {
                            this.map.addLayer(this.complaintLayer);
                        }
                        this.$watch('showCoverage', (on) => {
                            if (on) {
                                this.map.addLayer(this.circles);
                            } else {
                                this.map.removeLayer(this.circles);
                            }
                        });
                        this.$watch('pinColorMode', (mode) => {
                            localStorage.setItem('towerline-pin-color', mode);
                            this.draw(false);
                        });
                        this.$watch('onlyFlagged', () => this.draw(false));
                        this.$watch('onlyLicenseAlert', () => this.draw(false));
                        this.$watch('showComplaints', (on) => {
                            if (! this.complaintLayer) {
                                return;
                            }
                            if (on && this.complaintsEndpoint) {
                                this.map.addLayer(this.complaintLayer);
                                this.loadComplaints();
                            } else {
                                this.map.removeLayer(this.complaintLayer);
                            }
                        });
                        this.loadTowers();
                        if (this.showComplaints && this.complaintsEndpoint) {
                            this.loadComplaints();
                        }
                        this.$nextTick(() => this.map.invalidateSize());
                        window.addEventListener('resize', () => this.map.invalidateSize());
                    },
                    queryString() {
                        const params = new URLSearchParams();
                        Object.entries(this.filters).forEach(([key, value]) => {
                            if (value) params.set(key, value);
                        });
                        return params.toString();
                    },
                    applyFilters() {
                        const qs = this.queryString();
                        history.replaceState({}, '', qs ? `{{ route('map') }}?${qs}` : '{{ route('map') }}');
                        this.loadTowers();
                        if (window.matchMedia('(max-width: 767px)').matches) {
                            this.filtersOpen = false;
                        }
                    },
                    resetFilters() {
                        Object.keys(this.filters).forEach((key) => { this.filters[key] = ''; });
                        this.districtOptions = [];
                        this.subDistrictOptions = [];
                        this.onlyFlagged = false;
                        this.onlyLicenseAlert = false;
                        this.applyFilters();
                    },
                    async onRegionFilterChange() {
                        this.filters.district_id = '';
                        this.filters.sub_district_id = '';
                        this.subDistrictOptions = [];
                        await this.loadDistrictOptions();
                        this.applyFilters();
                    },
                    async onDistrictFilterChange() {
                        this.filters.sub_district_id = '';
                        await this.loadSubDistrictOptions();
                        this.applyFilters();
                    },
                    async loadDistrictOptions() {
                        if (! this.filters.region_id) {
                            this.districtOptions = [];
                            return;
                        }
                        const response = await fetch(`${this.districtsUrl}?region_id=${this.filters.region_id}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const data = await response.json();
                        this.districtOptions = data.districts || [];
                    },
                    async loadSubDistrictOptions() {
                        if (! this.filters.district_id) {
                            this.subDistrictOptions = [];
                            return;
                        }
                        const response = await fetch(`${this.subDistrictsUrl}?district_id=${this.filters.district_id}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const data = await response.json();
                        this.subDistrictOptions = data.sub_districts || [];
                    },
                    async loadTowers() {
                        const qs = this.queryString();
                        const response = await fetch(qs ? `${this.endpoint}?${qs}` : this.endpoint, {
                            headers: { Accept: 'application/json' },
                        });
                        const data = await response.json();
                        this.allTowers = data.towers || [];
                        this.mapBounds = data.bounds || null;
                        this.draw(true);
                    },
                    async loadComplaints() {
                        if (! this.complaintsEndpoint || ! this.complaintLayer) {
                            return;
                        }
                        const response = await fetch(this.complaintsEndpoint, { headers: { Accept: 'application/json' } });
                        const data = await response.json();
                        this.complaintLayer.clearLayers();
                        (data.complaints || []).forEach((complaint) => {
                            const size = complaint.priority === 'critical' ? 18 : 14;
                            const icon = L.divIcon({
                                className: '',
                                html: `<span style="display:block;width:${size}px;height:${size}px;background:${complaint.color};transform:rotate(45deg);border:2px solid white;box-shadow:0 1px 5px rgba(0,0,0,.35)"></span>`,
                                iconSize: [size, size],
                                iconAnchor: [size / 2, size / 2],
                            });
                            L.marker([complaint.lat, complaint.lng], { icon })
                                .bindPopup(
                                    `<div class="text-sm"><p class="font-mono font-semibold">${complaint.reference_number}</p>`
                                    + `<p>${complaint.type_label} · ${complaint.status_label}</p>`
                                    + `<p class="text-xs text-gray-500">${complaint.priority_label}</p>`
                                    + `<a class="font-semibold text-brand" href="${complaint.url}">${this.labels.view_complaint}</a></div>`
                                )
                                .addTo(this.complaintLayer);
                        });
                    },
                    visibleTowers() {
                        return this.allTowers.filter((tower) => {
                            if (this.onlyFlagged && ! (tower.overdue || ['critical', 'needs_attention'].includes(tower.health_status))) {
                                return false;
                            }
                            if (this.onlyLicenseAlert) {
                                const state = tower.license?.status;
                                if (! ['expired', 'expiring_soon'].includes(state)) {
                                    return false;
                                }
                            }
                            return true;
                        });
                    },
                    draw(fit = false) {
                        const towers = this.visibleTowers();
                        this.circles.clearLayers();
                        this.markers.clearLayers();
                        this.selected = null;
                        this.count = towers.length;
                        const bounds = [];

                        towers.forEach((tower) => {
                            bounds.push([tower.lat, tower.lng]);
                            L.circle([tower.lat, tower.lng], {
                                pane: 'coverage',
                                radius: tower.signal_radius_m,
                                color: this.pinColor(tower),
                                fillColor: this.pinColor(tower),
                                fillOpacity: 0.12,
                                weight: 1,
                            }).addTo(this.circles);

                            const color = this.pinColor(tower);
                            const icon = L.divIcon({
                                className: '',
                                html: `<span style="display:block;width:16px;height:16px;border-radius:9999px;background:${color};border:2px solid white;box-shadow:0 1px 5px rgba(0,0,0,.3)"></span>`,
                                iconSize: [16, 16],
                                iconAnchor: [8, 8],
                            });

                            L.marker([tower.lat, tower.lng], { icon })
                                .on('click', () => { this.selected = tower; })
                                .addTo(this.markers);
                        });

                        this.map.invalidateSize();
                        if (! fit) {
                            return;
                        }
                        if (this.mapBounds) {
                            this.map.fitBounds(
                                [[this.mapBounds.south, this.mapBounds.west], [this.mapBounds.north, this.mapBounds.east]],
                                { padding: [40, 40], maxZoom: 12, animate: false },
                            );
                            return;
                        }
                        if (bounds.length) {
                            this.map.fitBounds(bounds, { padding: [40, 40], maxZoom: 12, animate: false });
                        } else {
                            this.map.setView([9.56, 44.06], 7);
                        }
                    },
                    printMap() {
                        this.filtersOpen = false;
                        this.$nextTick(() => {
                            this.map.invalidateSize();
                            window.print();
                        });
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
