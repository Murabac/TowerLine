<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('app.guidelines.window_label', [], 'en') }} — {{ __('app.ministry_en', [], 'en') }}</title>
    <link rel="icon" href="{{ asset('images/mocit-logo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans antialiased bg-[#F3F6F4] text-gray-900">
    <div class="min-h-screen flex flex-col">
        <header class="relative bg-brand text-white">
            <div class="absolute inset-x-0 bottom-0 h-1 bg-[#F5C518]"></div>
            <div class="mx-auto flex max-w-4xl items-center gap-4 px-5 py-4 sm:px-8 sm:py-5">
                <img src="{{ asset('images/mocit-logo.jpg') }}" alt="" class="h-14 w-14 shrink-0 rounded-full bg-[#F5C518] object-cover ring-2 ring-[#F5C518]/80 shadow-sm">
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#F5C518]">{{ __('app.guidelines.window_label', [], 'en') }}</p>
                    <p class="mt-0.5 truncate text-base font-semibold leading-tight">{{ __('app.ministry_so', [], 'so') }}</p>
                    <p class="truncate text-xs text-brand-light leading-tight">{{ __('app.ministry_en', [], 'en') }}</p>
                </div>
                <span class="hidden sm:inline-flex rounded-full bg-white/10 px-3 py-1 text-[11px] font-medium tracking-wide text-white/90 ring-1 ring-white/15">MoCIT</span>
            </div>
        </header>
        <main class="flex-1 px-4 py-10 sm:px-6 sm:py-12">
            <div class="mx-auto w-full max-w-4xl">
                {{ $slot }}
            </div>
        </main>
        <footer class="border-t border-brand/10 bg-white/70">
            <p class="mx-auto max-w-4xl px-5 py-4 text-center text-[11px] text-gray-500 sm:px-8">
                {{ __('app.ministry_en', [], 'en') }} · {{ __('app.guidelines.window_label', [], 'en') }}
            </p>
        </footer>
    </div>
    @stack('scripts')
</body>
</html>
