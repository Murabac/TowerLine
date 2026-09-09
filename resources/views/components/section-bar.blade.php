@props([
    'title',
    'hint' => null,
])

<div {{ $attributes->merge([
    'class' => 'section-bar flex items-center justify-between gap-3 px-5 py-3',
    'style' => 'background-image: linear-gradient(90deg, #1B4D3E 0%, #246352 52%, #14382C 100%)',
]) }}>
    <div class="min-w-0">
        <h2 class="text-sm font-semibold text-white">{{ $title }}</h2>
        @if ($hint)
            <p class="mt-0.5 text-xs text-white/75">{{ $hint }}</p>
        @endif
        {{ $sub ?? '' }}
    </div>
    {{ $end ?? '' }}
</div>
