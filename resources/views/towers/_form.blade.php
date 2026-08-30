@php
    $tower = $tower ?? null;
    $landAreaPreset = old('land_area_preset', \App\Support\TowerLandArea::presetKeyForValue($tower?->land_area) ?: '20x20');
    $fencePreset = old('fence_distance_preset', \App\Support\TowerFenceDistance::presetKeyForValue($tower?->fence_distance_m));
    $defaultApplicationDate = old('application_date', $tower?->application_date?->toDateString() ?? now()->toDateString());
    $defaultSignalRadius = old('signal_radius_m', $tower?->signal_radius_m ?? \App\Support\TowerSignalRadius::defaultForCapacity(old('capacity', $tower?->capacity)));
@endphp

<div class="space-y-8">
    <section>
        <h2 class="text-sm font-semibold text-brand">{{ __('app.towers.sections.company_location') }}</h2>
        <p class="mt-1 text-xs text-gray-500">{{ __('app.towers.sections.company_location_hint') }}</p>
        <div class="mt-4 grid lg:grid-cols-2 gap-4">
            <div x-data="operatorRegionFilter({
                options: @js(($operatorOptions ?? $operators->map->toFormOption())->values()),
                selected: @js((string) old('operator_id', $tower?->operator_id ?? '')),
            })" x-init="init()" @change.window="onRegionChange($event)">
                <x-input-label for="operator_id" :value="__('app.towers.form_fields.company')" />
                <select id="operator_id" name="operator_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-brand focus:ring-brand" required x-model="selected">
                    <template x-for="operator in visibleOperators" :key="operator.id">
                        <option :value="operator.id" :data-name="operator.name" x-text="operator.display_name"></option>
                    </template>
                </select>
                <p class="mt-1 text-xs text-gray-500">{{ __('app.operators.region_filter_hint') }}</p>
                <x-input-error :messages="$errors->get('operator_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label :value="__('app.towers.name')" />
                <div
                    class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
                    x-data="towerNamePreview({
                        operators: @js($operators->map(fn ($op) => ['id' => $op->id, 'name' => $op->name])->values()),
                        initial: @js($tower?->name),
                        placeholder: @js(__('app.towers.name_auto_hint')),
                    })"
                    x-init="init()"
                    x-on:change.window="refreshFrom($event.target)"
                    x-on:input.window="refreshFrom($event.target)"
                >
                    <span class="font-medium text-brand" x-text="preview || initial || placeholder"></span>
                </div>
                <p class="mt-1 text-xs text-gray-500">{{ __('app.towers.name_format_hint') }}</p>
            </div>
            <x-geography-fields
                :regions="$regions"
                :initial-districts="$initialDistricts ?? []"
                :initial-sub-districts="$initialSubDistricts ?? []"
                :initial-cities="$initialCities ?? []"
                :tower="$tower"
            />
            <div>
                <x-input-label for="application_date" :value="__('app.towers.form_fields.application_date')" />
                <x-text-input id="application_date" name="application_date" type="date" class="mt-1 block w-full" :value="$defaultApplicationDate" />
                <p class="mt-1 text-xs text-gray-500">{{ __('app.towers.application_date_hint') }}</p>
                <x-input-error :messages="$errors->get('application_date')" class="mt-1" />
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-sm font-semibold text-brand">{{ __('app.towers.form_fields.gps') }}</h2>
        <p class="mt-1 text-xs text-gray-500">{{ __('app.towers.form_fields.gps_hint') }}</p>
        <div class="mt-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-600">{{ __('app.towers.pick_map') }}</p>
                <button type="button" id="use-gps" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11Z"/>
                        <circle cx="12" cy="10" r="2.25" />
                    </svg>
                    <span data-gps-label>{{ __('app.towers.use_gps') }}</span>
                </button>
            </div>
            <p id="gps-status" class="mt-2 text-sm text-gray-500 hidden"></p>
            <div class="mt-2 grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="latitude" :value="__('app.towers.latitude')" />
                    <x-text-input id="latitude" name="latitude" type="number" step="0.0000001" class="mt-1 block w-full" :value="old('latitude', $tower?->latitude ?? 9.562)" required />
                    <x-input-error :messages="$errors->get('latitude')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="longitude" :value="__('app.towers.longitude')" />
                    <x-text-input id="longitude" name="longitude" type="number" step="0.0000001" class="mt-1 block w-full" :value="old('longitude', $tower?->longitude ?? 44.077)" required />
                    <x-input-error :messages="$errors->get('longitude')" class="mt-1" />
                </div>
            </div>
            <div id="picker-map" class="mt-3 h-72 rounded-xl border border-gray-200 overflow-hidden"></div>
        </div>
    </section>

    <section>
        <h2 class="text-sm font-semibold text-brand">{{ __('app.towers.sections.site_measurements') }}</h2>
        <div class="mt-4 grid lg:grid-cols-2 gap-4" x-data="{ landPreset: @js($landAreaPreset) }">
            <div>
                <x-input-label for="land_area_preset" :value="__('app.towers.form_fields.land_area')" />
                <select id="land_area_preset" name="land_area_preset" class="mt-1 block w-full rounded-md border-gray-300 focus:border-brand focus:ring-brand" x-model="landPreset">
                    @foreach (\App\Support\TowerLandArea::PRESETS as $key => $label)
                        <option value="{{ $key }}" @selected($landAreaPreset === $key)>{{ $label }}</option>
                    @endforeach
                    <option value="custom" @selected($landAreaPreset === 'custom')>{{ __('app.towers.custom') }}</option>
                </select>
                <x-input-error :messages="$errors->get('land_area_preset')" class="mt-1" />
            </div>
            <div x-show="landPreset === 'custom'" x-cloak>
                <x-input-label for="land_area_custom" :value="__('app.towers.land_area_custom')" />
                <x-text-input id="land_area_custom" name="land_area_custom" class="mt-1 block w-full" :value="old('land_area_custom', $landAreaPreset === 'custom' ? $tower?->land_area : '')" />
                <x-input-error :messages="$errors->get('land_area_custom')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="height_m" :value="__('app.towers.form_fields.tower_height')" />
                <x-text-input id="height_m" name="height_m" type="number" step="0.1" min="1" class="mt-1 block w-full" :value="old('height_m', $tower?->height_m)" required />
                <x-input-error :messages="$errors->get('height_m')" class="mt-1" />
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-sm font-semibold text-brand">{{ __('app.towers.sections.proximity') }}</h2>
        <p class="mt-1 text-xs text-gray-500">{{ __('app.towers.sections.proximity_hint') }}</p>
        <div class="mt-4 grid lg:grid-cols-2 gap-4">
            @foreach ([
                'school' => ['name' => 'nearest_school_name', 'distance' => 'nearest_school_m', 'label' => __('app.towers.form_fields.nearest_school')],
                'hospital' => ['name' => 'nearest_hospital_name', 'distance' => 'nearest_hospital_m', 'label' => __('app.towers.form_fields.nearest_hospital')],
                'house' => ['name' => 'nearest_house_name', 'distance' => 'nearest_house_m', 'label' => __('app.towers.form_fields.nearest_house')],
            ] as $prefix => $fields)
                <div class="space-y-2">
                    <x-input-label :value="$fields['label']" />
                    <p class="text-xs text-gray-500">{{ __('app.towers.proximity_field_hint') }}</p>
                    <x-text-input
                        id="{{ $fields['name'] }}"
                        name="{{ $fields['name'] }}"
                        class="block w-full"
                        :value="old($fields['name'], $tower?->{$fields['name']})"
                        :placeholder="__('app.towers.proximity_name_placeholder')"
                    />
                    <x-text-input
                        id="{{ $fields['distance'] }}"
                        name="{{ $fields['distance'] }}"
                        type="number"
                        min="0"
                        class="block w-full"
                        :value="old($fields['distance'], $tower?->{$fields['distance']})"
                        :placeholder="__('app.towers.proximity_distance_placeholder')"
                    />
                    <p id="proximity-{{ $prefix }}-map-hint" class="hidden text-xs text-brand">{{ __('app.towers.proximity_from_map') }}</p>
                    <x-input-error :messages="$errors->get($fields['name'])" class="mt-1" />
                    <x-input-error :messages="$errors->get($fields['distance'])" class="mt-1" />
                </div>
            @endforeach
            <div x-data="{ fencePreset: @js($fencePreset) }">
                <x-input-label for="fence_distance_preset" :value="__('app.towers.form_fields.fence_distance_m')" />
                <select id="fence_distance_preset" name="fence_distance_preset" class="mt-1 block w-full rounded-md border-gray-300 focus:border-brand focus:ring-brand" x-model="fencePreset">
                    @foreach (\App\Support\TowerFenceDistance::PRESETS as $preset)
                        <option value="{{ $preset }}" @selected($fencePreset === (string) $preset)>{{ $preset }} m</option>
                    @endforeach
                    <option value="custom" @selected($fencePreset === 'custom')>{{ __('app.towers.custom') }}</option>
                </select>
                <div class="mt-2" x-show="fencePreset === 'custom'" x-cloak>
                    <x-text-input id="fence_distance_custom" name="fence_distance_custom" type="number" step="0.1" min="0" class="block w-full" :value="old('fence_distance_custom', $fencePreset === 'custom' ? $tower?->fence_distance_m : '')" />
                </div>
                <x-input-error :messages="$errors->get('fence_distance_preset')" class="mt-1" />
                <x-input-error :messages="$errors->get('fence_distance_custom')" class="mt-1" />
            </div>
            @php
                $formNearbyTowers = $tower?->nearbyTowers() ?? collect();
            @endphp
            <div class="lg:col-span-2">
                <x-input-label :value="__('app.towers.form_fields.other_towers_nearby')" />
                <p class="mt-1 text-xs text-gray-500">{{ __('app.towers.form_fields.other_towers_nearby_hint') }}</p>
                <div id="proximity-nearby-auto" class="mt-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                    @if ($formNearbyTowers->isNotEmpty())
                        <ul class="space-y-1">
                            @foreach ($formNearbyTowers as $item)
                                <li>
                                    <a href="{{ route('towers.show', $item['tower']) }}" class="text-brand hover:underline">{{ $item['tower']->name }}</a>
                                    <span class="text-gray-500">— {{ $item['tower']->operator->name }} ({{ number_format($item['distance_m']) }} m)</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p id="proximity-nearby-empty" class="text-gray-500">{{ __('app.towers.nearby.none', ['radius' => number_format(\App\Support\TowerProximity::NEARBY_RADIUS_METERS)]) }}</p>
                    @endif
                </div>
            </div>
            <div class="lg:col-span-2">
                <x-input-label for="site_map" :value="__('app.towers.form_fields.site_map_file')" />
                <input id="site_map" name="site_map" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-brand file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-dark" />
                @if ($tower?->site_map_path)
                    <p class="mt-2 text-sm">
                        <a href="{{ Storage::disk('public')->url($tower->site_map_path) }}" class="text-brand hover:underline" target="_blank" rel="noopener">{{ __('app.towers.view_site_map') }}</a>
                    </p>
                @endif
                <x-input-error :messages="$errors->get('site_map')" class="mt-1" />
            </div>
            <div class="lg:col-span-2">
                <x-input-label for="site_map_notes" :value="__('app.towers.form_fields.site_map_notes')" />
                <textarea id="site_map_notes" name="site_map_notes" rows="3" class="field mt-1 w-full" placeholder="{{ __('app.towers.form_fields.site_map_notes_hint') }}">{{ old('site_map_notes', $tower?->site_map_notes) }}</textarea>
                <x-input-error :messages="$errors->get('site_map_notes')" class="mt-1" />
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-sm font-semibold text-brand">{{ __('app.towers.sections.technical') }}</h2>
        <p class="mt-1 text-xs text-gray-500">{{ __('app.towers.sections.technical_hint') }}</p>
        <div class="mt-4 grid lg:grid-cols-2 gap-4">
            <div>
                <x-input-label for="type" :value="__('app.towers.type')" />
                <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 focus:border-brand focus:ring-brand" required>
                    @foreach (['guyed', 'monopole', 'rooftop'] as $type)
                        <option value="{{ $type }}" @selected(old('type', $tower?->type) === $type)>{{ __('app.status.'.$type) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="status" :value="__('app.towers.status')" />
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 focus:border-brand focus:ring-brand" required>
                    @foreach (['active', 'under_construction', 'decommissioned'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $tower?->status ?? 'active') === $status)>{{ __('app.status.'.$status) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="capacity" :value="__('app.towers.capacity')" />
                <select id="capacity" name="capacity" class="mt-1 block w-full rounded-md border-gray-300 focus:border-brand focus:ring-brand" required
                    @change="document.getElementById('signal_radius_m').value = (@js(\App\Support\TowerSignalRadius::BY_CAPACITY))[event.target.value] || document.getElementById('signal_radius_m').value">
                    <option value="" disabled @selected(! old('capacity', $tower?->capacity))>—</option>
                    @foreach (\App\Support\TowerCapacity::OPTIONS as $capacity)
                        <option value="{{ $capacity }}" @selected(old('capacity', $tower?->capacity) === $capacity)>{{ __('app.towers.capacities.'.$capacity) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('capacity')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="signal_radius_m" :value="__('app.towers.signal_radius')" />
                <x-text-input id="signal_radius_m" name="signal_radius_m" type="number" min="100" class="mt-1 block w-full" :value="$defaultSignalRadius" required />
                <p class="mt-1 text-xs text-gray-500">{{ __('app.towers.signal_radius_hint') }}</p>
                <x-input-error :messages="$errors->get('signal_radius_m')" class="mt-1" />
            </div>
            <div>
                <x-input-label :value="__('app.towers.commissioned')" />
                <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                    @if ($tower?->commissioned_at)
                        {{ $tower->commissioned_at->format('d M Y') }}
                    @else
                        {{ __('app.towers.commissioned_auto_hint') }}
                    @endif
                </div>
            </div>
            <x-power-source-fields :tower="$tower" />
        </div>
    </section>

    <section>
        <h2 class="text-sm font-semibold text-brand">{{ __('app.towers.sections.ministry_review') }}</h2>
        <p class="mt-1 text-xs text-gray-500">{{ __('app.towers.sections.ministry_review_hint') }}</p>
        <div class="mt-4 grid gap-4">
            <div>
                <x-input-label for="registration_inspector_notes" :value="__('app.towers.form_fields.inspector_notes')" />
                <textarea id="registration_inspector_notes" name="registration_inspector_notes" rows="3" class="field mt-1 w-full">{{ old('registration_inspector_notes', $tower?->registration_inspector_notes) }}</textarea>
                <x-input-error :messages="$errors->get('registration_inspector_notes')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="registration_director_notes" :value="__('app.towers.form_fields.director_notes')" />
                <textarea id="registration_director_notes" name="registration_director_notes" rows="3" class="field mt-1 w-full">{{ old('registration_director_notes', $tower?->registration_director_notes) }}</textarea>
                <x-input-error :messages="$errors->get('registration_director_notes')" class="mt-1" />
            </div>
        </div>
    </section>
</div>

@once
    @push('scripts')
        <script>
            function operatorRegionFilter(config) {
                return {
                    options: config.options || [],
                    selected: config.selected || '',
                    visibleOperators: [],
                    init() {
                        this.refresh();
                    },
                    onRegionChange(event) {
                        if (! event?.target || event.target.name !== 'region_id') {
                            return;
                        }

                        this.refresh();
                    },
                    refresh() {
                        const regionId = document.getElementById('region_id')?.value || '';
                        this.visibleOperators = this.options.filter((operator) => {
                            if (! regionId || operator.national) {
                                return true;
                            }

                            return (operator.region_ids || []).map(String).includes(String(regionId));
                        });

                        if (this.selected && ! this.visibleOperators.some((operator) => String(operator.id) === String(this.selected))) {
                            this.selected = this.visibleOperators[0]?.id ? String(this.visibleOperators[0].id) : '';
                        }
                    },
                };
            }

            function towerNamePreview(config) {
                return {
                    operators: config.operators || [],
                    initial: config.initial || '',
                    placeholder: config.placeholder || '',
                    preview: '',
                    init() {
                        this.refresh();
                    },
                    refreshFrom(target) {
                        if (! target || ! target.name) {
                            return;
                        }

                        if (! ['operator_id', 'city', 'district_id', 'sub_district_id'].includes(target.name)) {
                            return;
                        }

                        this.refresh();
                    },
                    refresh() {
                        const operatorSelect = document.getElementById('operator_id');
                        const citySelect = document.getElementById('city');
                        const districtSelect = document.getElementById('district_id');
                        const subDistrictSelect = document.getElementById('sub_district_id');

                        const operatorName = operatorSelect?.selectedOptions?.[0]?.dataset?.name
                            || operatorSelect?.selectedOptions?.[0]?.text?.trim()
                            || '';
                        const city = citySelect?.value?.trim() || '';
                        const districtName = districtSelect?.selectedOptions?.[0]?.text?.trim() || '';
                        const subDistrictName = subDistrictSelect?.selectedOptions?.[0]?.text?.trim() || '';
                        const location = city || this.cleanLabel(subDistrictName) || this.cleanLabel(districtName);

                        if (! operatorName || ! location) {
                            this.preview = '';
                            return;
                        }

                        this.preview = `${this.codeSegment(operatorName)}-${this.codeSegment(location)}-###`;
                    },
                    codeSegment(label) {
                        const normalized = (label || '').replace(/[^a-zA-Z]/g, '').toUpperCase();

                        if (! normalized) {
                            return 'UNK';
                        }

                        return (normalized.slice(0, 3) || 'UNK').padEnd(3, 'X');
                    },
                    cleanLabel(label) {
                        if (! label) {
                            return '';
                        }

                        const lower = label.toLowerCase();

                        if (lower.includes('select') || lower === '—') {
                            return '';
                        }

                        return label;
                    },
                };
            }
        </script>
    @endpush
@endonce
