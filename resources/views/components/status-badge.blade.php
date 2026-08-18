@props(['value' => 'unknown'])

@php
    $styles = [
        'active' => ['bg-emerald-50 text-emerald-800 ring-emerald-600/15', 'bg-emerald-500'],
        'good' => ['bg-emerald-50 text-emerald-800 ring-emerald-600/15', 'bg-emerald-500'],
        'under_construction' => ['bg-sky-50 text-sky-800 ring-sky-600/15', 'bg-sky-500'],
        'decommissioned' => ['bg-gray-100 text-gray-600 ring-gray-500/15', 'bg-gray-400'],
        'needs_attention' => ['bg-amber-50 text-amber-800 ring-amber-600/15', 'bg-amber-500'],
        'critical' => ['bg-red-50 text-red-800 ring-red-600/15', 'bg-red-500'],
        'unknown' => ['bg-gray-100 text-gray-600 ring-gray-500/10', 'bg-gray-400'],
        'expired' => ['bg-red-50 text-red-800 ring-red-600/15', 'bg-red-500'],
        'expiring_soon' => ['bg-amber-50 text-amber-800 ring-amber-600/15', 'bg-amber-500'],
        'telecom' => ['bg-teal-50 text-teal-800 ring-teal-600/15', 'bg-teal-500'],
        'broadcast' => ['bg-orange-50 text-orange-800 ring-orange-600/15', 'bg-orange-500'],
        'A' => ['bg-brand/10 text-brand ring-brand/15', 'bg-brand'],
        'B' => ['bg-indigo-50 text-indigo-800 ring-indigo-600/15', 'bg-indigo-500'],
        'C' => ['bg-slate-100 text-slate-700 ring-slate-500/15', 'bg-slate-500'],
    ];
    [$chip, $dot] = $styles[$value] ?? ['bg-gray-100 text-gray-700 ring-gray-500/10', 'bg-gray-400'];
    $label = in_array($value, ['A', 'B', 'C'], true) ? $value : __('app.status.'.$value);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {$chip}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
    {{ $label }}
</span>
