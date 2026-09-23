@php
    $managedUser = $managedUser ?? null;
    $regionalRoleKeys = $roles->where('requires_regions', true)->pluck('key')->values();
@endphp

<div
    class="space-y-6"
    x-data="{
        role: @js(old('role', $managedUser?->role ?? 'inspector')),
        regionalRoles: @js($regionalRoleKeys),
        operatorRole: @js(\App\Models\Role::KEY_OPERATOR_VIEWER),
        get needsRegions() { return this.regionalRoles.includes(this.role) },
        get needsOperator() { return this.role === this.operatorRole }
    }"
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
        <label for="role" class="text-sm font-semibold text-gray-900">{{ __('app.users.role') }}</label>
        <select id="role" name="role" class="field mt-2" x-model="role" required>
            @foreach ($roles as $roleOption)
                <option value="{{ $roleOption->key }}" @selected(old('role', $managedUser?->role ?? 'inspector') === $roleOption->key)>
                    {{ $roleOption->displayName() }}
                </option>
            @endforeach
        </select>
        @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        <p class="text-sm text-gray-500 leading-relaxed">{{ __('app.users.role_help') }}</p>
    </div>

    <div x-show="needsOperator" x-cloak>
        <label for="operator_id" class="text-sm font-semibold text-gray-900">{{ __('app.users.operator') }}</label>
        <p class="mt-1 text-sm text-gray-500">{{ __('app.users.operator_help') }}</p>
        <select id="operator_id" name="operator_id" class="field mt-2">
            <option value="">{{ __('app.towers.operator') }}</option>
            @foreach ($operators as $operator)
                <option value="{{ $operator->id }}" @selected((string) old('operator_id', $managedUser?->operator_id) === (string) $operator->id)>{{ $operator->name }}</option>
            @endforeach
        </select>
        @error('operator_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div x-show="needsRegions" x-cloak>
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
