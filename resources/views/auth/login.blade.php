<x-guest-layout>
    <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-8">
        <div class="text-center mb-6">
            <img src="{{ asset('images/mocit-logo.jpg') }}" alt="" class="mx-auto h-20 w-20 rounded-full bg-[#F5C518] object-cover ring-2 ring-brand/20">
            <h1 class="mt-4 text-lg font-semibold text-brand">{{ __('app.app_short') }}</h1>
            <p class="text-sm text-gray-600">{{ __('app.welcome_back') }}</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <x-input-label for="email" :value="__('app.email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', 'admin@mocit.local')" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password" :value="__('app.password')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-brand focus:ring-brand">
                {{ __('app.remember') }}
            </label>
            <x-primary-button class="w-full justify-center">{{ __('app.log_in') }}</x-primary-button>
        </form>
        <p class="mt-6 text-[11px] text-gray-500 leading-relaxed">
            Demo: <code>admin@mocit.local</code> / <code>password</code>
        </p>
    </div>
</x-guest-layout>
