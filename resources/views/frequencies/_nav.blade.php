@php
    $current = $current ?? 'dashboard';

    $items = [
        'dashboard' => [
            'route' => 'frequencies.dashboard',
            'label' => __('app.frequencies.nav.overview'),
            'description' => __('app.frequencies.nav.overview_desc'),
            'icon' => 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6.75v6.75',
        ],
        'registry' => [
            'route' => 'frequencies.registry',
            'label' => __('app.frequencies.nav.registry'),
            'description' => __('app.frequencies.nav.registry_desc'),
            'icon' => 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.125 1.125 0 0 1 1.125 1.125v2.25a1.125 1.125 0 0 1-1.125 1.125H5.625a1.125 1.125 0 0 1-1.125-1.125V5.625A1.125 1.125 0 0 1 5.625 4.5Z',
        ],
    ];
@endphp

<nav class="data-card p-2 sm:p-2.5" aria-label="{{ __('app.frequencies.title') }}">
    <div class="flex flex-col gap-2 lg:flex-row lg:items-stretch">
        <div class="grid flex-1 gap-2 sm:grid-cols-2">
            @foreach ($items as $key => $item)
                @php $active = $current === $key; @endphp
                <a href="{{ route($item['route']) }}"
                   @class([
                       'group relative flex items-start gap-3 rounded-xl border px-4 py-3.5 transition',
                       'border-brand/20 bg-brand text-white shadow-sm' => $active,
                       'border-transparent bg-gray-50/80 text-gray-700 hover:border-gray-200 hover:bg-white' => ! $active,
                   ])
                   @if ($active) aria-current="page" @endif>
                    <span @class([
                        'mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                        'bg-white/15 text-white' => $active,
                        'bg-white text-brand ring-1 ring-gray-200/80 group-hover:ring-brand/20' => ! $active,
                    ])>
                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                    </span>
                    <span class="min-w-0 pt-0.5">
                        <span class="block text-sm font-semibold leading-tight">{{ $item['label'] }}</span>
                        <span @class([
                            'mt-0.5 block text-xs leading-snug',
                            'text-white/80' => $active,
                            'text-gray-500' => ! $active,
                        ])>{{ $item['description'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        @can('create', App\Models\FrequencyAllocation::class)
            @php $createActive = $current === 'create'; @endphp
            <a href="{{ route('frequencies.create') }}"
               @class([
                   'group flex items-center gap-3 rounded-xl border px-4 py-3.5 transition lg:w-56 lg:shrink-0',
                   'border-brand/20 bg-brand text-white shadow-sm' => $createActive,
                   'border-dashed border-brand/30 bg-brand/[0.04] text-brand hover:border-brand/50 hover:bg-brand/[0.08]' => ! $createActive,
               ])
               @if ($createActive) aria-current="page" @endif>
                <span @class([
                    'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                    'bg-white/15 text-white' => $createActive,
                    'bg-brand text-white shadow-sm group-hover:bg-brand-dark' => ! $createActive,
                ])>
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold leading-tight">{{ __('app.frequencies.create') }}</span>
                    <span @class([
                        'mt-0.5 block text-xs leading-snug',
                        'text-white/80' => $createActive,
                        'text-brand/70' => ! $createActive,
                    ])>{{ __('app.frequencies.nav.create_desc') }}</span>
                </span>
            </a>
        @endcan
    </div>
</nav>
