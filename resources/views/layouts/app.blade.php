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
<body class="font-sans antialiased text-gray-900 bg-surface {{ $map ? 'h-screen overflow-hidden' : 'min-h-screen' }}"
      x-data="{
          collapsed: localStorage.getItem('towerline-sidebar') === 'collapsed',
          mobileOpen: false,
          toggleCollapsed() {
              this.collapsed = ! this.collapsed;
              localStorage.setItem('towerline-sidebar', this.collapsed ? 'collapsed' : 'expanded');
              this.$nextTick(() => window.dispatchEvent(new Event('resize')));
          }
      }">
    <div class="{{ $map ? 'h-screen flex flex-col' : 'min-h-screen flex flex-col' }}">
        <header class="relative z-[2000] h-16 bg-brand text-white no-print shrink-0">
            <div class="flex h-full items-center gap-2 px-3 lg:px-4">
                <div class="flex min-w-0 flex-1 items-center gap-2.5">
                    <img src="{{ asset('images/mocit-logo.jpg') }}" alt="Somaliland emblem" class="h-10 w-10 lg:h-12 lg:w-12 shrink-0 rounded-full bg-[#F5C518] object-cover ring-2 ring-white/40">
                    <div class="hidden min-[420px]:block min-w-0">
                        <p class="text-sm font-semibold leading-tight truncate">{{ __('app.ministry_so') }}</p>
                        <p class="text-xs text-brand-light leading-tight truncate">{{ __('app.ministry_en') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
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
                            <button type="button" class="inline-flex items-center gap-2 rounded-md p-0.5 sm:px-1.5 sm:py-1 text-left hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/40">
                                <span class="inline-flex h-9 w-9 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-full bg-[#F5C518] text-[13px] font-semibold leading-none tracking-wide text-brand ring-2 ring-white/35">{{ $initials }}</span>
                                <span class="hidden sm:flex min-w-0 max-w-[11rem] flex-col">
                                    <span class="truncate text-sm font-medium leading-5">{{ Auth::user()->name }}</span>
                                    <span class="truncate text-xs text-brand-light leading-4">{{ Auth::user()->roleLabel() }}</span>
                                </span>
                                <svg class="hidden sm:block h-4 w-4 shrink-0 text-white/80" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
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

                    <button type="button" class="lg:hidden p-2 rounded hover:bg-white/10" @click.stop="mobileOpen = ! mobileOpen" aria-label="Menu">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </header>

        <div class="relative flex flex-1 min-h-0">
            <div
                x-show="mobileOpen"
                x-cloak
                class="lg:hidden fixed inset-x-0 bottom-0 top-16 z-[1900] bg-black/40"
                @click="mobileOpen = false"
            ></div>
            <aside
                id="sidebar"
                class="no-print w-64 max-w-[80vw] shrink-0 flex-col bg-brand-dark text-brand-light overflow-y-auto lg:relative lg:z-auto lg:w-56 lg:max-w-none"
                :class="(mobileOpen ? 'flex fixed left-0 top-16 bottom-0 z-[1950]' : 'hidden') + ' lg:flex lg:static lg:inset-auto ' + (collapsed ? 'lg:w-14' : 'lg:w-56')"
            >
                @php
                    $links = [
                        ['dashboard', 'app.nav.dashboard', 'dashboard', 'M3 7.5A1.5 1.5 0 0 1 4.5 6h5A1.5 1.5 0 0 1 11 7.5v3A1.5 1.5 0 0 1 9.5 12h-5A1.5 1.5 0 0 1 3 10.5v-3Zm10.5 0A1.5 1.5 0 0 1 15 6h4.5A1.5 1.5 0 0 1 21 7.5v9a1.5 1.5 0 0 1-1.5 1.5H15a1.5 1.5 0 0 1-1.5-1.5v-9ZM3 16.5A1.5 1.5 0 0 1 4.5 15h5a1.5 1.5 0 0 1 1.5 1.5v3A1.5 1.5 0 0 1 9.5 21h-5A1.5 1.5 0 0 1 3 19.5v-3Z'],
                        ['map', 'app.nav.map', 'map', 'M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z'],
                        ['towers.index', 'app.nav.towers', 'towers.*', 'M12 21V10.5M8.25 21V14.25m7.5 6.75V12M4.5 21h15M12 3.75 6.75 8.25h10.5L12 3.75Z'],
                        ['licenses.index', 'app.nav.licenses', 'licenses.*', 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
                        ['help', 'app.nav.help', 'help', 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z'],
                    ];
                    if (Auth::user()->isAdmin()) {
                        $links[] = ['districts.index', 'app.nav.geography', 'districts.*', 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25h9m-4.5-8.25L12 3m0 0 4.5 3.75M12 3 7.5 6.75'];
                        $links[] = ['users.index', 'app.nav.users', 'users.*', 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'];
                        $links[] = ['audit-logs.index', 'app.nav.audit', 'audit-logs.*', 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18A2.25 2.25 0 0 0 20.25 16.5V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z'];
                        $links[] = ['settings.ministry.edit', 'app.nav.settings', 'settings.*', 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'];
                    }
                @endphp
                <div class="hidden lg:flex items-center px-2 py-2 border-b border-white/10" :class="collapsed ? 'justify-center' : 'justify-end'">
                    <button type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-brand-light hover:bg-brand/60 hover:text-white"
                            @click="toggleCollapsed()"
                            :title="collapsed ? @js(__('app.nav.expand')) : @js(__('app.nav.collapse'))"
                            :aria-label="collapsed ? @js(__('app.nav.expand')) : @js(__('app.nav.collapse'))">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" :class="collapsed ? 'rotate-180' : ''">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                </div>
                <nav class="p-2 space-y-1 text-sm">
                    @foreach ($links as [$route, $label, $pattern, $icon])
                        <a href="{{ route($route) }}"
                           @click="mobileOpen = false"
                           title="{{ __($label) }}"
                           class="flex items-center gap-3 rounded px-2.5 py-2 {{ request()->routeIs($pattern) ? 'bg-brand text-white' : 'hover:bg-brand/60 hover:text-white' }}"
                           :class="collapsed ? 'lg:justify-center lg:px-0' : ''">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                            </svg>
                            <span class="truncate" :class="collapsed ? 'lg:hidden' : ''">{{ __($label) }}</span>
                        </a>
                    @endforeach
                </nav>
                <p class="mt-auto p-3 text-[11px] text-white/50" :class="collapsed ? 'lg:hidden' : ''">{{ __('app.demo_notice') }}</p>
            </aside>

            <main class="flex-1 min-w-0 {{ $map ? 'overflow-hidden min-h-0' : 'overflow-auto' }}">
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
