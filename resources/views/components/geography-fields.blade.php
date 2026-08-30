@props([
    'regions',
    'initialDistricts' => [],
    'initialSubDistricts' => [],
    'initialCities' => [],
    'tower' => null,
    'regionId' => null,
    'districtId' => null,
    'subDistrictId' => null,
    'city' => null,
])

@php
    $regionId = old('region_id', $regionId ?? ($tower->region_id ?? null));
    $districtId = old('district_id', $districtId ?? ($tower->district_id ?? null));
    $subDistrictId = old('sub_district_id', $subDistrictId ?? ($tower->sub_district_id ?? null));
    $city = old('city', $city ?? ($tower->city ?? ''));
@endphp

<div
    class="contents"
    x-data="geographyCascade({
        districtsUrl: @js(route('geography.districts')),
        subDistrictsUrl: @js(route('geography.sub-districts')),
        citiesUrl: @js(route('geography.cities')),
        regionId: @js($regionId ? (string) $regionId : ''),
        districtId: @js($districtId ? (string) $districtId : ''),
        subDistrictId: @js($subDistrictId ? (string) $subDistrictId : ''),
        city: @js($city),
        districts: @js($initialDistricts),
        subDistricts: @js($initialSubDistricts),
        cities: @js($initialCities),
        labels: {
            district: @js(__('app.geography.select_district')),
            subDistrict: @js(__('app.geography.select_sub_district')),
            city: @js(__('app.towers.form_fields.select_city')),
        },
    })"
    x-init="init()"
>
    <div>
        <x-input-label for="region_id" :value="__('app.towers.region')" />
        <select id="region_id" name="region_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-brand focus:ring-brand" required x-model="regionId" @change="onRegionChange()">
            @foreach ($regions as $region)
                <option value="{{ $region->id }}">{{ $region->localizedName() }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('region_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="district_id" :value="__('app.towers.district')" />
        <select id="district_id" name="district_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-brand focus:ring-brand" x-model="districtId" @change="onDistrictChange()">
            <option value="" x-text="labels.district"></option>
            <template x-for="district in districts" :key="district.id">
                <option :value="district.id" x-text="district.name" :selected="district.id == districtId"></option>
            </template>
        </select>
        <x-input-error :messages="$errors->get('district_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="sub_district_id" :value="__('app.towers.sub_district')" />
        <select id="sub_district_id" name="sub_district_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-brand focus:ring-brand" x-model="subDistrictId">
            <option value="" x-text="labels.subDistrict"></option>
            <template x-for="subDistrict in subDistricts" :key="subDistrict.id">
                <option :value="subDistrict.id" x-text="subDistrict.name" :selected="subDistrict.id == subDistrictId"></option>
            </template>
        </select>
        <x-input-error :messages="$errors->get('sub_district_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="city" :value="__('app.towers.form_fields.city')" />
        <select id="city" name="city" class="mt-1 block w-full rounded-md border-gray-300 focus:border-brand focus:ring-brand" required x-model="city" @change="$dispatch('input', $event)">
            <option value="" x-text="labels.city"></option>
            <template x-for="cityName in cities" :key="cityName">
                <option :value="cityName" x-text="cityName"></option>
            </template>
        </select>
        <x-input-error :messages="$errors->get('city')" class="mt-1" />
    </div>
</div>

@once
    @push('scripts')
        <script>
            function geographyCascade(config) {
                return {
                    districtsUrl: config.districtsUrl,
                    subDistrictsUrl: config.subDistrictsUrl,
                    citiesUrl: config.citiesUrl,
                    regionId: config.regionId || '',
                    districtId: config.districtId || '',
                    subDistrictId: config.subDistrictId || '',
                    city: config.city || '',
                    districts: config.districts || [],
                    subDistricts: config.subDistricts || [],
                    cities: config.cities || [],
                    labels: config.labels,
                    init() {
                        if (this.regionId && ! this.districts.length) {
                            this.loadDistricts(false);
                        }
                        if (this.districtId && ! this.subDistricts.length) {
                            this.loadSubDistricts(false);
                        }
                        if (this.districtId && ! this.cities.length) {
                            this.loadCities(false);
                        }
                    },
                    async onRegionChange() {
                        this.districtId = '';
                        this.subDistrictId = '';
                        this.city = '';
                        this.subDistricts = [];
                        this.cities = [];
                        await this.loadDistricts(true);
                    },
                    async onDistrictChange() {
                        this.subDistrictId = '';
                        this.city = '';
                        await Promise.all([this.loadSubDistricts(true), this.loadCities(true)]);
                    },
                    async loadDistricts(clearSelection) {
                        if (! this.regionId) {
                            this.districts = [];
                            return;
                        }
                        const response = await fetch(`${this.districtsUrl}?region_id=${this.regionId}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const data = await response.json();
                        this.districts = data.districts || [];
                        if (clearSelection) {
                            this.districtId = '';
                        }
                    },
                    async loadSubDistricts(clearSelection) {
                        if (! this.districtId) {
                            this.subDistricts = [];
                            return;
                        }
                        const response = await fetch(`${this.subDistrictsUrl}?district_id=${this.districtId}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const data = await response.json();
                        this.subDistricts = data.sub_districts || [];
                        if (clearSelection) {
                            this.subDistrictId = '';
                        }
                    },
                    async loadCities(autoSelect) {
                        if (! this.districtId) {
                            this.cities = [];
                            return;
                        }
                        const response = await fetch(`${this.citiesUrl}?district_id=${this.districtId}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const data = await response.json();
                        this.cities = data.cities || [];
                        if (autoSelect && this.cities.length && ! this.city) {
                            this.city = this.cities[0];
                            document.getElementById('city')?.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    },
                };
            }
        </script>
    @endpush
@endonce
