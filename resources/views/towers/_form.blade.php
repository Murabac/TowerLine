@php
    $tower = $tower ?? null;
@endphp

<div class="grid lg:grid-cols-2 gap-4">
    <div>
        <x-input-label for="name" :value="__('app.towers.name')" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $tower?->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="operator_id" :value="__('app.towers.operator')" />
        <select id="operator_id" name="operator_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-brand focus:ring-brand" required>
            @foreach ($operators as $operator)
                <option value="{{ $operator->id }}" @selected(old('operator_id', $tower?->operator_id) == $operator->id)>{{ $operator->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('operator_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="region_id" :value="__('app.towers.region')" />
        <select id="region_id" name="region_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-brand focus:ring-brand" required>
            @foreach ($regions as $region)
                <option value="{{ $region->id }}" @selected(old('region_id', $tower?->region_id ?? auth()->user()->regionIds()[0] ?? null) == $region->id)>{{ $region->localizedName() }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('region_id')" class="mt-1" />
    </div>
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
        <x-input-label for="height_m" :value="__('app.towers.height')" />
        <x-text-input id="height_m" name="height_m" type="number" step="0.1" min="1" class="mt-1 block w-full" :value="old('height_m', $tower?->height_m)" required />
        <x-input-error :messages="$errors->get('height_m')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="capacity" :value="__('app.towers.capacity')" />
        <x-text-input id="capacity" name="capacity" class="mt-1 block w-full" :value="old('capacity', $tower?->capacity)" />
        <x-input-error :messages="$errors->get('capacity')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="signal_radius_m" :value="__('app.towers.signal_radius')" />
        <x-text-input id="signal_radius_m" name="signal_radius_m" type="number" min="100" class="mt-1 block w-full" :value="old('signal_radius_m', $tower?->signal_radius_m ?? 10000)" required />
        <x-input-error :messages="$errors->get('signal_radius_m')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="commissioned_at" :value="__('app.towers.commissioned')" />
        <x-text-input id="commissioned_at" name="commissioned_at" type="date" class="mt-1 block w-full" :value="old('commissioned_at', $tower?->commissioned_at?->toDateString())" />
        <x-input-error :messages="$errors->get('commissioned_at')" class="mt-1" />
    </div>
</div>

<div class="mt-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm font-medium text-gray-700">{{ __('app.towers.pick_map') }}</p>
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
