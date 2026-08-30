@php
    $managedUser = $managedUser ?? null;
@endphp

<div
    class="space-y-6"
    x-data="{ role: @js(old('role', $managedUser?->role ?? 'inspector')) }"
>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="name" class="text-sm font-semibold text-gray-900">{{ __('app.users.name') }}</label>
            <input id="name" name="name" value="{{ old('name', $managedUser?->name) }}" class="field mt-2" required>
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email" class="text-sm font-semibold text-gray-900">{{ __('app.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email', $managedUser?->email) }}" class="field mt-2" required>
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="password" class="text-sm font-semibold text-gray-900">{{ __('app.password') }}</label>
        @if ($managedUser)
            <p class="mt-1 text-sm text-gray-500">{{ __('app.users.password_hint') }}</p>
        @endif
        <input id="password" type="password" name="password" class="field mt-2" autocomplete="new-password" @required(! $managedUser)>
        @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <div class="flex items-stretch gap-3">
            <p class="text-sm font-semibold text-gray-900 shrink-0 self-center w-16 sm:w-20">{{ __('app.users.role') }}</p>
            <div class="flex flex-1 flex-row gap-2 min-w-0">
                @foreach (['admin', 'operations_manager', 'inspector'] as $role)
                    <label class="flex-1 min-w-0 cursor-pointer rounded-2xl border border-gray-200 bg-white px-2 py-3 sm:px-3 text-center transition has-[:checked]:border-brand has-[:checked]:bg-[#F4F8F6] has-[:checked]:shadow-[inset_0_0_0_1px_#1B4D3E]">
                        <input type="radio" name="role" value="{{ $role }}" class="sr-only" x-model="role" @checked(old('role', $managedUser?->role ?? 'inspector') === $role) required>
                        <span class="block text-[11px] sm:text-sm font-semibold text-gray-900 leading-snug">{{ __('app.roles.'.$role) }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        @error('role') <p class="text-sm text-red-600 pl-[4.75rem] sm:pl-[5.75rem]">{{ $message }}</p> @enderror
        <p class="text-sm text-gray-500 leading-relaxed pl-[4.75rem] sm:pl-[5.75rem]">{{ __('app.users.role_help') }}</p>
    </div>

    <div x-show="role === 'inspector'" x-cloak>
        <p class="text-sm font-semibold text-gray-900">{{ __('app.users.regions') }}</p>
        <p class="mt-1 text-sm text-gray-500">{{ __('app.users.regions_help') }}</p>
        @error('region_ids') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        @php
            $selectedRegions = collect(old('region_ids', $managedUser?->regions?->pluck('id')->all() ?? []))->map(fn ($id) => (string) $id);
        @endphp
        <div class="mt-3 grid sm:grid-cols-2 gap-2">
            @foreach ($regions as $region)
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-3 text-sm has-[:checked]:border-brand has-[:checked]:bg-[#F4F8F6]">
                    <input type="checkbox" name="region_ids[]" value="{{ $region->id }}" class="rounded border-gray-300 text-brand focus:ring-brand/30" @checked($selectedRegions->contains((string) $region->id))>
                    <span class="font-medium text-gray-900">{{ $region->localizedName() }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>
