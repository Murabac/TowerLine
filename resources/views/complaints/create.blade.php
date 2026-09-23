<x-app-layout>
    <div class="p-4 lg:p-6 max-w-5xl"
         x-data="complaintIntake(@js([
            'towersUrl' => $towersUrl,
            'selectedArea' => __('app.complaints.selected_area'),
            'clearSelection' => __('app.complaints.clear_selection'),
         ]))"
         x-init="init()">
        <a href="{{ route('complaints.index') }}" class="text-sm text-brand hover:underline">{{ __('app.back') }}</a>
        <h1 class="mt-2 text-2xl font-semibold text-brand">{{ __('app.complaints.intake') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('app.complaints.intake_hint') }}</p>

        @php
            $field = 'mt-1 field border border-gray-300 bg-white';
        @endphp

        <form method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf

            <section class="data-card overflow-hidden">
                <x-section-bar :title="__('app.complaints.section_report')" />
                <div class="bg-[#F4F8F6] p-4 lg:p-6 grid lg:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="complaint_type" :value="__('app.complaints.type')" />
                        <select id="complaint_type" name="complaint_type" class="{{ $field }}" required>
                            @foreach (\App\Models\Complaint::TYPES as $type)
                                <option value="{{ $type }}" @selected(old('complaint_type') === $type)>{{ __('app.complaints.types.'.$type) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('complaint_type')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="priority" :value="__('app.complaints.priority_label')" />
                        <select id="priority" name="priority" class="{{ $field }}">
                            @foreach (\App\Models\Complaint::PRIORITIES as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', 'medium') === $priority)>{{ __('app.complaints.priority.'.$priority) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <x-input-label for="description" :value="__('app.complaints.description')" />
                        <textarea id="description" name="description" rows="5" class="{{ $field }}" required>{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>
                    <div class="lg:col-span-2">
                        <x-input-label for="photo" :value="__('app.complaints.photo')" />
                        <input id="photo" name="photo" type="file" accept="image/*" class="{{ $field }} px-3 py-2">
                        <p class="mt-1 text-xs text-gray-500">{{ __('app.optional') }}</p>
                    </div>
                </div>
            </section>

            <section class="data-card overflow-hidden">
                <x-section-bar :title="__('app.complaints.section_caller')" />
                <div class="bg-[#F4F8F6] p-4 lg:p-6 grid lg:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="submitter_name" :value="__('app.complaints.submitter_name')" />
                        <x-text-input id="submitter_name" name="submitter_name" class="{{ $field }}" :value="old('submitter_name')" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('app.optional') }}</p>
                    </div>
                    <div>
                        <x-input-label for="submitter_phone" :value="__('app.complaints.submitter_phone')" />
                        <x-text-input id="submitter_phone" name="submitter_phone" class="{{ $field }}" :value="old('submitter_phone')" placeholder="+252" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('app.complaints.phone_hint') }}</p>
                        <x-input-error :messages="$errors->get('submitter_phone')" class="mt-1" />
                    </div>
                </div>
            </section>

            <section class="data-card overflow-hidden">
                <x-section-bar :title="__('app.complaints.map_pin')" :hint="__('app.complaints.map_pin_hint')" />
                <div class="bg-[#F4F8F6] p-4 lg:p-6 space-y-4">
                    <div>
                        <x-input-label for="region_id" :value="__('app.towers.region')" />
                        <select id="region_id" name="region_id" class="{{ $field }}" x-model="regionId" :required="! towerId">
                            <option value="">{{ __('app.towers.region') }}</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region->id }}" @selected((string) old('region_id') === (string) $region->id)>{{ $region->localizedName() }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('app.complaints.region_hint') }}</p>
                        <x-input-error :messages="$errors->get('region_id')" class="mt-1" />
                    </div>

                    <input type="hidden" name="tower_id" x-model="towerId">
                    <input type="hidden" name="latitude" x-model="lat">
                    <input type="hidden" name="longitude" x-model="lng">
                    <x-input-error :messages="$errors->get('latitude')" class="mt-1" />

                    <div id="complaint-intake-map" class="h-[28rem] rounded-lg border border-gray-300 overflow-hidden bg-white"></div>
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2" x-show="towerId || (lat && lng)" x-cloak>
                        <p class="text-sm text-gray-800" x-text="selectionLabel"></p>
                        <button type="button" class="text-xs font-semibold text-brand hover:underline" @click="clearSelection()" x-text="labels.clearSelection"></button>
                    </div>
                </div>
            </section>

            <div class="flex gap-3">
                <x-primary-button>{{ __('app.complaints.submit') }}</x-primary-button>
                <a href="{{ route('complaints.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:underline">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </div>
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
        <script>
            function complaintIntake(config) {
                return {
                    towersUrl: config.towersUrl,
                    labels: {
                        selectedArea: config.selectedArea,
                        clearSelection: config.clearSelection,
                    },
                    regionId: @js((string) old('region_id', '')),
                    towerId: @js((string) old('tower_id', '')),
                    lat: @js(old('latitude')),
                    lng: @js(old('longitude')),
                    towers: [],
                    map: null,
                    markers: null,
                    pin: null,
                    get selectionLabel() {
                        const tower = this.towers.find((item) => String(item.id) === String(this.towerId));
                        if (tower) {
                            const operator = tower.operator?.name || tower.operator || '';
                            const region = tower.region || '';
                            return [tower.name, operator, region].filter(Boolean).join(' · ');
                        }
                        if (this.lat && this.lng) {
                            return `${this.labels.selectedArea} · ${this.lat}, ${this.lng}`;
                        }
                        return '';
                    },
                    init() {
                        this.map = L.map('complaint-intake-map').setView([9.56, 44.06], 7);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap',
                            maxZoom: 19,
                        }).addTo(this.map);
                        this.markers = L.markerClusterGroup({
                            showCoverageOnHover: false,
                            maxClusterRadius: 48,
                            disableClusteringAtZoom: 12,
                        });
                        this.map.addLayer(this.markers);
                        this.map.on('click', (event) => {
                            this.pickArea(event.latlng.lat, event.latlng.lng);
                        });
                        this.loadTowers();
                        this.$nextTick(() => this.map.invalidateSize());
                    },
                    async loadTowers() {
                        const response = await fetch(this.towersUrl, { headers: { Accept: 'application/json' } });
                        const data = await response.json();
                        this.towers = data.towers || [];
                        this.drawTowers(data.bounds || null);
                        if (this.towerId) {
                            const tower = this.towers.find((item) => String(item.id) === String(this.towerId));
                            if (tower) {
                                this.pickTower(tower, true);
                                return;
                            }
                        }
                        if (this.lat && this.lng && ! this.towerId) {
                            this.setPin(Number(this.lat), Number(this.lng), true);
                        }
                    },
                    drawTowers(bounds) {
                        this.markers.clearLayers();
                        this.towers.forEach((tower) => {
                            const selected = String(this.towerId) === String(tower.id);
                            const color = tower.operator?.color || tower.color || '#0F766E';
                            const size = selected ? 22 : 16;
                            const icon = L.divIcon({
                                className: '',
                                html: `<span style="display:block;width:${size}px;height:${size}px;border-radius:9999px;background:${color};border:${selected ? '3px' : '2px'} solid ${selected ? '#F5C518' : 'white'};box-shadow:0 1px 5px rgba(0,0,0,.3)"></span>`,
                                iconSize: [size, size],
                                iconAnchor: [size / 2, size / 2],
                            });
                            L.marker([tower.lat, tower.lng], { icon })
                                .on('click', (event) => {
                                    L.DomEvent.stopPropagation(event);
                                    this.pickTower(tower, false);
                                })
                                .addTo(this.markers);
                        });
                        if (this.towerId || (this.lat && this.lng)) {
                            return;
                        }
                        if (bounds) {
                            this.map.fitBounds(
                                [[bounds.south, bounds.west], [bounds.north, bounds.east]],
                                { padding: [40, 40], maxZoom: 12, animate: false },
                            );
                            return;
                        }
                        if (this.towers.length) {
                            this.map.fitBounds(
                                this.towers.map((tower) => [tower.lat, tower.lng]),
                                { padding: [40, 40], maxZoom: 12, animate: false },
                            );
                        }
                    },
                    pickTower(tower, fly) {
                        this.towerId = String(tower.id);
                        if (tower.region_id) {
                            this.regionId = String(tower.region_id);
                        }
                        this.lat = Number(tower.lat).toFixed(6);
                        this.lng = Number(tower.lng).toFixed(6);
                        this.clearPin();
                        this.drawTowers(null);
                        if (fly) {
                            this.map.setView([tower.lat, tower.lng], 13);
                        }
                    },
                    pickArea(lat, lng) {
                        this.towerId = '';
                        this.setPin(lat, lng, false);
                        this.drawTowers(null);
                    },
                    setPin(lat, lng, fly) {
                        this.lat = Number(lat).toFixed(6);
                        this.lng = Number(lng).toFixed(6);
                        if (this.pin) {
                            this.pin.setLatLng([lat, lng]);
                        } else {
                            this.pin = L.marker([lat, lng]).addTo(this.map);
                        }
                        if (fly) {
                            this.map.setView([lat, lng], 13);
                        }
                    },
                    clearPin() {
                        if (this.pin) {
                            this.map.removeLayer(this.pin);
                            this.pin = null;
                        }
                    },
                    clearSelection() {
                        this.towerId = '';
                        this.lat = null;
                        this.lng = null;
                        this.clearPin();
                        this.drawTowers(null);
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
