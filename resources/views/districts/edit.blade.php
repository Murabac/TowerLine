<x-app-layout>
    <div
        class="p-4 lg:p-6"
        x-data="districtGeographyEditor(@js([
            'subDistricts' => $subDistrictPayload,
            'towers' => $towerPayload,
            'mapCenter' => $mapCenter,
            'selectedId' => request()->integer('sub_district') ?: ($subDistrictPayload->first()['id'] ?? null),
            'updateBase' => url('/districts/'.$district->id.'/sub-districts'),
            'deleteBase' => url('/districts/'.$district->id.'/sub-districts'),
            'labels' => [
                'draw_area' => __('app.geography.draw_area'),
                'draw_hint' => __('app.geography.draw_hint'),
                'clear_area' => __('app.geography.clear_area'),
                'area_set' => __('app.geography.area_set'),
                'area_not_set' => __('app.geography.area_not_set'),
                'cancel_draw' => __('app.geography.cancel_draw'),
                'draw_on_map' => __('app.geography.draw_on_map'),
                'draw_idle_hint' => __('app.geography.draw_idle_hint'),
                'draw_step_1' => __('app.geography.draw_step_1'),
                'draw_step_2' => __('app.geography.draw_step_2'),
                'saving_area' => __('app.geography.saving_area'),
            ],
        ]))"
        x-init="init()"
    >
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <a href="{{ route('districts.index', ['region_id' => $district->region_id]) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    {{ __('app.geography.title') }}
                </a>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-brand">{{ $district->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $district->region->localizedName() }} · {{ __('app.geography.manage_sub_districts') }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="space-y-4">
            <div class="data-card px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <p class="text-sm text-gray-600">
                        <span class="font-semibold text-brand">{{ __('app.geography.sub_district') }}</span>
                        <span class="hidden sm:inline text-gray-300 mx-1.5">·</span>
                        <span class="hidden sm:inline text-xs text-gray-500">{{ __('app.geography.pick_sub_district') }}</span>
                    </p>
                    <details class="relative">
                        <summary class="cursor-pointer list-none inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 bg-white hover:border-brand text-brand">
                            + {{ __('app.geography.add_sub_district') }}
                        </summary>
                        <form method="POST" action="{{ route('districts.sub-districts.store', $district) }}" class="absolute right-0 z-20 mt-2 w-64 rounded-xl border border-gray-200 bg-white p-3 shadow-lg space-y-2">
                            @csrf
                            <x-text-input name="name" class="block w-full text-sm" :placeholder="__('app.geography.district')" required />
                            <button class="w-full inline-flex items-center justify-center px-3 py-2 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand-dark">
                                {{ __('app.geography.add_sub_district') }}
                            </button>
                        </form>
                    </details>
                </div>
                <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
                    <template x-for="subDistrict in subDistricts" :key="subDistrict.id">
                        <button
                            type="button"
                            class="shrink-0 rounded-lg border px-3 py-2 text-left transition-colors min-w-[8.5rem] max-w-[11rem]"
                            :class="selectedId === subDistrict.id
                                ? 'border-brand bg-brand text-white shadow-md'
                                : 'border-gray-200 bg-white hover:border-brand/40'"
                            @click="selectSubDistrict(subDistrict.id)"
                        >
                            <p class="text-sm font-semibold truncate" :class="selectedId === subDistrict.id ? 'text-white' : 'text-gray-900'" x-text="subDistrict.label"></p>
                            <p
                                class="text-[11px] mt-0.5 truncate"
                                :class="selectedId === subDistrict.id
                                    ? 'text-white/80'
                                    : (subDistrict.has_area ? 'text-green-700' : 'text-amber-700')"
                                x-text="subDistrict.has_area ? labels.area_set : labels.area_not_set"
                            ></p>
                        </button>
                    </template>
                </div>
            </div>

            <details class="data-card group">
                    <summary class="cursor-pointer list-none px-4 py-3 flex items-center justify-between">
                        <span class="text-sm font-semibold text-brand">{{ __('app.geography.district_details') }}</span>
                        <span class="text-xs text-gray-400 group-open:hidden">{{ __('app.view') }}</span>
                    </summary>
                    <form method="POST" action="{{ route('districts.update', $district) }}" class="px-4 pb-4 grid sm:grid-cols-2 gap-3 border-t border-gray-100 pt-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <x-input-label for="name" :value="__('app.geography.district')" />
                            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $district->name)" required />
                        </div>
                        <div>
                            <x-input-label for="grade" :value="__('app.geography.grade')" />
                            <x-text-input id="grade" name="grade" class="mt-1 block w-full max-w-[6rem]" maxlength="1" :value="old('grade', $district->grade)" />
                        </div>
                        <div class="sm:col-span-3 flex flex-wrap items-center justify-between gap-3">
                            <button class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 text-sm font-semibold rounded-lg hover:border-brand">{{ __('app.save') }}</button>
                            @can('delete', $district)
                                <form method="POST" action="{{ route('districts.destroy', $district) }}" onsubmit="return confirm(@json(__('app.geography.confirm_delete_district')))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg border border-red-200 text-red-700 hover:bg-red-50">
                                        {{ __('app.delete') }} {{ __('app.geography.district') }}
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </form>
                </details>

            @if ($subDistrictPayload->isNotEmpty())
            <div class="data-card overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-brand" x-text="selected.label"></h2>
                                <p class="text-xs text-gray-500 mt-0.5" x-text="labels.draw_idle_hint"></p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold rounded-lg border"
                                    :class="drawing ? 'border-brand bg-brand text-white' : 'border-gray-200 bg-white hover:border-brand'"
                                    @click="toggleDrawing()"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4 20 7.5-7.5M14 4l6 6M9.5 9.5 4 4"/>
                                    </svg>
                                    <span x-text="drawing ? labels.cancel_draw : labels.draw_area"></span>
                                </button>
                                <button type="button" class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-lg border border-gray-200 bg-white hover:border-red-300 text-red-700" @click="clearArea()">
                                    <span x-text="labels.clear_area"></span>
                                </button>
                                @can('delete', $district)
                                    <form
                                        method="POST"
                                        :action="selected ? `${deleteBase}/${selected.id}` : '#'"
                                        onsubmit="return confirm(@json(__('app.geography.confirm_delete_sub_district')))"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-lg border border-red-200 bg-white text-red-700 hover:bg-red-50"
                                            :disabled="! selected"
                                        >
                                            {{ __('app.delete') }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>

                        <div class="relative">
                            <div
                                class="absolute inset-x-0 top-0 z-[500] px-4 py-2 text-sm font-medium text-center pointer-events-none"
                                :class="drawing ? 'bg-brand text-white' : 'bg-gray-800/75 text-white'"
                                x-text="drawStatusLabel"
                            ></div>
                            <div
                                id="district-area-map"
                                class="h-[28rem] min-h-[28rem] w-full bg-gray-100"
                                :class="drawing ? 'ring-2 ring-inset ring-brand cursor-crosshair' : ''"
                            ></div>
                        </div>

                        <form x-ref="areaForm" class="p-4 border-t border-gray-100 grid sm:grid-cols-2 gap-3" :action="formAction" method="POST" @submit="saving = true">
                            @csrf
                            @method('PUT')
                            <div class="sm:col-span-2">
                                <x-input-label :value="__('app.geography.sub_district')" />
                                <input type="text" name="name" class="field mt-1 w-full" x-model="form.name" required>
                            </div>
                            <input type="hidden" name="bounds_south" x-model="form.bounds_south">
                            <input type="hidden" name="bounds_west" x-model="form.bounds_west">
                            <input type="hidden" name="bounds_north" x-model="form.bounds_north">
                            <input type="hidden" name="bounds_east" x-model="form.bounds_east">
                            <input type="hidden" name="clear_area" :value="form.clear_area ? 1 : 0">
                            <div class="sm:col-span-2 flex items-center justify-between gap-3">
                                <p class="text-xs text-gray-500" x-show="form.bounds_south" x-cloak>
                                    <span x-text="`${Number(form.bounds_south).toFixed(4)}, ${Number(form.bounds_west).toFixed(4)} → ${Number(form.bounds_north).toFixed(4)}, ${Number(form.bounds_east).toFixed(4)}`"></span>
                                </p>
                                <p class="text-xs text-gray-400 ml-auto" x-show="! saving" x-cloak>{{ __('app.geography.area_auto_save') }}</p>
                                <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark disabled:opacity-60" :disabled="saving">
                                    <span x-text="saving ? labels.saving_area : @js(__('app.save'))"></span>
                                </button>
                            </div>
                        </form>
                    </div>
            @endif
        </div>
    </div>

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <style>
            [x-cloak]{display:none !important}
            #district-area-map { min-height: 28rem; z-index: 0; }
            #district-area-map .leaflet-container { height: 100% !important; width: 100% !important; }
            #district-area-map.drawing-active { cursor: crosshair !important; }
            #district-area-map.drawing-active .leaflet-container { cursor: crosshair !important; }
        </style>
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            function districtGeographyEditor(config) {
                return {
                    subDistricts: config.subDistricts || [],
                    towers: config.towers || [],
                    labels: config.labels || {},
                    deleteBase: config.deleteBase || '',
                    selectedId: config.selectedId || null,
                    map: null,
                    layer: null,
                    towerLayer: null,
                    previewLayer: null,
                    drawing: false,
                    saving: false,
                    drawStart: null,
                    form: {
                        name: '',
                        bounds_south: '',
                        bounds_west: '',
                        bounds_north: '',
                        bounds_east: '',
                        clear_area: false,
                    },
                    get selected() {
                        return this.subDistricts.find((item) => item.id === this.selectedId) || null;
                    },
                    get formAction() {
                        if (! this.selected) {
                            return '#';
                        }

                        return `${config.updateBase}/${this.selected.id}`;
                    },
                    get drawStatusLabel() {
                        if (this.saving) {
                            return this.labels.saving_area;
                        }

                        if (this.drawing && this.drawStart) {
                            return this.labels.draw_step_2;
                        }

                        if (this.drawing) {
                            return this.labels.draw_step_1;
                        }

                        return this.labels.draw_on_map;
                    },
                    init() {
                        this.$nextTick(() => {
                            this.$nextTick(() => this.ensureMap());
                        });

                        window.addEventListener('resize', () => {
                            if (this.map) {
                                this.map.invalidateSize();
                            }
                        });
                    },
                    ensureMap() {
                        const element = document.getElementById('district-area-map');

                        if (! element || ! this.selected) {
                            return;
                        }

                        if (! this.map) {
                            this.map = L.map(element, { zoomControl: true }).setView(
                                [config.mapCenter.lat, config.mapCenter.lng],
                                config.mapCenter.zoom,
                            );

                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; OpenStreetMap',
                                maxZoom: 19,
                            }).addTo(this.map);

                            this.layer = L.layerGroup().addTo(this.map);
                            this.towerLayer = L.layerGroup().addTo(this.map);
                            this.previewLayer = L.layerGroup().addTo(this.map);

                            this.towers.forEach((tower) => {
                                L.circleMarker([tower.lat, tower.lng], {
                                    radius: 5,
                                    color: '#0F766E',
                                    fillColor: '#0F766E',
                                    fillOpacity: 0.85,
                                    weight: 1,
                                }).bindTooltip(tower.name).addTo(this.towerLayer);
                            });

                            this.map.on('click', (event) => this.handleMapClick(event));
                            this.map.on('mousemove', (event) => this.handleMapMove(event));
                        }

                        if (this.selectedId) {
                            this.loadForm(this.selected);
                        }

                        this.renderAreas();
                        this.syncDrawingState();

                        this.$nextTick(() => {
                            this.map.invalidateSize();
                        });
                    },
                    selectSubDistrict(id) {
                        this.selectedId = id;
                        this.drawing = false;
                        this.drawStart = null;
                        this.previewLayer?.clearLayers();
                        this.syncDrawingState();
                        this.loadForm(this.selected);
                        this.$nextTick(() => {
                            this.ensureMap();
                        });
                    },
                    loadForm(subDistrict) {
                        if (! subDistrict) {
                            return;
                        }

                        this.form.name = subDistrict.name;
                        this.form.clear_area = false;

                        if (subDistrict.bounds) {
                            this.form.bounds_south = subDistrict.bounds.south;
                            this.form.bounds_west = subDistrict.bounds.west;
                            this.form.bounds_north = subDistrict.bounds.north;
                            this.form.bounds_east = subDistrict.bounds.east;
                        } else {
                            this.form.bounds_south = '';
                            this.form.bounds_west = '';
                            this.form.bounds_north = '';
                            this.form.bounds_east = '';
                        }
                    },
                    renderAreas() {
                        if (! this.map || ! this.layer) {
                            return;
                        }

                        this.layer.clearLayers();

                        this.subDistricts.forEach((subDistrict) => {
                            if (! subDistrict.bounds) {
                                return;
                            }

                            const bounds = [
                                [subDistrict.bounds.south, subDistrict.bounds.west],
                                [subDistrict.bounds.north, subDistrict.bounds.east],
                            ];
                            const isSelected = subDistrict.id === this.selectedId;

                            L.rectangle(bounds, {
                                color: isSelected ? '#0F766E' : '#94A3B8',
                                weight: isSelected ? 3 : 1,
                                fillColor: isSelected ? '#0F766E' : '#CBD5E1',
                                fillOpacity: isSelected ? 0.18 : 0.08,
                            }).addTo(this.layer);
                        });

                        if (this.selected?.bounds) {
                            this.map.fitBounds([
                                [this.selected.bounds.south, this.selected.bounds.west],
                                [this.selected.bounds.north, this.selected.bounds.east],
                            ], { padding: [24, 24] });
                        } else if (this.towers.length) {
                            this.map.fitBounds(this.towers.map((tower) => [tower.lat, tower.lng]), { padding: [24, 24], maxZoom: 13 });
                        }
                    },
                    toggleDrawing() {
                        this.drawing = ! this.drawing;
                        this.drawStart = null;
                        this.previewLayer?.clearLayers();
                        this.syncDrawingState();
                    },
                    syncDrawingState() {
                        const mapEl = document.getElementById('district-area-map');

                        if (! mapEl) {
                            return;
                        }

                        mapEl.classList.toggle('drawing-active', this.drawing);

                        if (this.map) {
                            this.map.getContainer().style.cursor = this.drawing ? 'crosshair' : '';
                        }
                    },
                    handleMapClick(event) {
                        if (! this.drawing || ! this.selected) {
                            return;
                        }

                        if (! this.drawStart) {
                            this.drawStart = event.latlng;
                            this.previewLayer.clearLayers();
                            L.circleMarker(this.drawStart, {
                                radius: 7,
                                color: '#ffffff',
                                fillColor: '#0F766E',
                                fillOpacity: 1,
                                weight: 3,
                            }).addTo(this.previewLayer);

                            return;
                        }

                        const bounds = L.latLngBounds(this.drawStart, event.latlng);
                        this.applyBounds(bounds);
                        this.drawing = false;
                        this.drawStart = null;
                        this.previewLayer.clearLayers();
                        this.syncDrawingState();
                    },
                    handleMapMove(event) {
                        if (! this.drawing || ! this.drawStart) {
                            return;
                        }

                        this.previewLayer.clearLayers();
                        L.rectangle(L.latLngBounds(this.drawStart, event.latlng), {
                            color: '#0F766E',
                            weight: 2,
                            dashArray: '6 4',
                            fillOpacity: 0.12,
                        }).addTo(this.previewLayer);
                    },
                    applyBounds(bounds) {
                        const south = bounds.getSouth();
                        const west = bounds.getWest();
                        const north = bounds.getNorth();
                        const east = bounds.getEast();

                        this.form.bounds_south = south;
                        this.form.bounds_west = west;
                        this.form.bounds_north = north;
                        this.form.bounds_east = east;
                        this.form.clear_area = false;

                        this.selected.bounds = { south, west, north, east };
                        this.selected.has_area = true;
                        this.renderAreas();
                        this.saveArea();
                    },
                    saveArea() {
                        this.$nextTick(() => {
                            this.$refs.areaForm?.requestSubmit();
                        });
                    },
                    clearArea() {
                        this.form.bounds_south = '';
                        this.form.bounds_west = '';
                        this.form.bounds_north = '';
                        this.form.bounds_east = '';
                        this.form.clear_area = true;

                        if (this.selected) {
                            this.selected.bounds = null;
                            this.selected.has_area = false;
                        }

                        this.renderAreas();
                        this.saveArea();
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
