<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('app.sign_in') }} — {{ __('app.ministry_en') }}</title>
    <link rel="icon" href="{{ asset('images/mocit-logo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-surface text-gray-900">
    <div class="min-h-screen flex flex-col">
        <div class="bg-brand text-white py-3 px-4 flex items-center justify-center gap-3">
            <img src="{{ asset('images/mocit-logo.jpg') }}" alt="" class="h-10 w-10 rounded-full bg-[#F5C518] object-cover">
            <div class="text-center">
                <p class="text-sm font-semibold">{{ __('app.ministry_so') }}</p>
                <p class="text-xs text-brand-light">{{ __('app.ministry_en') }}</p>
            </div>
        </div>
        <div class="flex-1 flex items-center justify-center p-6">
            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
