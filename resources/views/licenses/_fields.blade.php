@php
    $license = $license ?? null;
@endphp

<div
    class="space-y-6"
    x-data="{
        type: @js(old('license_type', $license?->license_type ?? 'A')),
        issued: @js(old('issued_at', $license?->issued_at?->format('Y-m-d'))),
        expires: @js(old('expires_at', $license?->expires_at?->format('Y-m-d'))),
        existing: @js($license?->documentList() ?? []),
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
        days() {
            if (! this.expires) return null;
            const exp = new Date(this.expires + 'T12:00:00');
            const today = new Date();
            today.setHours(12, 0, 0, 0);
            return Math.round((exp - today) / 86400000);
        },
    }"
>
    <fieldset>
        <legend class="text-sm font-semibold text-gray-900">{{ __('app.licenses.type') }}</legend>
        <p class="mt-1 text-sm text-gray-500">{{ __('app.licenses.type_help') }}</p>
        @error('license_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        <div class="mt-3 grid grid-cols-3 gap-2.5">
            @foreach (['A', 'B', 'C'] as $type)
                <label class="cursor-pointer rounded-2xl border border-gray-200 bg-white px-3 py-4 text-center transition has-[:checked]:border-brand has-[:checked]:bg-[#F4F8F6] has-[:checked]:shadow-[inset_0_0_0_1px_#1B4D3E]">
                    <input type="radio" name="license_type" value="{{ $type }}" class="sr-only" x-model="type" @checked(old('license_type', $license?->license_type ?? 'A') === $type) required>
                    <span class="block text-xl font-semibold tracking-tight text-gray-900">{{ $type }}</span>
                    <span class="mt-1 block text-[11px] text-gray-500">{{ __('app.licenses.type_label.'.$type) }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="issued_at" class="text-sm font-semibold text-gray-900">{{ __('app.licenses.issued') }}</label>
            <p class="mt-1 text-sm text-gray-500">{{ __('app.licenses.issued_help') }}</p>
            <input id="issued_at" type="date" name="issued_at" x-model="issued" value="{{ old('issued_at', $license?->issued_at?->format('Y-m-d')) }}" class="field mt-3" required>
            @error('issued_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="expires_at" class="text-sm font-semibold text-gray-900">{{ __('app.licenses.expires') }}</label>
            <p class="mt-1 text-sm text-gray-500">{{ __('app.licenses.expires_help') }}</p>
            <input id="expires_at" type="date" name="expires_at" x-model="expires" value="{{ old('expires_at', $license?->expires_at?->format('Y-m-d')) }}" class="field mt-3" required>
            @error('expires_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3.5" x-show="state()" x-cloak>
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">{{ __('app.licenses.state_preview') }}</p>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                  :class="{
                      'bg-emerald-50 text-emerald-800 ring-emerald-600/15': state() === 'active',
                      'bg-amber-50 text-amber-800 ring-amber-600/15': state() === 'expiring_soon',
                      'bg-red-50 text-red-800 ring-red-600/15': state() === 'expired',
                  }">
                <span class="h-1.5 w-1.5 rounded-full"
                      :class="{
                          'bg-emerald-500': state() === 'active',
                          'bg-amber-500': state() === 'expiring_soon',
                          'bg-red-500': state() === 'expired',
                      }"></span>
                <span x-text="{
                    active: @js(__('app.status.active')),
                    expiring_soon: @js(__('app.status.expiring_soon')),
                    expired: @js(__('app.status.expired')),
                }[state()]"></span>
            </span>
            <span class="text-sm text-gray-500" x-show="days() !== null" x-text="days() < 0
                ? @js(__('app.licenses.days_ago')).replace(':count', String(Math.abs(days())))
                : @js(__('app.licenses.days_left')).replace(':count', String(days()))"></span>
        </div>
    </div>

    <div>
        <p class="text-sm font-semibold text-gray-900">{{ __('app.licenses.documents') }}</p>
        <p class="mt-1 text-sm text-gray-500">{{ __('app.licenses.documents_help') }}</p>
        @error('documents') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('documents.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

        <ul class="mt-3 space-y-2" x-show="existing.length || pending.length" x-cloak>
            <template x-for="doc in existing" :key="doc.path">
                <li class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2.5">
                    <a :href="doc.url" target="_blank" rel="noopener" class="min-w-0 truncate text-sm font-medium text-brand hover:underline" x-text="doc.name"></a>
                    <button type="button" class="text-sm font-semibold text-red-600 hover:text-red-700" @click="dropExisting(doc.path)">{{ __('app.licenses.document_remove') }}</button>
                </li>
            </template>
            <template x-for="(item, index) in pending" :key="item.url">
                <li class="flex items-center justify-between gap-3 rounded-xl border border-dashed border-brand/30 bg-[#F4F8F6] px-3 py-2.5">
                    <span class="min-w-0 truncate text-sm font-medium text-gray-800" x-text="item.name"></span>
                    <button type="button" class="text-sm font-semibold text-red-600 hover:text-red-700" @click="dropPending(index)">{{ __('app.licenses.document_remove') }}</button>
                </li>
            </template>
        </ul>

        <template x-for="path in remove" :key="path">
            <input type="hidden" name="remove_documents[]" :value="path">
        </template>

        <button
            type="button"
            class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand disabled:text-gray-400"
            :disabled="remaining() <= 0"
            @click="$refs.docPicker.click()"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739 10.54 20.576a5.125 5.125 0 1 1-7.25-7.25l9.19-9.19a3.75 3.75 0 1 1 5.304 5.305L9.16 17.06a2.25 2.25 0 0 1-3.182-3.182l8.49-8.49"/></svg>
            <span x-text="(existing.length + pending.length) ? @js(__('app.licenses.documents_add_more')) : @js(__('app.licenses.documents_add'))"></span>
            <span class="font-normal text-gray-500" x-text="'(' + (existing.length + pending.length) + '/' + max + ')'"></span>
        </button>
        <input
            type="file"
            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,application/pdf,image/*"
            multiple
            class="sr-only"
            x-ref="docPicker"
            @change="add($event.target.files); $event.target.value = ''"
        >
        <input
            type="file"
            name="documents[]"
            multiple
            class="sr-only"
            x-ref="docInput"
            disabled
            tabindex="-1"
        >
    </div>
</div>
