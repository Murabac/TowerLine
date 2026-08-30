@php
    $allocation = $allocation ?? null;
@endphp

<div
    class="space-y-6"
    x-data="{
        issued: @js(old('issued_at', $allocation?->issued_at?->format('Y-m-d') ?? now()->toDateString())),
        expires: @js(old('expires_at', $allocation?->expires_at?->format('Y-m-d') ?? now()->addYear()->toDateString())),
        existing: @js($allocation?->documentList() ?? []),
        pending: [],
        remove: [],
        max: 10,
        remaining() {
            return this.max - this.existing.length - this.pending.length;
        },
        add(list) {
            for (const file of list) {
                if (this.remaining() <= 0) break;
                this.pending.push({ file, name: file.name, url: URL.createObjectURL(file) });
            }
            this.sync();
        },
        dropPending(index) {
            URL.revokeObjectURL(this.pending[index].url);
            this.pending.splice(index, 1);
            this.sync();
        },
        dropExisting(path) {
            this.remove.push(path);
            this.existing = this.existing.filter((doc) => doc.path !== path);
        },
        sync() {
            const dt = new DataTransfer();
            this.pending.forEach((item) => dt.items.add(item.file));
            this.$refs.docInput.files = dt.files;
            this.$refs.docInput.disabled = this.pending.length === 0;
        },
        bumpExpiry() {
            if (! this.issued) return;
            const start = new Date(this.issued + 'T12:00:00');
            start.setFullYear(start.getFullYear() + 1);
            this.expires = start.toISOString().slice(0, 10);
        },
        state() {
            if (! this.expires) return null;
            const exp = new Date(this.expires + 'T12:00:00');
            const today = new Date();
            today.setHours(12, 0, 0, 0);
            const diff = Math.round((exp - today) / 86400000);
            if (diff < 0) return 'expired';
            if (diff <= 30) return 'expiring_soon';
            return 'active';
        },
    }"
>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="operator_id" class="text-sm font-semibold text-gray-900">{{ __('app.frequencies.operator') }}</label>
            <select id="operator_id" name="operator_id" class="field mt-2" required>
                <option value="">{{ __('app.frequencies.operator') }}</option>
                @foreach ($operators as $operator)
                    <option value="{{ $operator->id }}" @selected((string) old('operator_id', $allocation?->operator_id) === (string) $operator->id)>{{ $operator->name }}</option>
                @endforeach
            </select>
            @error('operator_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="region_id" class="text-sm font-semibold text-gray-900">{{ __('app.frequencies.region') }}</label>
            <p class="mt-1 text-xs text-gray-500">{{ __('app.frequencies.region_hint') }}</p>
            <select id="region_id" name="region_id" class="field mt-2">
                <option value="">{{ __('app.frequencies.national') }}</option>
                @foreach ($regions as $region)
                    <option value="{{ $region->id }}" @selected((string) old('region_id', $allocation?->region_id) === (string) $region->id)>{{ $region->localizedName() }}</option>
                @endforeach
            </select>
            @error('region_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="band_label" class="text-sm font-semibold text-gray-900">{{ __('app.frequencies.band') }}</label>
            <input id="band_label" name="band_label" type="text" class="field mt-2" list="frequency-bands" value="{{ old('band_label', $allocation?->band_label) }}" required>
            <datalist id="frequency-bands">
                @foreach (['900 MHz', '1800 MHz', '2100 MHz', '2600 MHz', '3500 MHz'] as $band)
                    <option value="{{ $band }}"></option>
                @endforeach
            </datalist>
            @error('band_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="frequency_range" class="text-sm font-semibold text-gray-900">{{ __('app.frequencies.range') }}</label>
            <input id="frequency_range" name="frequency_range" type="text" class="field mt-2" placeholder="880–915 MHz" value="{{ old('frequency_range', $allocation?->frequency_range) }}" required>
            @error('frequency_range') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="channel_details" class="text-sm font-semibold text-gray-900">{{ __('app.frequencies.channels') }}</label>
        <textarea id="channel_details" name="channel_details" rows="3" class="field mt-2 w-full">{{ old('channel_details', $allocation?->channel_details) }}</textarea>
        @error('channel_details') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="issued_at" class="text-sm font-semibold text-gray-900">{{ __('app.frequencies.issued') }}</label>
            <input id="issued_at" type="date" name="issued_at" x-model="issued" @change="bumpExpiry()" class="field mt-2" required>
            @error('issued_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="expires_at" class="text-sm font-semibold text-gray-900">{{ __('app.frequencies.expires') }}</label>
            <p class="mt-1 text-xs text-gray-500">{{ __('app.frequencies.expires_hint') }}</p>
            <input id="expires_at" type="date" name="expires_at" x-model="expires" class="field mt-2" required>
            @error('expires_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="notes" class="text-sm font-semibold text-gray-900">{{ __('app.frequencies.notes') }}</label>
        <textarea id="notes" name="notes" rows="2" class="field mt-2 w-full">{{ old('notes', $allocation?->notes) }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3.5" x-show="state()" x-cloak>
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">{{ __('app.frequencies.state_preview') }}</p>
        <p class="mt-2 text-sm font-medium text-gray-700" x-text="{
            active: @js(__('app.status.active')),
            expiring_soon: @js(__('app.status.expiring_soon')),
            expired: @js(__('app.status.expired')),
        }[state()]"></p>
    </div>

    <div>
        <p class="text-sm font-semibold text-gray-900">{{ __('app.frequencies.documents') }}</p>
        @error('documents') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('documents.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

        <ul class="mt-3 space-y-2" x-show="existing.length || pending.length" x-cloak>
            <template x-for="doc in existing" :key="doc.path">
                <li class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2.5">
                    <a :href="doc.url" target="_blank" rel="noopener" class="min-w-0 truncate text-sm font-medium text-brand hover:underline" x-text="doc.name"></a>
                    <button type="button" class="text-sm font-semibold text-red-600" @click="dropExisting(doc.path)">{{ __('app.licenses.document_remove') }}</button>
                </li>
            </template>
            <template x-for="(item, index) in pending" :key="item.url">
                <li class="flex items-center justify-between gap-3 rounded-xl border border-dashed border-brand/30 bg-[#F4F8F6] px-3 py-2.5">
                    <span class="min-w-0 truncate text-sm font-medium text-gray-800" x-text="item.name"></span>
                    <button type="button" class="text-sm font-semibold text-red-600" @click="dropPending(index)">{{ __('app.licenses.document_remove') }}</button>
                </li>
            </template>
        </ul>

        <template x-for="path in remove" :key="path">
            <input type="hidden" name="remove_documents[]" :value="path">
        </template>

        <button type="button" class="mt-3 text-sm font-semibold text-brand" :disabled="remaining() <= 0" @click="$refs.docPicker.click()">
            {{ __('app.licenses.documents_add') }}
        </button>
        <input type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" multiple class="sr-only" x-ref="docPicker" @change="add($event.target.files); $event.target.value = ''">
        <input type="file" name="documents[]" multiple class="sr-only" x-ref="docInput" disabled tabindex="-1">
    </div>
</div>
