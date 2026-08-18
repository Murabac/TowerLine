@if ($paginator->hasPages())
    <nav role="navigation" class="inline-flex items-center gap-1">
        @if ($paginator->onFirstPage())
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-white hover:text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-1 text-gray-400 text-sm">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-brand px-2 text-sm font-semibold text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm text-gray-600 hover:bg-white hover:text-brand">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-white hover:text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </a>
        @else
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </span>
        @endif
    </nav>
@endif
