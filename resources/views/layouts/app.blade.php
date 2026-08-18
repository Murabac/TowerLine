<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('app.app_short') }} — {{ __('app.ministry_en') }}</title>
    <link rel="icon" href="{{ asset('images/mocit-logo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans antialiased text-gray-900 bg-surface {{ $map ? 'h-screen overflow-hidden' : 'min-h-screen' }}">
    <div class="{{ $map ? 'h-screen flex flex-col' : 'min-h-screen flex flex-col' }}">
        <header class="bg-brand text-white no-print shrink-0">
            <div class="flex items-center justify-between gap-3 px-3 py-2.5 lg:px-4">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('images/mocit-logo.jpg') }}" alt="Somaliland emblem" class="h-12 w-12 rounded-full bg-[#F5C518] object-cover ring-2 ring-white/40">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold leading-tight truncate">{{ __('app.ministry_so') }}</p>
                        <p class="text-xs text-brand-light leading-tight truncate">{{ __('app.ministry_en') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <form method="POST" action="{{ route('locale.update') }}" class="flex rounded overflow-hidden border border-white/20 text-xs">
                        @csrf
                        <button name="locale" value="en" class="px-2 py-1 {{ app()->getLocale() === 'en' ? 'bg-white text-brand' : 'text-white/80 hover:bg-white/10' }}">EN</button>
                        <button name="locale" value="so" class="px-2 py-1 {{ app()->getLocale() === 'so' ? 'bg-white text-brand' : 'text-white/80 hover:bg-white/10' }}">SO</button>
                    </form>

                    @php
                        $initials = collect(preg_split('/\s+/', trim(Auth::user()->name)))
                            ->filter()
                            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->take(2)
                            ->implode('');
                    @endphp

                    <x-dropdown align="right" width="w-64" contentClasses="py-1 bg-white">
                        <x-slot name="trigger">
                            <button type="button" class="inline-flex items-center gap-2.5 rounded-md px-1.5 py-1 text-left hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/40">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#F5C518] text-[13px] font-semibold leading-none tracking-wide text-brand ring-2 ring-white/35">{{ $initials }}</span>
                                <span class="hidden sm:flex min-w-0 max-w-[11rem] flex-col">
                                    <span class="truncate text-sm font-medium leading-5">{{ Auth::user()->name }}</span>
                                    <span class="truncate text-xs text-brand-light leading-4">{{ Auth::user()->roleLabel() }}</span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-white/80" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                                <p class="mt-1 text-xs text-brand">{{ Auth::user()->roleLabel() }}</p>
                            </div>
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('app.profile') }}
                            </x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('app.log_out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>

                    <button type="button" class="lg:hidden p-2 rounded hover:bg-white/10" onclick="document.getElementById('sidebar').classList.toggle('hidden')">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </header>

        <div class="flex flex-1 min-h-0">
            <aside id="sidebar" class="no-print hidden lg:flex w-56 shrink-0 flex-col bg-brand-dark text-brand-light {{ $map ? 'absolute lg:static z-20 h-[calc(100vh-4rem)] lg:h-auto' : '' }}">
                @php
                    $links = [
                        ['dashboard', 'app.nav.dashboard', 'dashboard', true],
                        ['towers.index', 'app.nav.towers', 'towers.*', true],
                    ];
                @endphp
                <nav class="p-3 space-y-1 text-sm">
                    @foreach ($links as [$route, $label, $pattern, $show])
                        @if ($show)
                            <a href="{{ route($route) }}"
                               class="block rounded px-3 py-2 {{ request()->routeIs($pattern) ? 'bg-brand text-white' : 'hover:bg-brand/60 hover:text-white' }}">
                                {{ __($label) }}
                            </a>
                        @endif
                    @endforeach
                </nav>
                <p class="mt-auto p-3 text-[11px] text-white/50">{{ __('app.demo_notice') }}</p>
            </aside>

            <main class="flex-1 min-w-0 {{ $map ? 'overflow-hidden' : 'overflow-auto' }}">
                @if (session('status') && ! $map)
                    <div class="mx-4 mt-4 rounded border border-health-good/30 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
