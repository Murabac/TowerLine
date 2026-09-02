<x-app-layout>
    @php
        $photoSlots = ['wide', 'base', 'power', 'condition'];
        $powerChoices = [
            'on_grid' => 'on_grid',
            'generator' => 'generator_power',
            'battery' => 'battery',
            'down' => 'down',
        ];
        $staleDays = \App\Models\Tower::INSPECTION_STALE_DAYS;
        $defaults = $defaults ?? [];
        $existingPhotos = $existingPhotos ?? [];
        $formAction = $formAction ?? route('towers.inspections.store', $tower);
        $formMethod = $formMethod ?? 'POST';
        $submitLabel = $submitLabel ?? __('app.inspections.create');
        $cancelUrl = $cancelUrl ?? route('towers.show', $tower);
        $isCorrection = $formMethod === 'PUT';
    @endphp

    <div
        class="p-4 lg:p-8 max-w-3xl"
        x-data="{
            files: { wide: [], base: [], power: [], condition: [] },
            max: 8,
            add(slot, list) {
                for (const file of list) {
                    if (this.files[slot].length >= this.max) break;
                    if (file.type && ! file.type.startsWith('image/')) continue;
                    this.files[slot].push({ file, url: URL.createObjectURL(file) });
                }
                this.sync(slot);
            },
            remove(slot, index) {
                URL.revokeObjectURL(this.files[slot][index].url);
                this.files[slot].splice(index, 1);
                this.sync(slot);
            },
            sync(slot) {
                const dt = new DataTransfer();
                this.files[slot].forEach((item) => dt.items.add(item.file));
                this.$refs['input_' + slot].files = dt.files;
            },
        }"
    >
        <a href="{{ $cancelUrl }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            {{ $tower->name }}
        </a>

        <div class="mt-4 rounded-2xl bg-brand text-white px-5 py-5 sm:px-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-light">{{ __('app.inspections.title') }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $isCorrection ? __('app.approvals.correct_this') : __('app.inspections.create') }}</h1>
            <p class="mt-2 text-sm text-white/75 max-w-xl">{{ __('app.inspections.subtitle') }}</p>
            <p class="mt-3 text-sm text-white/90 max-w-xl">{{ __('app.inspections.lenient_intro') }}</p>
            <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1">
                    <span class="h-2 w-2 rounded-full" style="background: {{ $tower->operator->color }}"></span>
                    {{ $tower->operator->name }}
                </span>
                <span class="rounded-full bg-white/10 px-2.5 py-1">{{ $tower->region->localizedName() }}</span>
            </div>
        </div>

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="mt-5 space-y-5">
            @csrf
            @if ($formMethod === 'PUT')
                @method('PUT')
            @endif
            @if ($isCorrection)
                <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">{{ __('app.approvals.correct_hint') }}</p>
            @endif

            <section class="data-card p-5 sm:p-6 space-y-4 border-l-4 border-brand">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-brand-muted">1 · {{ __('app.inspections.step_comment') }}</p>
                    <h2 class="mt-1 text-base font-semibold text-gray-900">{{ __('app.inspections.comment') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('app.inspections.comment_help') }}</p>
                </div>
                <div>
                    <textarea id="notes" name="notes" rows="5" class="field min-h-[8rem]" placeholder="{{ __('app.inspections.comment_placeholder') }}">{{ old('notes', $defaults['notes'] ?? '') }}</textarea>
                    @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="data-card p-5 sm:p-6 space-y-6">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-brand-muted">2 · {{ __('app.inspections.step_status') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ __('app.inspections.optional_fields_help') }}</p>
                </div>

                <fieldset>
                    <legend class="text-sm font-semibold text-gray-900">
                        {{ __('app.inspections.power') }}
                        <span class="ml-1 text-xs font-medium text-gray-400">({{ __('app.optional') }})</span>
                    </legend>
                    <p class="mt-1 text-sm text-gray-500">{{ __('app.inspections.power_help') }}</p>
                    @error('power_status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div class="mt-3 grid sm:grid-cols-2 gap-2.5">
                        @foreach ($powerChoices as $value => $label)
                            <label class="group relative flex cursor-pointer gap-3 rounded-2xl border border-gray-200 bg-white p-4 transition has-[:checked]:border-brand has-[:checked]:bg-[#F4F8F6] has-[:checked]:shadow-[inset_0_0_0_1px_#1B4D3E]">
                                <input type="radio" name="power_status" value="{{ $value }}" class="mt-1 text-brand focus:ring-brand/30" @checked(old('power_status', $defaults['power_status'] ?? null) === $value)>
                                <span>
                                    <span class="block text-sm font-semibold text-gray-900">{{ __('app.inspections.'.$label) }}</span>
                                    <span class="mt-0.5 block text-xs text-gray-500">{{ __('app.inspections.choice.'.$value) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="text-sm font-semibold text-gray-900">
                        {{ __('app.inspections.generator') }}
                        <span class="ml-1 text-xs font-medium text-gray-400">({{ __('app.optional') }})</span>
                    </legend>
                    <p class="mt-1 text-sm text-gray-500">{{ __('app.inspections.generator_help') }}</p>
                    @error('generator_condition') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach (['good', 'fair', 'poor', 'n_a'] as $value)
                            <label class="cursor-pointer rounded-2xl border border-gray-200 bg-white px-3 py-3 text-center text-sm font-semibold text-gray-800 transition has-[:checked]:border-brand has-[:checked]:bg-brand has-[:checked]:text-white">
                                <input type="radio" name="generator_condition" value="{{ $value }}" class="sr-only" @checked(old('generator_condition', $defaults['generator_condition'] ?? null) === $value)>
                                {{ __('app.inspections.'.$value) }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="text-sm font-semibold text-gray-900">
                        {{ __('app.inspections.physical') }}
                        <span class="ml-1 text-xs font-medium text-gray-400">({{ __('app.optional') }})</span>
                    </legend>
                    <p class="mt-1 text-sm text-gray-500">{{ __('app.inspections.physical_help') }}</p>
                    @error('physical_condition') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        @foreach (['good', 'fair', 'poor'] as $value)
                            <label class="cursor-pointer rounded-2xl border border-gray-200 bg-white px-3 py-3 text-center text-sm font-semibold text-gray-800 transition has-[:checked]:border-brand has-[:checked]:bg-brand has-[:checked]:text-white">
                                <input type="radio" name="physical_condition" value="{{ $value }}" class="sr-only" @checked(old('physical_condition', $defaults['physical_condition'] ?? null) === $value)>
                                {{ __('app.inspections.'.$value) }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </section>

            <section class="data-card p-5 sm:p-6 space-y-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-brand-muted">3 · {{ __('app.inspections.step_photos') }}</p>
                    <h2 class="mt-1 text-base font-semibold text-gray-900">
                        {{ __('app.inspections.photos') }}
                        <span class="ml-1 text-xs font-medium text-gray-400">({{ __('app.optional') }})</span>
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('app.inspections.photos_intro') }}</p>
                </div>
                @error('photos') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <div class="grid sm:grid-cols-2 gap-4">
                    @foreach ($photoSlots as $slot)
                        @php
                            $hint = public_path('images/inspection-hints/'.$slot.'.png');
                            $hintUrl = file_exists($hint) ? asset('images/inspection-hints/'.$slot.'.png') : null;
                        @endphp
                        <div class="block">
                            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-100">
                                <div class="relative aspect-[4/3]">
                                    @if ($hintUrl)
                                        <img src="{{ $hintUrl }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <div class="absolute inset-0 flex items-center justify-center text-gray-400">
                                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
                                        </div>
                                    @endif
                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3 pt-10">
                                        <p class="text-sm font-semibold text-white">{{ __('app.inspections.photo_slots.'.$slot.'.title') }}</p>
                                        <p class="mt-0.5 text-[11px] leading-snug text-white/80">{{ __('app.inspections.photo_slots.'.$slot.'.how') }}</p>
                                    </div>
                                </div>
                            </div>

                            @if (! empty($existingPhotos[$slot]))
                                <div class="mt-2 grid grid-cols-4 gap-1.5">
                                    @foreach ($existingPhotos[$slot] as $path)
                                        <a href="{{ asset('storage/'.$path) }}" target="_blank" class="block h-16 overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                                            <img src="{{ asset('storage/'.$path) }}" alt="" class="h-full w-full object-cover">
                                        </a>
                                    @endforeach
                                </div>
                                <p class="mt-1 text-xs text-gray-500">{{ __('app.approvals.photos_replace_hint') }}</p>
                            @endif

                            <div class="mt-2 grid grid-cols-4 gap-1.5" x-show="files.{{ $slot }}.length" x-cloak>
                                <template x-for="(item, index) in files.{{ $slot }}" :key="item.url">
                                    <div class="relative h-16 w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                                        <img :src="item.url" alt="" class="h-full w-full object-cover">
                                        <button
                                            type="button"
                                            class="absolute right-1 top-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-black/70 text-xs text-white"
                                            @click="remove('{{ $slot }}', index)"
                                            :aria-label="@js(__('app.inspections.photo_remove'))"
                                        >×</button>
                                    </div>
                                </template>
                            </div>

                            <button
                                type="button"
                                class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-brand disabled:text-gray-400"
                                :disabled="files.{{ $slot }}.length >= max"
                                @click="$refs.picker_{{ $slot }}.click()"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
                                <span x-text="files.{{ $slot }}.length ? @js(__('app.inspections.photo_add_more')) : @js(__('app.inspections.photo_add'))"></span>
                                <span class="font-normal text-gray-500" x-show="files.{{ $slot }}.length" x-text="'(' + files.{{ $slot }}.length + '/' + max + ')'"></span>
                            </button>
                            <input
                                type="file"
                                accept="image/*"
                                multiple
                                class="sr-only"
                                x-ref="picker_{{ $slot }}"
                                @change="add('{{ $slot }}', $event.target.files); $event.target.value = ''"
                            >
                            <input
                                type="file"
                                name="photos[{{ $slot }}][]"
                                accept="image/*"
                                multiple
                                class="sr-only"
                                x-ref="input_{{ $slot }}"
                                :disabled="files.{{ $slot }}.length === 0"
                                tabindex="-1"
                            >
                            @error('photos.'.$slot) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('photos.'.$slot.'.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <p class="text-xs text-gray-500">{{ __('app.inspections.stale_hint', ['days' => $staleDays]) }}</p>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="{{ $cancelUrl }}" class="inline-flex items-center justify-center px-4 py-3 text-sm font-semibold text-gray-600">{{ __('app.cancel') }}</a>
                <button class="inline-flex items-center justify-center px-5 py-3 bg-brand text-white text-sm font-semibold rounded-2xl hover:bg-brand-dark shadow-sm">{{ $submitLabel }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
