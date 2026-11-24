@php
    $editing = $approval !== null;
    $value = fn (string $key, $default = null) => old($key, $values[$key] ?? $default);
    $regionDefault = $value('region_id', $regions->count() === 1 ? $regions->first()->id : null);
    $heightPresets = \App\Support\ExistingTowerRegistration::HEIGHT_PRESETS;
@endphp

<x-app-layout>
    <div class="p-4 lg:p-8 max-w-3xl">
        <a href="{{ $editing ? route('approvals.show', $approval) : route('towers.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            {{ $editing ? __('app.approvals.back_to_submission') : __('app.nav.towers') }}
        </a>

        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-brand">
            {{ $editing ? __('app.existing_towers.correct_title') : __('app.existing_towers.title') }}
        </h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('app.existing_towers.subtitle') }}</p>

        <form method="POST"
              action="{{ $editing ? route('approvals.existing.update', $approval) : route('towers.existing.store') }}"
              class="mt-5 space-y-5"
              x-data="{
                  regionId: @js((string) $regionDefault),
                  districtId: @js((string) $value('district_id', '')),
                  districts: @js($districts->map(fn ($d) => ['id' => (string) $d->id, 'region_id' => (string) $d->region_id, 'name' => $d->name])->values()),
                  height: @js((string) $value('height_m', '')),
                  get regionDistricts() { return this.districts.filter(d => d.region_id === this.regionId); },
              }">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            @if ($duplicates !== [])
                <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 sm:p-5">
                    <p class="text-sm font-semibold text-amber-900">{{ __('app.existing_towers.duplicate_title', ['meters' => \App\Support\ExistingTowerRegistration::DUPLICATE_RADIUS_METERS]) }}</p>
                    <ul class="mt-2 space-y-1 text-sm text-amber-900">
                        @foreach ($duplicates as $match)
                            <li>
                                @if ($match['url'])
                                    <a href="{{ $match['url'] }}" target="_blank" class="font-semibold underline">{{ $match['name'] }}</a>
                                @else
                                    <span class="font-semibold">{{ $match['name'] }}</span> <span class="text-amber-700">({{ __('app.existing_towers.waiting_confirmation') }})</span>
                                @endif
                                — {{ $match['operator'] }}, {{ __('app.existing_towers.meters_away', ['meters' => $match['distance_m']]) }}
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-sm text-amber-900">{{ __('app.existing_towers.duplicate_question') }}</p>
                    <label class="mt-2 flex items-start gap-2 text-sm font-medium text-amber-950">
                        <input type="checkbox" name="not_duplicate" value="1" class="mt-0.5 rounded border-amber-400 text-brand focus:ring-brand" @checked(old('not_duplicate'))>
                        <span>{{ __('app.existing_towers.not_duplicate') }}</span>
                    </label>
                    <x-input-error :messages="$errors->get('not_duplicate')" class="mt-1" />
                </div>
            @endif

            <section class="data-card p-4 sm:p-6 space-y-4">
                <div>
                    <x-input-label for="operator_id" :value="__('app.existing_towers.operator')" />
                    <select id="operator_id" name="operator_id" class="field mt-1 w-full" required>
                        <option value="">{{ __('app.existing_towers.choose_operator') }}</option>
                        @foreach ($operators as $operator)
                            <option value="{{ $operator->id }}" @selected((string) $value('operator_id') === (string) $operator->id)>{{ $operator->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('operator_id')" class="mt-1" />
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="region_id" :value="__('app.towers.region')" />
                        <select id="region_id" name="region_id" class="field mt-1 w-full" required x-model="regionId" @change="districtId = ''">
                            @if ($regions->count() !== 1)
                                <option value="">{{ __('app.existing_towers.choose_region') }}</option>
                            @endif
                            @foreach ($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->localizedName() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('region_id')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="district_id" :value="__('app.towers.district')" />
                        <select id="district_id" name="district_id" class="field mt-1 w-full" x-model="districtId">
                            <option value="">{{ __('app.existing_towers.district_from_location') }}</option>
                            <template x-for="district in regionDistricts" :key="district.id">
                                <option :value="district.id" x-text="district.name" :selected="district.id === districtId"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('district_id')" class="mt-1" />
                    </div>
                </div>
            </section>

            <section class="data-card p-4 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-brand">{{ __('app.existing_towers.location') }}</h2>
                        <p class="mt-0.5 text-xs text-gray-500">{{ __('app.existing_towers.location_hint') }}</p>
                    </div>
                    <button type="button" id="use-gps" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11Z"/>
                            <circle cx="12" cy="10" r="2.25" />
                        </svg>
                        <span data-gps-label>{{ __('app.towers.use_gps') }}</span>
                    </button>
                </div>
                <p id="gps-status" class="mt-2 text-sm text-gray-500 hidden"></p>
                <div id="picker-map" class="mt-3 h-64 sm:h-72 rounded-xl border border-gray-200 overflow-hidden"></div>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="latitude" :value="__('app.towers.latitude')" />
                        <x-text-input id="latitude" name="latitude" type="number" step="0.0000001" inputmode="decimal" class="mt-1 block w-full" :value="$value('latitude', 9.562)" required />
                        <x-input-error :messages="$errors->get('latitude')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="longitude" :value="__('app.towers.longitude')" />
                        <x-text-input id="longitude" name="longitude" type="number" step="0.0000001" inputmode="decimal" class="mt-1 block w-full" :value="$value('longitude', 44.077)" required />
                        <x-input-error :messages="$errors->get('longitude')" class="mt-1" />
                    </div>
                </div>
                <div class="mt-3 text-sm">
                    <p class="font-medium text-gray-700">{{ __('app.existing_towers.nearby_title') }}</p>
                    <div id="proximity-nearby-auto" class="mt-1"></div>
                </div>
            </section>

            <section class="data-card p-4 sm:p-6 space-y-5">
                <fieldset>
                    <legend class="text-sm font-medium text-gray-700">{{ __('app.existing_towers.type') }}</legend>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        @foreach (\App\Support\ExistingTowerRegistration::TYPES as $type)
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="{{ $type }}" class="peer sr-only" @checked($value('type') === $type) required>
                                <span class="block rounded-xl border border-gray-200 px-3 py-3 text-center text-sm font-semibold text-gray-700 peer-checked:border-brand peer-checked:bg-brand peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand">{{ __('app.status.'.$type) }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </fieldset>

                <div>
                    <x-input-label for="height_m" :value="__('app.existing_towers.height')" />
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($heightPresets as $preset)
                            <button type="button"
                                    @click="height = '{{ $preset }}'"
                                    :class="height === '{{ $preset }}' ? 'border-brand bg-brand text-white' : 'border-gray-200 text-gray-700'"
                                    class="rounded-xl border px-4 py-2 text-sm font-semibold">{{ $preset }} m</button>
                        @endforeach
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <input id="height_m" name="height_m" type="number" step="0.5" min="1" max="500" inputmode="decimal" x-model="height" class="field w-32" required>
                        <span class="text-sm text-gray-500">{{ __('app.existing_towers.height_hint') }}</span>
                    </div>
                    <x-input-error :messages="$errors->get('height_m')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="site_name" :value="__('app.existing_towers.site_name_optional')" />
                    <x-text-input id="site_name" name="site_name" type="text" class="mt-1 block w-full" :value="$value('site_name')" maxlength="255" />
                    <p class="mt-1 text-xs text-gray-500">{{ __('app.existing_towers.site_name_hint') }}</p>
                    <x-input-error :messages="$errors->get('site_name')" class="mt-1" />
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-3">
                <button class="inline-flex items-center px-6 py-3 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                    {{ $editing ? __('app.approvals.save_corrections') : __('app.existing_towers.submit') }}
                </button>
                @unless ($editing)
                    <p class="text-sm text-gray-500">{{ __('app.existing_towers.submit_hint') }}</p>
                @endunless
            </div>
        </form>
    </div>

    @include('towers._picker-script')
</x-app-layout>
