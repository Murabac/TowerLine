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

    <fieldset>
        <legend class="text-sm font-semibold text-gray-900">{{ __('app.users.role') }}</legend>
        <p class="mt-1 text-sm text-gray-500">{{ __('app.users.role_help') }}</p>
        @error('role') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        <div class="mt-3 grid sm:grid-cols-3 gap-2.5">
            @foreach (['admin', 'inspector', 'operator_viewer'] as $role)
                <label class="cursor-pointer rounded-2xl border border-gray-200 bg-white px-3 py-4 text-center transition has-[:checked]:border-brand has-[:checked]:bg-[#F4F8F6] has-[:checked]:shadow-[inset_0_0_0_1px_#1B4D3E]">
                    <input type="radio" name="role" value="{{ $role }}" class="sr-only" x-model="role" @checked(old('role', $managedUser?->role ?? 'inspector') === $role) required>
                    <span class="block text-sm font-semibold text-gray-900">{{ __('app.roles.'.$role) }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    <div x-show="role === 'inspector'" x-cloak>
        <label for="region_id" class="text-sm font-semibold text-gray-900">{{ __('app.users.region') }}</label>
        <select id="region_id" name="region_id" class="field mt-2" :required="role === 'inspector'">
            <option value="">{{ __('app.users.region') }}</option>
            @foreach ($regions as $region)
                <option value="{{ $region->id }}" @selected((string) old('region_id', $managedUser?->region_id) === (string) $region->id)>{{ $region->localizedName() }}</option>
            @endforeach
        </select>
        @error('region_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div x-show="role === 'operator_viewer'" x-cloak>
        <label for="operator_id" class="text-sm font-semibold text-gray-900">{{ __('app.users.operator') }}</label>
        <select id="operator_id" name="operator_id" class="field mt-2" :required="role === 'operator_viewer'">
            <option value="">{{ __('app.users.operator') }}</option>
            @foreach ($operators as $operator)
                <option value="{{ $operator->id }}" @selected((string) old('operator_id', $managedUser?->operator_id) === (string) $operator->id)>{{ $operator->name }}</option>
            @endforeach
        </select>
        @error('operator_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
