@php
    $managedRole = $managedRole ?? null;
    $selectedTasks = collect(old('tasks', $selectedTasks ?? []));
@endphp

<section class="data-card p-5 sm:p-6 space-y-5">
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="name" class="text-sm font-semibold text-gray-900">{{ __('app.role_admin.name') }}</label>
            @if ($managedRole?->isSystem())
                <p class="mt-2 text-sm text-gray-800">{{ $managedRole->displayName() }}</p>
            @else
                <input id="name" name="name" value="{{ old('name', $managedRole?->name) }}" class="field mt-2" required>
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @endif
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-900">{{ __('app.role_admin.scope') }}</p>
            @if ($managedRole?->isSystem())
                <p class="mt-2 text-sm text-gray-800">{{ $managedRole->requires_regions ? __('app.role_admin.regional') : __('app.role_admin.ministry_wide') }}</p>
            @else
                <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-white px-3 py-3 text-sm has-[:checked]:border-brand has-[:checked]:bg-[#F4F8F6]">
                    <input type="hidden" name="requires_regions" value="0">
                    <input type="checkbox" name="requires_regions" value="1" class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand/30" @checked(old('requires_regions', $managedRole?->requires_regions))>
                    <span>
                        <span class="block font-medium text-gray-900">{{ __('app.role_admin.regional') }}</span>
                        <span class="mt-0.5 block text-xs text-gray-500">{{ __('app.role_admin.regional_help') }}</span>
                    </span>
                </label>
            @endif
        </div>
    </div>
</section>

<section class="data-card p-5 sm:p-6 space-y-6">
    <div>
        <h2 class="text-base font-semibold text-gray-900">{{ __('app.role_admin.checklist') }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ __('app.role_admin.checklist_help') }}</p>
        @error('tasks') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('tasks.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @foreach ($groups as $group => $keys)
        <fieldset>
            <legend class="text-sm font-semibold text-brand">{{ __('app.role_admin.groups.'.$group) }}</legend>
            <div class="mt-3 grid sm:grid-cols-2 gap-2">
                @foreach ($keys as $key)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-white px-3 py-3 text-sm has-[:checked]:border-brand has-[:checked]:bg-[#F4F8F6]">
                        <input type="checkbox" name="tasks[]" value="{{ $key }}" class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand/30" @checked($selectedTasks->contains($key))>
                        <span>
                            <span class="block font-medium text-gray-900">{{ __('app.role_admin.task.'.str_replace('.', '_', $key)) }}</span>
                            <span class="mt-0.5 block text-[11px] text-gray-400">{{ $key }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach
</section>
