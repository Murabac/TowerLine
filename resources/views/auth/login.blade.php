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
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
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

        <p class="mt-5 text-center">
            <a href="{{ route('guidelines') }}" target="_blank" rel="noopener" class="text-sm font-semibold text-brand hover:underline">{{ __('app.guidelines.open') }}</a>
        </p>
        <p class="mt-2 text-center">
            <a href="{{ route('apply.create') }}" class="text-sm font-medium text-gray-600 hover:text-brand hover:underline">{{ __('app.apply.open', [], 'en') }}</a>
        </p>

        @unless (app()->isProduction())
            <div class="mt-6 border-t border-gray-100 pt-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">{{ __('app.demo.quick_login') }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ __('app.demo.quick_login_hint') }}</p>
                <div class="mt-3 grid gap-2">
                    @foreach ([
                        ['email' => 'admin@mocit.local', 'label' => __('app.demo.admin'), 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-900 hover:bg-emerald-100'],
                        ['email' => 'ops@mocit.local', 'label' => __('app.demo.operations_manager'), 'class' => 'border-amber-200 bg-amber-50 text-amber-900 hover:bg-amber-100'],
                        ['email' => 'inspector.maroodi@mocit.local', 'label' => __('app.demo.inspector_maroodi'), 'class' => 'border-sky-200 bg-sky-50 text-sky-900 hover:bg-sky-100'],
                        ['email' => 'section.head@mocit.local', 'label' => __('app.demo.section_head'), 'class' => 'border-violet-200 bg-violet-50 text-violet-900 hover:bg-violet-100'],
                        ['email' => 'coordinator.maroodi@mocit.local', 'label' => __('app.demo.coordinator_maroodi'), 'class' => 'border-violet-200 bg-violet-50 text-violet-900 hover:bg-violet-100'],
                        ['email' => 'director@mocit.local', 'label' => __('app.demo.director'), 'class' => 'border-violet-200 bg-violet-50 text-violet-900 hover:bg-violet-100'],
                        ['email' => 'dg@mocit.local', 'label' => __('app.demo.dg'), 'class' => 'border-violet-200 bg-violet-50 text-violet-900 hover:bg-violet-100'],
                    ] as $account)
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ $account['email'] }}">
                            <input type="hidden" name="password" value="password">
                            <button type="submit" class="w-full rounded-xl border px-3 py-2.5 text-sm font-semibold text-left {{ $account['class'] }}">
                                {{ $account['label'] }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endunless
    </div>
</x-guest-layout>
