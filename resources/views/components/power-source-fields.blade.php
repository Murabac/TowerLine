@props([
    'tower' => null,
])

@php
    $selected = old('power_sources', $tower?->powerSourceKeys() ?? []);
@endphp

<fieldset class="lg:col-span-2">
    <legend class="text-sm font-medium text-gray-700">{{ __('app.towers.power_source') }}</legend>
    <p class="mt-1 text-xs text-gray-500">{{ __('app.towers.power_source_help') }}</p>
    <div class="mt-3 flex flex-wrap gap-3">
        @foreach (\App\Support\TowerPowerSource::OPTIONS as $source)
            <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm cursor-pointer hover:border-brand/40 has-[:checked]:border-brand has-[:checked]:bg-brand/5">
                <input
                    type="checkbox"
                    name="power_sources[]"
                    value="{{ $source }}"
                    class="rounded border-gray-300 text-brand focus:ring-brand"
                    @checked(in_array($source, $selected, true))
                >
                <span>{{ __('app.towers.power_sources.'.$source) }}</span>
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('power_sources')" class="mt-2" />
    <x-input-error :messages="$errors->get('power_sources.*')" class="mt-2" />
</fieldset>
