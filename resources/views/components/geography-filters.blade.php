@props([
    'regions',
    'initialDistricts' => [],
    'initialSubDistricts' => [],
    'regionId' => null,
    'districtId' => null,
    'subDistrictId' => null,
])

<div
    class="contents"
    x-data="geographyCascade({
        districtsUrl: @js(route('geography.districts')),
        subDistrictsUrl: @js(route('geography.sub-districts')),
        regionId: @js($regionId ? (string) $regionId : (request('region_id') ? (string) request('region_id') : '')),
        districtId: @js($districtId ? (string) $districtId : (request('district_id') ? (string) request('district_id') : '')),
        subDistrictId: @js($subDistrictId ? (string) $subDistrictId : (request('sub_district_id') ? (string) request('sub_district_id') : '')),
        districts: @js($initialDistricts),
        subDistricts: @js($initialSubDistricts),
        labels: {
            district: @js(__('app.geography.select_district')),
            subDistrict: @js(__('app.geography.select_sub_district')),
        },
    })"
    x-init="init()"
>
    <select name="region_id" class="field lg:w-44" x-model="regionId" @change="onRegionChange()">
        <option value="">{{ __('app.towers.region') }}</option>
        @foreach ($regions as $region)
            <option value="{{ $region->id }}">{{ $region->localizedName() }}</option>
        @endforeach
    </select>
    <select name="district_id" class="field lg:w-44" x-model="districtId" @change="onDistrictChange()">
        <option value="" x-text="labels.district"></option>
        <template x-for="district in districts" :key="district.id">
            <option :value="district.id" x-text="district.name"></option>
        </template>
    </select>
    <select name="sub_district_id" class="field lg:w-44" x-model="subDistrictId">
        <option value="" x-text="labels.subDistrict"></option>
        <template x-for="subDistrict in subDistricts" :key="subDistrict.id">
            <option :value="subDistrict.id" x-text="subDistrict.name"></option>
        </template>
    </select>
</div>

@once
    @push('scripts')
        <script>
            function geographyCascade(config) {
                return {
                    districtsUrl: config.districtsUrl,
                    subDistrictsUrl: config.subDistrictsUrl,
                    regionId: config.regionId || '',
                    districtId: config.districtId || '',
                    subDistrictId: config.subDistrictId || '',
                    districts: config.districts || [],
                    subDistricts: config.subDistricts || [],
                    labels: config.labels,
                    init() {
                        if (this.regionId && ! this.districts.length) {
                            this.loadDistricts(false);
                        }
                        if (this.districtId && ! this.subDistricts.length) {
                            this.loadSubDistricts(false);
                        }
                    },
                    async onRegionChange() {
                        this.districtId = '';
                        this.subDistrictId = '';
                        this.subDistricts = [];
                        await this.loadDistricts(true);
                    },
                    async onDistrictChange() {
                        this.subDistrictId = '';
                        await this.loadSubDistricts(true);
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
                };
            }
        </script>
    @endpush
@endonce
