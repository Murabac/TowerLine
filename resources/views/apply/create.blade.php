<x-guidelines-layout>
    @php
        $control = '!mt-0 !block h-[3.25rem] !w-full !rounded-t-none !rounded-b-xl !border-2 !border-[#1B4D3E]/40 !bg-white !px-3.5 !text-[15px] !text-gray-900 !shadow-sm placeholder:!text-gray-500 focus:!border-brand focus:!bg-white focus:!ring-2 focus:!ring-brand/25';
        $invalid = '!border-red-500 focus:!border-red-500 focus:!ring-red-200';
        $label = '!block !w-full rounded-t-xl bg-gradient-to-r from-[#1B4D3E] via-[#246352] to-[#14382C] px-3.5 py-2 !text-[11px] font-semibold uppercase tracking-[0.12em] !text-white';
        $landAreaPreset = old('land_area_preset', '18x24');
        $fencePreset = old('fence_distance_preset', '6');
        $signalRadii = \App\Support\TowerSignalRadius::BY_CAPACITY;
        $fieldLabels = [
            'contact_name' => __('app.apply.contact_name', [], 'en'),
            'telephone' => __('app.apply.telephone', [], 'en'),
            'operator_id' => __('app.apply.operator', [], 'en'),
            'license_class_no' => __('app.apply.license_class_no', [], 'en'),
            'email' => __('app.apply.email', [], 'en'),
            'address' => __('app.apply.address', [], 'en'),
            'site_name' => __('app.apply.site_name', [], 'en'),
            'region_id' => __('app.apply.region', [], 'en'),
            'district_id' => __('app.apply.district', [], 'en'),
            'sub_district_id' => __('app.apply.sub_district', [], 'en'),
            'location' => __('app.towers.form_fields.gps', [], 'en'),
            'type' => __('app.towers.type', [], 'en'),
            'height_m' => __('app.towers.form_fields.tower_height', [], 'en'),
            'capacity' => __('app.towers.capacity', [], 'en'),
            'land_area_preset' => __('app.towers.form_fields.land_area', [], 'en'),
            'land_area_custom' => __('app.towers.land_area_custom', [], 'en'),
            'fence_distance_preset' => __('app.towers.form_fields.fence_distance_m', [], 'en'),
            'fence_distance_custom' => __('app.towers.form_fields.fence_distance_m', [], 'en'),
            'nearest_school_m' => __('app.towers.form_fields.nearest_school', [], 'en'),
            'nearest_hospital_m' => __('app.towers.form_fields.nearest_hospital', [], 'en'),
            'nearest_house_m' => __('app.towers.form_fields.nearest_house', [], 'en'),
            'letter' => __('app.apply.letter', [], 'en'),
            'layout' => __('app.apply.layout', [], 'en'),
            'radio' => __('app.apply.radio', [], 'en'),
            'icnirp' => __('app.apply.icnirp', [], 'en'),
        ];
        $fieldMessages = collect($errors->messages())->map(fn (array $messages) => $messages[0] ?? '');
        if ($fieldMessages->has('latitude') || $fieldMessages->has('longitude')) {
            $fieldMessages['location'] = $fieldMessages->get('latitude') ?: $fieldMessages->get('longitude');
        }
    @endphp

    <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-[#1B4D3E] via-[#246352] to-[#14382C] px-6 py-8 text-white shadow-[0_12px_40px_rgba(20,56,44,0.18)] sm:px-10 sm:py-10">
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[#F5C518]">{{ __('app.guidelines.window_label', [], 'en') }}</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('app.apply.title', [], 'en') }}</h1>
        <p class="mt-4 max-w-2xl text-sm leading-7 text-white/80 sm:text-[15px]">{{ __('app.apply.subtitle', [], 'en') }}</p>
        <a href="{{ route('guidelines') }}" class="mt-6 inline-flex text-sm font-medium text-[#F5C518] hover:underline">{{ __('app.apply.guidelines_link', [], 'en') }}</a>
    </section>

    <form method="POST" action="{{ route('apply.store') }}" enctype="multipart/form-data" class="mt-10 space-y-6" novalidate
        @submit="guardSubmit($event)"
        x-data="applyForm({
            tree: {{ Js::from($geography) }},
            operators: {{ Js::from($operators->map->toFormOption()->values()) }},
            operatorId: @js(old('operator_id', '')),
            regionId: @js(old('region_id', '')),
            districtId: @js(old('district_id', '')),
            subDistrictId: @js(old('sub_district_id', '')),
            landPreset: @js($landAreaPreset),
            fencePreset: @js($fencePreset),
            phoneLocal: @js(\App\Support\SomalilandPhone::localDigits(old('telephone'))),
            lat: @js(old('latitude', '')),
            lng: @js(old('longitude', '')),
            showBanner: @js($errors->any()),
            fieldMessages: {{ Js::from($fieldMessages) }},
            labels: {{ Js::from($fieldLabels) }},
            mins: {{ Js::from(\App\Support\SiteRegistrationGuidelines::distanceMins() + [
                'fence_distance_custom' => \App\Support\SiteRegistrationGuidelines::MIN_FENCE_M,
                'height_m' => \App\Support\SiteRegistrationGuidelines::MIN_HEIGHT_INHABITED_M,
            ]) }},
            plotShort: {{ \App\Support\SiteRegistrationGuidelines::MIN_PLOT_SHORT_M }},
            plotLong: {{ \App\Support\SiteRegistrationGuidelines::MIN_PLOT_LONG_M }},
            messages: {
                saving: @js(__('app.apply.location_saving', [], 'en')),
                saved: @js(__('app.apply.location_saved', [], 'en')),
                denied: @js(__('app.apply.location_denied', [], 'en')),
                unsupported: @js(__('app.apply.location_unsupported', [], 'en')),
                needed: @js(__('app.apply.location_needed', [], 'en')),
                required: @js(__('app.apply.required', [], 'en')),
                namedRequired: @js(__('app.apply.named_required', [], 'en')),
                telephoneInvalid: @js(__('app.apply.telephone_invalid', [], 'en')),
                email: @js(__('app.apply.email_invalid', [], 'en')),
                fileTypes: @js(__('app.apply.file_types', [], 'en')),
                fileTooLarge: @js(__('app.apply.file_too_large', [], 'en')),
                belowGuideline: @js(__('app.apply.below_guideline', [], 'en')),
                plotTooSmall: @js(__('app.apply.plot_too_small', [], 'en')),
                fix: @js(__('app.apply.fix_fields', [], 'en')),
            },
        })">
        @csrf

        <input type="hidden" name="latitude" x-model="lat">
        <input type="hidden" name="longitude" x-model="lng">

        <div x-cloak x-show="showBanner" x-ref="banner" class="overflow-hidden rounded-2xl border border-red-200 bg-red-50 shadow-sm">
            <div class="flex gap-4 p-5">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-600">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-4a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-red-900" x-text="messages.fix"></p>
                    <ul class="mt-2 space-y-1 text-sm text-red-800">
                        <template x-for="(message, field) in fieldMessages" :key="field">
                            <li x-show="message" class="flex gap-2">
                                <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-red-400"></span>
                                <span x-text="message"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        <section class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04),0_16px_40px_rgba(16,24,40,0.05)]">
            <div class="flex gap-4 border-b border-gray-100 px-6 py-6 sm:gap-6 sm:px-8">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand text-sm font-semibold text-white shadow-[inset_0_-1px_0_rgba(0,0,0,0.12)]">01</div>
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight text-gray-900">{{ __('app.apply.contact', [], 'en') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('app.apply.contact_lead', [], 'en') }}</p>
                </div>
            </div>
            <div class="grid gap-5 px-6 py-6 sm:grid-cols-2 sm:px-8 sm:py-8">
                <div>
                    <x-input-label for="contact_name" class="!block !w-full rounded-t-xl bg-gradient-to-r from-[#1B4D3E] via-[#246352] to-[#14382C] px-3.5 py-2 !text-[11px] font-semibold uppercase tracking-[0.12em] !text-white" :value="__('app.apply.contact_name', [], 'en')" />
                    <x-text-input id="contact_name" name="contact_name" class="{{ $control }}" x-bind:class="isInvalid('contact_name') && '{{ $invalid }}'" :value="old('contact_name')" :placeholder="__('app.apply.placeholder_name', [], 'en')" autocomplete="name" required x-on:input="clearField('contact_name')" />
                    <x-apply-field-error name="contact_name" />
                </div>
                <div>
                    <x-input-label for="telephone" class="!block !w-full rounded-t-xl bg-gradient-to-r from-[#1B4D3E] via-[#246352] to-[#14382C] px-3.5 py-2 !text-[11px] font-semibold uppercase tracking-[0.12em] !text-white" :value="__('app.apply.telephone', [], 'en')" />
                    <div class="flex overflow-hidden rounded-b-xl border-2 border-t-0 border-[#1B4D3E]/40 bg-white" :class="isInvalid('telephone') && 'border-red-500 ring-2 ring-red-200'">
                        <span class="inline-flex items-center bg-[#F4F8F6] px-3.5 text-[15px] font-semibold tabular-nums text-brand">+252</span>
                        <input id="telephone" type="text" inputmode="numeric" autocomplete="tel" maxlength="9" required
                            class="!mt-0 !block h-[3.25rem] min-w-0 flex-1 !border-0 !bg-white !px-3.5 !text-[15px] !text-gray-900 !shadow-none placeholder:!text-gray-500 focus:!ring-0"
                            placeholder="{{ __('app.apply.placeholder_telephone', [], 'en') }}"
                            x-model="phoneLocal"
                            x-on:keydown="blockNonInteger($event)"
                            x-on:input="phoneLocal = String(phoneLocal || '').replace(/\D/g, '').slice(0, 9); clearField('telephone')">
                    </div>
                    <input type="hidden" name="telephone" :value="'+252' + phoneLocal">
                    <p class="mt-1 text-xs text-gray-500">{{ __('app.apply.telephone_hint', [], 'en') }}</p>
                    <x-apply-field-error name="telephone" />
                </div>
                <div>
                    <x-input-label for="operator_id" class="{{ $label }}" :value="__('app.apply.operator', [], 'en')" />
                    <select id="operator_id" name="operator_id" class="{{ $control }}" :class="isInvalid('operator_id') && '{{ $invalid }}'" required x-model="operatorId" @change="clearField('operator_id')">
                        <option value="">{{ __('app.apply.select_operator', [], 'en') }}</option>
                        <template x-for="operator in operators" :key="operator.id">
                            <option :value="operator.id" x-text="operator.name"></option>
                        </template>
                    </select>
                    <x-apply-field-error name="operator_id" />
                </div>
                <div>
                    <x-input-label for="license_class_no" class="!block !w-full rounded-t-xl bg-gradient-to-r from-[#1B4D3E] via-[#246352] to-[#14382C] px-3.5 py-2 !text-[11px] font-semibold uppercase tracking-[0.12em] !text-white" :value="__('app.apply.license_class_no', [], 'en')" />
                    <x-text-input id="license_class_no" name="license_class_no" class="{{ $control }}" x-bind:class="isInvalid('license_class_no') && '{{ $invalid }}'" :value="old('license_class_no')" :placeholder="__('app.apply.placeholder_license', [], 'en')" required x-on:input="clearField('license_class_no')" />
                    <x-apply-field-error name="license_class_no" />
                </div>
                <div>
                    <x-input-label for="email" class="!block !w-full rounded-t-xl bg-gradient-to-r from-[#1B4D3E] via-[#246352] to-[#14382C] px-3.5 py-2 !text-[11px] font-semibold uppercase tracking-[0.12em] !text-white" :value="__('app.apply.email', [], 'en')" />
                    <x-text-input id="email" name="email" type="email" class="{{ $control }}" x-bind:class="isInvalid('email') && '{{ $invalid }}'" :value="old('email')" :placeholder="__('app.apply.placeholder_email', [], 'en')" autocomplete="email" required x-on:input="clearField('email')" />
                    <x-apply-field-error name="email" />
                </div>
                <div>
                    <x-input-label for="address" class="!block !w-full rounded-t-xl bg-gradient-to-r from-[#1B4D3E] via-[#246352] to-[#14382C] px-3.5 py-2 !text-[11px] font-semibold uppercase tracking-[0.12em] !text-white" :value="__('app.apply.address', [], 'en')" />
                    <x-text-input id="address" name="address" class="{{ $control }}" x-bind:class="isInvalid('address') && '{{ $invalid }}'" :value="old('address')" :placeholder="__('app.apply.placeholder_address', [], 'en')" autocomplete="street-address" required x-on:input="clearField('address')" />
                    <x-apply-field-error name="address" />
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04),0_16px_40px_rgba(16,24,40,0.05)]">
            <div class="flex gap-4 border-b border-gray-100 px-6 py-6 sm:gap-6 sm:px-8">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand text-sm font-semibold text-white shadow-[inset_0_-1px_0_rgba(0,0,0,0.12)]">02</div>
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight text-gray-900">{{ __('app.apply.site', [], 'en') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('app.apply.site_lead', [], 'en') }}</p>
                </div>
            </div>
            <div class="grid gap-5 px-6 py-6 sm:grid-cols-2 sm:px-8 sm:py-8">
                <div class="sm:col-span-2">
                    <x-input-label for="site_name" class="!block !w-full rounded-t-xl bg-gradient-to-r from-[#1B4D3E] via-[#246352] to-[#14382C] px-3.5 py-2 !text-[11px] font-semibold uppercase tracking-[0.12em] !text-white" :value="__('app.apply.site_name', [], 'en')" />
                    <x-text-input id="site_name" name="site_name" class="{{ $control }}" x-bind:class="isInvalid('site_name') && '{{ $invalid }}'" :value="old('site_name')" :placeholder="__('app.apply.placeholder_site', [], 'en')" required x-on:input="clearField('site_name')" />
                    <x-apply-field-error name="site_name" />
                </div>
                <div>
                    <x-input-label for="region_id" class="!block !w-full rounded-t-xl bg-gradient-to-r from-[#1B4D3E] via-[#246352] to-[#14382C] px-3.5 py-2 !text-[11px] font-semibold uppercase tracking-[0.12em] !text-white" :value="__('app.apply.region', [], 'en')" />
                    <select id="region_id" name="region_id" class="{{ $control }}" :class="isInvalid('region_id') && '{{ $invalid }}'" required x-model="regionId" @change="districtId = ''; subDistrictId = ''; clearField('region_id'); $nextTick(() => focusArea())">
                        <option value="">{{ __('app.apply.select_region', [], 'en') }}</option>
                        @foreach ($geography as $region)
                            <option value="{{ $region['id'] }}" @selected((string) old('region_id') === (string) $region['id'])>{{ $region['name'] }}</option>
                        @endforeach
                    </select>
                    <x-apply-field-error name="region_id" />
                </div>
                <div>
                    <x-input-label for="district_id" class="!block !w-full rounded-t-xl bg-gradient-to-r from-[#1B4D3E] via-[#246352] to-[#14382C] px-3.5 py-2 !text-[11px] font-semibold uppercase tracking-[0.12em] !text-white" :value="__('app.apply.district', [], 'en')" />
                    <select id="district_id" name="district_id" class="{{ $control }}" :class="isInvalid('district_id') && '{{ $invalid }}'" required x-model="districtId" @change="subDistrictId = ''; clearField('district_id'); $nextTick(() => focusArea())">
                        <option value="">{{ __('app.apply.select_district', [], 'en') }}</option>
                        <template x-for="district in districts" :key="district.id">
                            <option :value="district.id" x-text="district.name"></option>
                        </template>
                    </select>
                    <x-apply-field-error name="district_id" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="sub_district_id" class="!block !w-full rounded-t-xl bg-gradient-to-r from-[#1B4D3E] via-[#246352] to-[#14382C] px-3.5 py-2 !text-[11px] font-semibold uppercase tracking-[0.12em] !text-white" :value="__('app.apply.sub_district', [], 'en')" />
                    <select id="sub_district_id" name="sub_district_id" class="{{ $control }}" :class="isInvalid('sub_district_id') && '{{ $invalid }}'" required x-model="subDistrictId" @change="clearField('sub_district_id'); $nextTick(() => focusArea())">
                        <option value="">{{ __('app.apply.select_sub_district', [], 'en') }}</option>
                        <template x-for="sub in subDistricts" :key="sub.id">
                            <option :value="sub.id" x-text="sub.name"></option>
                        </template>
                    </select>
                    <x-apply-field-error name="sub_district_id" />
                </div>
                <div class="sm:col-span-2">
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-[#F7FAF8]" :class="isInvalid('location') && 'border-red-400 ring-2 ring-red-200'">
                        <div x-ref="map" class="h-80 w-full bg-[#dbe5df]"></div>
                        <div class="flex flex-col gap-3 border-t border-gray-200 bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <p class="text-sm leading-6 text-gray-600">{{ __('app.apply.pick_map', [], 'en') }}</p>
                            <div class="flex flex-wrap items-center gap-3">
                                <span x-cloak x-show="hasLocation && ! locating" class="inline-flex items-center gap-1.5 rounded-full bg-brand/10 px-3 py-1.5 text-xs font-semibold text-brand">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand"></span>
                                    {{ __('app.apply.location_saved', [], 'en') }}
                                </span>
                                <button type="button" @click="saveCurrentLocation()" :disabled="locating"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#F5C518] px-5 py-3 text-sm font-semibold text-brand-dark shadow-sm hover:bg-white disabled:cursor-wait disabled:opacity-70">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m13.5-6.5-2 2m-7 7-2 2m0-11 2 2m7 7 2 2M12 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8z"/>
                                    </svg>
                                    <span x-text="locating ? messages.saving : @js(__('app.apply.location_save', [], 'en'))"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <p x-cloak x-show="locationError && ! messageFor('location')" class="mt-2 flex items-start gap-2 rounded-lg bg-red-50 px-3 py-2 text-xs font-medium leading-5 text-red-800 ring-1 ring-inset ring-red-200" x-text="locationError"></p>
                    <x-apply-field-error name="location" />
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04),0_16px_40px_rgba(16,24,40,0.05)]">
            <div class="flex gap-4 border-b border-gray-100 px-6 py-6 sm:gap-6 sm:px-8">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand text-sm font-semibold text-white shadow-[inset_0_-1px_0_rgba(0,0,0,0.12)]">03</div>
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight text-gray-900">{{ __('app.apply.tower', [], 'en') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('app.apply.tower_lead', [], 'en') }}</p>
                </div>
            </div>
            <div class="grid gap-5 px-6 py-6 sm:grid-cols-2 sm:px-8 sm:py-8">
                <div>
                    <x-input-label for="type" class="{{ $label }}" :value="__('app.towers.type', [], 'en')" />
                    <select id="type" name="type" class="{{ $control }}" :class="isInvalid('type') && '{{ $invalid }}'" required @change="clearField('type')">
                        <option value="">{{ __('app.apply.select_type', [], 'en') }}</option>
                        @foreach (['guyed', 'monopole', 'rooftop'] as $type)
                            <option value="{{ $type }}" @selected(old('type') === $type)>{{ __('app.status.'.$type, [], 'en') }}</option>
                        @endforeach
                    </select>
                    <x-apply-field-error name="type" />
                </div>
                <div>
                    <x-input-label for="height_m" class="{{ $label }}" :value="__('app.towers.form_fields.tower_height', [], 'en')" />
                    <x-text-input id="height_m" name="height_m" type="number" inputmode="numeric" pattern="[0-9]*" step="1" min="1" class="{{ $control }}" x-bind:class="isInvalid('height_m') && '{{ $invalid }}'" :value="old('height_m')" required x-on:keydown="blockNonInteger($event)" x-on:input="sanitizeInteger($event); clearField('height_m')" x-on:paste="sanitizeInteger($event)" />
                    <p class="mt-1 text-xs text-gray-500">{{ __('app.apply.height_hint', [], 'en') }}</p>
                    <x-apply-field-error name="height_m" />
                </div>
                <div>
                    <x-input-label for="capacity" class="{{ $label }}" :value="__('app.towers.capacity', [], 'en')" />
                    <select id="capacity" name="capacity" class="{{ $control }}" :class="isInvalid('capacity') && '{{ $invalid }}'" required
                        @change="clearField('capacity'); document.getElementById('signal_radius_m').value = (@js($signalRadii))[$event.target.value] || document.getElementById('signal_radius_m').value">
                        <option value="">{{ __('app.apply.select_capacity', [], 'en') }}</option>
                        @foreach (\App\Support\TowerCapacity::OPTIONS as $capacity)
                            <option value="{{ $capacity }}" @selected(old('capacity') === $capacity)>{{ __('app.towers.capacities.'.$capacity, [], 'en') }}</option>
                        @endforeach
                    </select>
                    <x-apply-field-error name="capacity" />
                    <input type="hidden" id="signal_radius_m" name="signal_radius_m" value="{{ old('signal_radius_m', \App\Support\TowerSignalRadius::defaultForCapacity(old('capacity'))) }}">
                </div>
                <div>
                    <x-input-label for="land_area_preset" class="{{ $label }}" :value="__('app.towers.form_fields.land_area', [], 'en')" />
                    <select id="land_area_preset" name="land_area_preset" class="{{ $control }}" x-model="landPreset" @change="clearField('land_area_preset')">
                        @foreach (\App\Support\TowerLandArea::APPLY_PRESETS as $key => $text)
                            <option value="{{ $key }}">{{ $text }}</option>
                        @endforeach
                        <option value="custom">{{ __('app.towers.custom', [], 'en') }}</option>
                    </select>
                    <x-apply-field-error name="land_area_preset" />
                </div>
                <div x-show="landPreset === 'custom'" x-cloak class="sm:col-span-2">
                    <x-input-label for="land_area_custom" class="{{ $label }}" :value="__('app.towers.land_area_custom', [], 'en')" />
                    <x-text-input id="land_area_custom" name="land_area_custom" class="{{ $control }}" x-bind:class="isInvalid('land_area_custom') && '{{ $invalid }}'" :value="old('land_area_custom')" x-on:input="clearField('land_area_custom')" />
                    <x-apply-field-error name="land_area_custom" />
                </div>
                <div>
                    <x-input-label for="fence_distance_preset" class="{{ $label }}" :value="__('app.towers.form_fields.fence_distance_m', [], 'en')" />
                    <select id="fence_distance_preset" name="fence_distance_preset" class="{{ $control }}" x-model="fencePreset">
                        @foreach (\App\Support\TowerFenceDistance::PRESETS as $preset)
                            <option value="{{ $preset }}">{{ $preset }} m</option>
                        @endforeach
                        <option value="custom">{{ __('app.towers.custom', [], 'en') }}</option>
                    </select>
                    <x-apply-field-error name="fence_distance_preset" />
                </div>
                <div x-show="fencePreset === 'custom'" x-cloak>
                    <x-input-label for="fence_distance_custom" class="{{ $label }}" :value="__('app.towers.form_fields.fence_distance_m', [], 'en')" />
                    <x-text-input id="fence_distance_custom" name="fence_distance_custom" type="number" inputmode="numeric" pattern="[0-9]*" step="1" min="{{ \App\Support\SiteRegistrationGuidelines::MIN_FENCE_M }}" class="{{ $control }}" x-bind:class="isInvalid('fence_distance_custom') && '{{ $invalid }}'" :value="old('fence_distance_custom')" x-on:keydown="blockNonInteger($event)" x-on:input="sanitizeInteger($event); clearField('fence_distance_custom')" x-on:paste="sanitizeInteger($event)" />
                    <x-apply-field-error name="fence_distance_custom" />
                </div>
                <div class="sm:col-span-2">
                    <p class="{{ $label }}">{{ __('app.towers.power_source', [], 'en') }}</p>
                    <div class="rounded-b-xl border-2 border-t-0 border-[#1B4D3E]/40 bg-white px-4 py-4">
                        <p class="text-xs text-gray-500">{{ __('app.towers.power_source_help', [], 'en') }}</p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            @foreach (\App\Support\TowerPowerSource::OPTIONS as $source)
                                <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-[#F7FAF8] px-3 py-2 text-sm cursor-pointer hover:border-brand/40 has-[:checked]:border-brand has-[:checked]:bg-brand/5">
                                    <input type="checkbox" name="power_sources[]" value="{{ $source }}" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(in_array($source, old('power_sources', []), true))>
                                    <span>{{ __('app.towers.power_sources.'.$source, [], 'en') }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <x-apply-field-error name="power_sources" />
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04),0_16px_40px_rgba(16,24,40,0.05)]">
            <div class="flex gap-4 border-b border-gray-100 px-6 py-6 sm:gap-6 sm:px-8">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand text-sm font-semibold text-white shadow-[inset_0_-1px_0_rgba(0,0,0,0.12)]">04</div>
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight text-gray-900">{{ __('app.apply.proximity', [], 'en') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('app.apply.proximity_lead', [], 'en') }}</p>
                </div>
            </div>
            <div class="grid gap-5 px-6 py-6 sm:grid-cols-2 sm:px-8 sm:py-8">
                @foreach ([
                    ['school', 'nearest_school_name', 'nearest_school_m', __('app.towers.form_fields.nearest_school', [], 'en'), \App\Support\SiteRegistrationGuidelines::MIN_SENSITIVE_M],
                    ['hospital', 'nearest_hospital_name', 'nearest_hospital_m', __('app.towers.form_fields.nearest_hospital', [], 'en'), \App\Support\SiteRegistrationGuidelines::MIN_SENSITIVE_M],
                    ['house', 'nearest_house_name', 'nearest_house_m', __('app.towers.form_fields.nearest_house', [], 'en'), \App\Support\SiteRegistrationGuidelines::MIN_PUBLIC_M],
                ] as [$key, $nameField, $distanceField, $proximityLabel, $minMetres])
                    <div>
                        <x-input-label for="{{ $nameField }}" class="{{ $label }}" :value="$proximityLabel" />
                        <x-text-input id="{{ $nameField }}" name="{{ $nameField }}" class="{{ $control }}" x-bind:class="isInvalid('{{ $nameField }}') && '{{ $invalid }}'" :value="old($nameField)" :placeholder="__('app.towers.proximity_name_placeholder', [], 'en')" x-on:input="clearField('{{ $nameField }}')" />
                        <x-apply-field-error name="{{ $nameField }}" />
                        <x-text-input id="{{ $distanceField }}" name="{{ $distanceField }}" type="number" inputmode="numeric" pattern="[0-9]*" step="1" min="{{ $minMetres }}" class="{{ $control }} !rounded-xl !mt-2" x-bind:class="isInvalid('{{ $distanceField }}') && '{{ $invalid }}'" :value="old($distanceField)" :placeholder="__('app.towers.proximity_distance_placeholder', [], 'en')" x-on:keydown="blockNonInteger($event)" x-on:input="sanitizeInteger($event); clearField('{{ $distanceField }}')" x-on:paste="sanitizeInteger($event)" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('app.apply.min_metres', ['min' => $minMetres], 'en') }}</p>
                        <x-apply-field-error name="{{ $distanceField }}" />
                    </div>
                @endforeach
                <div class="sm:col-span-2">
                    <x-input-label for="site_map_notes" class="{{ $label }}" :value="__('app.towers.form_fields.site_map_notes', [], 'en')" />
                    <textarea id="site_map_notes" name="site_map_notes" rows="3" class="!mt-0 !block w-full !rounded-t-none !rounded-b-xl !border-2 !border-[#1B4D3E]/40 !bg-white !px-3.5 !py-3 !text-[15px] !text-gray-900 focus:!border-brand focus:!ring-2 focus:!ring-brand/25" placeholder="{{ __('app.towers.form_fields.site_map_notes_hint', [], 'en') }}">{{ old('site_map_notes') }}</textarea>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04),0_16px_40px_rgba(16,24,40,0.05)]">
            <div class="flex gap-4 border-b border-gray-100 px-6 py-6 sm:gap-6 sm:px-8">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand text-sm font-semibold text-white shadow-[inset_0_-1px_0_rgba(0,0,0,0.12)]">05</div>
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight text-gray-900">{{ __('app.apply.documents', [], 'en') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('app.apply.documents_hint', [], 'en') }}</p>
                </div>
            </div>
            <div class="grid gap-3 px-6 py-6 sm:px-8 sm:py-8">
                @foreach (['letter', 'layout', 'radio', 'icnirp'] as $i => $doc)
                    <label for="{{ $doc }}" class="group flex cursor-pointer items-start gap-4 rounded-2xl border border-gray-200 bg-[#F7FAF8] px-4 py-4 transition hover:border-brand/40 hover:bg-white sm:px-5" :class="isInvalid('{{ $doc }}') && 'border-red-400 bg-red-50 ring-1 ring-red-200'">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-xs font-semibold text-brand ring-1 ring-brand/15">{{ sprintf('%02d', $i + 1) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-gray-900">{{ __('app.apply.'.$doc, [], 'en') }}</span>
                            <span class="mt-0.5 block text-xs text-gray-500">{{ __('app.apply.file_types_short', [], 'en') }}</span>
                            <span class="mt-2 block truncate text-sm font-medium text-brand" x-show="fileNames.{{ $doc }}" x-text="fileNames.{{ $doc }}"></span>
                            <x-apply-field-error name="{{ $doc }}" />
                        </span>
                        <span class="shrink-0 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-brand ring-1 ring-brand/15 group-hover:bg-brand group-hover:text-white">Upload</span>
                        <input id="{{ $doc }}" name="{{ $doc }}" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png" required class="sr-only" x-on:change="onFileChange('{{ $doc }}', $event)">
                    </label>
                @endforeach
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-brand/10 bg-white">
            <div class="flex">
                <div class="w-1.5 shrink-0 bg-[#F5C518]"></div>
                <div class="flex flex-1 flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <p class="text-sm leading-6 text-gray-600">{{ __('app.guidelines.compulsory', [], 'en') }}</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('guidelines') }}" class="text-sm font-medium text-brand hover:underline">{{ __('app.apply.guidelines_link', [], 'en') }}</a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand px-6 py-3.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-dark">{{ __('app.apply.submit', [], 'en') }}</button>
                    </div>
                </div>
            </div>
        </section>
    </form>

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            function applyForm(config) {
                return {
                    tree: config.tree || [],
                    operators: config.operators || [],
                    operatorId: String(config.operatorId || ''),
                    regionId: String(config.regionId || ''),
                    districtId: String(config.districtId || ''),
                    subDistrictId: String(config.subDistrictId || ''),
                    phoneLocal: String(config.phoneLocal || ''),
                    landPreset: config.landPreset || '18x24',
                    fencePreset: config.fencePreset || '6',
                    lat: config.lat ? String(config.lat) : '',
                    lng: config.lng ? String(config.lng) : '',
                    locating: false,
                    locationError: '',
                    showBanner: Boolean(config.showBanner),
                    fieldMessages: config.fieldMessages || {},
                    labels: config.labels || {},
                    mins: config.mins || {},
                    plotShort: Number(config.plotShort || 18),
                    plotLong: Number(config.plotLong || 24),
                    fileNames: { letter: '', layout: '', radio: '', icnirp: '' },
                    messages: config.messages,
                    map: null,
                    marker: null,
                    get districts() {
                        const region = this.tree.find((item) => String(item.id) === String(this.regionId));
                        return region ? region.districts : [];
                    },
                    get subDistricts() {
                        const district = this.districts.find((item) => String(item.id) === String(this.districtId));
                        return district ? district.sub_districts : [];
                    },
                    get hasLocation() {
                        return this.lat !== '' && this.lng !== '';
                    },
                    isInvalid(name) {
                        return Boolean(this.fieldMessages[name]);
                    },
                    labelFor(name) {
                        return this.labels[name] || name;
                    },
                    requiredFor(name) {
                        return (this.messages.namedRequired || ':field is required.').replace(':field', this.labelFor(name));
                    },
                    namedMessage(template, name) {
                        return (template || '').replace(':field', this.labelFor(name));
                    },
                    blockNonInteger(event) {
                        if (event.ctrlKey || event.metaKey || event.altKey) {
                            return;
                        }
                        if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) {
                            return;
                        }
                        if (/^\d$/.test(event.key)) {
                            return;
                        }
                        event.preventDefault();
                    },
                    sanitizeInteger(event) {
                        const el = event.target;
                        const next = String(el.value || '').replace(/\D/g, '');
                        if (el.value !== next) {
                            el.value = next;
                        }
                    },
                    belowGuidelineMessage(name, min) {
                        return (this.messages.belowGuideline || ':field cannot be below :min m (guideline minimum).')
                            .replace(':field', this.labelFor(name))
                            .replace(':min', String(min));
                    },
                    plotMeetsGuideline() {
                        const text = this.landPreset === 'custom'
                            ? String(this.$root.querySelector('[name="land_area_custom"]')?.value || '')
                            : String(this.landPreset || '');
                        const match = text.match(/(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)/i);
                        if (! match) {
                            return this.landPreset !== 'custom';
                        }
                        const a = Number(match[1]);
                        const b = Number(match[2]);
                        return Math.min(a, b) + 0.001 >= this.plotShort && Math.max(a, b) + 0.001 >= this.plotLong;
                    },
                    fileProblem(file) {
                        const ext = String(file.name || '').split('.').pop().toLowerCase();
                        if (! ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'].includes(ext)) {
                            return 'type';
                        }
                        const mime = String(file.type || '').toLowerCase();
                        const allowedMimes = [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-word',
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'application/octet-stream',
                        ];
                        if (mime && ! allowedMimes.includes(mime)) {
                            return 'type';
                        }
                        if (file.size > 10 * 1024 * 1024) {
                            return 'size';
                        }
                        return '';
                    },
                    fileMessage(name, problem) {
                        if (problem === 'size') {
                            return this.namedMessage(this.messages.fileTooLarge, name);
                        }
                        return this.namedMessage(this.messages.fileTypes, name);
                    },
                    setFieldMessage(name, message) {
                        this.fieldMessages = { ...this.fieldMessages, [name]: message };
                        this.showBanner = true;
                    },
                    onFileChange(name, event) {
                        const input = event.target;
                        const file = input.files && input.files[0];
                        if (! file) {
                            this.fileNames[name] = '';
                            return;
                        }
                        const problem = this.fileProblem(file);
                        if (problem) {
                            input.value = '';
                            this.fileNames[name] = '';
                            this.setFieldMessage(name, this.fileMessage(name, problem));
                            return;
                        }
                        this.fileNames[name] = file.name;
                        this.clearField(name);
                    },
                    messageFor(name) {
                        return this.fieldMessages[name] || '';
                    },
                    clearField(name) {
                        if (! this.fieldMessages[name]) {
                            return;
                        }
                        const next = { ...this.fieldMessages };
                        delete next[name];
                        this.fieldMessages = next;
                        this.showBanner = Object.keys(this.fieldMessages).length > 0;
                    },
                    init() {
                        this.$nextTick(() => this.initMap());
                    },
                    leafletBounds(bounds) {
                        if (! bounds || bounds.south == null || bounds.west == null || bounds.north == null || bounds.east == null) {
                            return null;
                        }
                        return [[bounds.south, bounds.west], [bounds.north, bounds.east]];
                    },
                    selectedBounds() {
                        const sub = this.subDistricts.find((item) => String(item.id) === String(this.subDistrictId));
                        if (sub && sub.bounds) {
                            return { bounds: sub.bounds, maxZoom: 14 };
                        }
                        const district = this.districts.find((item) => String(item.id) === String(this.districtId));
                        if (district && district.bounds) {
                            return { bounds: district.bounds, maxZoom: 12 };
                        }
                        const region = this.tree.find((item) => String(item.id) === String(this.regionId));
                        if (region && region.bounds) {
                            return { bounds: region.bounds, maxZoom: 9 };
                        }
                        return null;
                    },
                    focusArea() {
                        if (! this.map) {
                            return;
                        }
                        const selection = this.selectedBounds();
                        const box = selection ? this.leafletBounds(selection.bounds) : null;
                        if (! box) {
                            return;
                        }
                        this.map.fitBounds(box, {
                            padding: [28, 28],
                            maxZoom: selection.maxZoom,
                            animate: true,
                        });
                        setTimeout(() => this.map.invalidateSize(), 150);
                    },
                    initMap() {
                        if (typeof L === 'undefined' || ! this.$refs.map) {
                            return;
                        }
                        const start = this.hasLocation
                            ? [parseFloat(this.lat), parseFloat(this.lng)]
                            : [9.56, 44.07];
                        this.map = L.map(this.$refs.map).setView(start, this.hasLocation ? 15 : 7);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap',
                        }).addTo(this.map);
                        if (this.hasLocation) {
                            this.placeMarker(parseFloat(this.lat), parseFloat(this.lng));
                        } else {
                            this.focusArea();
                        }
                        this.map.on('click', (event) => {
                            this.setLocation(event.latlng.lat, event.latlng.lng);
                        });
                        setTimeout(() => this.map.invalidateSize(), 200);
                    },
                    placeMarker(lat, lng) {
                        if (this.marker) {
                            this.marker.setLatLng([lat, lng]);
                            return;
                        }
                        this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                        this.marker.on('dragend', () => {
                            const point = this.marker.getLatLng();
                            this.setLocation(point.lat, point.lng, false);
                        });
                    },
                    setLocation(lat, lng, fly = true) {
                        this.lat = Number(lat).toFixed(7);
                        this.lng = Number(lng).toFixed(7);
                        this.locationError = '';
                        this.clearField('location');
                        this.placeMarker(Number(this.lat), Number(this.lng));
                        if (fly && this.map) {
                            this.map.setView([Number(this.lat), Number(this.lng)], 16);
                        }
                    },
                    saveCurrentLocation() {
                        if (! navigator.geolocation) {
                            this.locationError = this.messages.unsupported;
                            return;
                        }
                        this.locating = true;
                        this.locationError = '';
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                this.locating = false;
                                this.setLocation(position.coords.latitude, position.coords.longitude);
                            },
                            () => {
                                this.locating = false;
                                this.locationError = this.messages.denied;
                            },
                            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                        );
                    },
                    validate() {
                        const next = {};
                        const required = [
                            'contact_name', 'telephone', 'operator_id', 'license_class_no', 'email', 'address',
                            'site_name', 'region_id', 'district_id', 'sub_district_id',
                            'type', 'height_m', 'capacity',
                        ];
                        required.forEach((name) => {
                            if (name === 'telephone') {
                                return;
                            }
                            const el = this.$root.querySelector(`[name="${name}"]`);
                            if (! el || String(el.value || '').trim() === '') {
                                next[name] = this.requiredFor(name);
                            }
                        });
                        if (! /^\d{9}$/.test(String(this.phoneLocal || ''))) {
                            next.telephone = this.messages.telephoneInvalid || this.requiredFor('telephone');
                        }
                        const email = this.$root.querySelector('[name="email"]');
                        if (email && email.value && ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                            next.email = `${this.labelFor('email')}: ${this.messages.email}`;
                        }
                        ['letter', 'layout', 'radio', 'icnirp'].forEach((name) => {
                            const el = this.$root.querySelector(`[name="${name}"]`);
                            const file = el?.files?.[0];
                            if (! file) {
                                next[name] = this.requiredFor(name);
                                return;
                            }
                            const problem = this.fileProblem(file);
                            if (problem) {
                                next[name] = this.fileMessage(name, problem);
                            }
                        });
                        Object.keys(this.mins).forEach((name) => {
                            if (name === 'fence_distance_custom' && this.fencePreset !== 'custom') {
                                return;
                            }
                            if (name === 'height_m') {
                                const type = this.$root.querySelector('[name="type"]')?.value;
                                if (type === 'rooftop') {
                                    return;
                                }
                            }
                            const el = this.$root.querySelector(`[name="${name}"]`);
                            const raw = el ? String(el.value || '').trim() : '';
                            if (raw === '') {
                                return;
                            }
                            const min = Number(this.mins[name]);
                            if (Number(raw) < min) {
                                next[name] = this.belowGuidelineMessage(name, min);
                            }
                        });
                        if (this.landPreset && ! this.plotMeetsGuideline()) {
                            const field = this.landPreset === 'custom' ? 'land_area_custom' : 'land_area_preset';
                            next[field] = (this.messages.plotTooSmall || 'Land area cannot be below :short × :long m (guideline minimum).')
                                .replace(':short', String(this.plotShort))
                                .replace(':long', String(this.plotLong));
                        }
                        if (! this.hasLocation) {
                            next.location = `${this.labelFor('location')}: ${this.messages.needed}`;
                            this.locationError = this.messages.needed;
                        }
                        this.fieldMessages = next;
                        this.showBanner = Object.keys(next).length > 0;
                        return ! this.showBanner;
                    },
                    guardSubmit(event) {
                        if (this.validate()) {
                            return;
                        }
                        event.preventDefault();
                        this.$nextTick(() => this.$refs.banner?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
                    },
                };
            }
        </script>
    @endpush
</x-guidelines-layout>
