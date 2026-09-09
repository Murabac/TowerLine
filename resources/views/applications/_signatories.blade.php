@if (count($signatories))
    <div>
        @if ($showHeading ?? true)
            <h3 class="text-sm font-semibold text-gray-900">{{ __('app.signatures.trail') }}</h3>
            <p class="mt-1 text-xs text-gray-500">{{ __('app.signatures.trail_hint') }}</p>
        @endif
        <ul @class(['divide-y divide-gray-100 rounded-xl border border-gray-100', 'mt-3' => $showHeading ?? true])>
            @foreach ($signatories as $row)
                <li class="flex flex-wrap items-center gap-4 px-4 py-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $row['name'] }}</p>
                        <p class="text-xs text-gray-500">{{ $row['label'] }} · {{ $row['outcome'] }}</p>
                        @if ($row['at'])
                            <p class="text-xs text-gray-400">{{ $row['at']->timezone(config('app.timezone'))->format('d M Y H:i') }}</p>
                        @endif
                    </div>
                    <div class="h-14 w-40 shrink-0 rounded border border-gray-100 bg-white px-2 py-1">
                        @if ($row['path'])
                            <img src="{{ route('applications.signatures.show', [$application, $row['party']]) }}" alt="" class="h-full w-full object-contain">
                        @else
                            <p class="flex h-full items-center justify-center text-[11px] text-gray-400">{{ __('app.signatures.missing') }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
