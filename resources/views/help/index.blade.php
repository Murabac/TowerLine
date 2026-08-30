<x-app-layout>
    <div class="p-4 lg:p-8 max-w-5xl">
        <div class="overflow-hidden rounded-3xl border border-brand/15 bg-gradient-to-br from-[#1B4D3E] via-[#246352] to-[#1B4D3E] px-6 py-8 sm:px-8 text-white">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/70">{{ __('app.app_short') }}</p>
            <h1 class="mt-2 max-w-xl text-2xl sm:text-3xl font-semibold tracking-tight">{{ __('app.help.title') }}</h1>
            <p class="mt-2 max-w-xl text-sm text-white/80">{{ __('app.help.subtitle') }}</p>
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('map') }}" class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-brand hover:bg-brand-light">{{ __('app.help.open_map') }}</a>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/25 hover:bg-white/20">{{ __('app.nav.dashboard') }}</a>
                <a href="{{ route('towers.index') }}" class="inline-flex items-center rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/25 hover:bg-white/20">{{ __('app.nav.towers') }}</a>
            </div>
        </div>

        <section class="mt-8">
            <h2 class="text-sm font-semibold uppercase tracking-[0.12em] text-gray-400">{{ __('app.help.daily.title') }}</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach (['one', 'two', 'three', 'four'] as $i => $step)
                    <article class="rounded-2xl border border-gray-200/90 bg-white p-5 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand/10 text-sm font-semibold text-brand">{{ $i + 1 }}</span>
                        <p class="mt-3 text-sm leading-6 text-gray-700">{{ __('app.help.daily.'.$step) }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="mt-8">
            <h2 class="text-sm font-semibold uppercase tracking-[0.12em] text-gray-400">{{ __('app.help.roles.title') }}</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                @foreach ([
                    'admin' => ['bg' => 'bg-emerald-50', 'ring' => 'ring-emerald-200', 'badge' => 'bg-emerald-100 text-emerald-800'],
                    'operations_manager' => ['bg' => 'bg-amber-50', 'ring' => 'ring-amber-200', 'badge' => 'bg-amber-100 text-amber-800'],
                    'inspector' => ['bg' => 'bg-sky-50', 'ring' => 'ring-sky-200', 'badge' => 'bg-sky-100 text-sky-800'],
                ] as $role => $tone)
                    <article class="rounded-2xl border border-gray-200/80 {{ $tone['bg'] }} p-5 {{ Auth::user()->role === $role ? 'ring-2 '.$tone['ring'] : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-base font-semibold text-gray-900">{{ __('app.roles.'.$role) }}</h3>
                            @if (Auth::user()->role === $role)
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $tone['badge'] }}">{{ __('app.help.your_role') }}</span>
                            @endif
                        </div>
                        <p class="mt-3 text-sm leading-6 text-gray-600">{{ __('app.help.roles.'.$role) }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="mt-8 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-2xl">
                    <h2 class="text-base font-semibold text-gray-900">{{ __('app.help.map.title') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600">{{ __('app.help.map.body') }}</p>
                </div>
                <a href="{{ route('map') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">
                    {{ __('app.help.open_map') }}
                </a>
            </div>
        </section>
    </div>
</x-app-layout>
