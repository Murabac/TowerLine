<x-app-layout>
    <div class="p-4 lg:p-8 max-w-4xl">
        <h1 class="text-2xl font-semibold text-brand">{{ __('app.dashboard.title') }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ __('app.demo_notice') }}</p>

        <div class="mt-6 grid sm:grid-cols-2 gap-4">
            <a href="{{ route('towers.index') }}" class="bg-white border border-gray-200 rounded-lg p-5 hover:border-brand">
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('app.towers.title') }}</p>
                <p class="mt-2 text-3xl font-semibold text-brand">{{ $towerCount }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ __('app.nav.towers') }}</p>
            </a>
        </div>
    </div>
</x-app-layout>
