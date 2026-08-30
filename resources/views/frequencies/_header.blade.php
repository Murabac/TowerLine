@php
    $current = $current ?? 'dashboard';
    $title = $title ?? __('app.frequencies.dashboard_title');
    $subtitle = $subtitle ?? null;
@endphp

<div class="space-y-4">
    <div>
        <p class="text-xs font-medium uppercase tracking-wider text-brand-muted">{{ __('app.app_short') }}</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $subtitle }}</p>
        @endif
    </div>

    @include('frequencies._nav', ['current' => $current])
</div>
