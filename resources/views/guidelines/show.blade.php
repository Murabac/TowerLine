<x-guidelines-layout>
    <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-[#1B4D3E] via-[#246352] to-[#14382C] px-6 py-8 text-white shadow-[0_12px_40px_rgba(20,56,44,0.18)] sm:px-10 sm:py-10">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[#F5C518]">{{ __('app.guidelines.window_label', [], 'en') }}</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('app.guidelines.title', [], 'en') }}</h1>
                <p class="mt-4 text-sm leading-7 text-white/80 sm:text-[15px]">{{ __('app.guidelines.intro', [], 'en') }}</p>
                <p class="mt-3 text-sm leading-7 text-white/70 sm:text-[15px]">{{ __('app.guidelines.pdf_note', [], 'en') }}</p>
            </div>
            <div class="flex flex-wrap gap-2 lg:flex-col lg:items-stretch">
                <a href="{{ route('guidelines.pdf') }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-brand shadow-sm hover:bg-[#F5C518] hover:text-brand-dark">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h8l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 3v5h5M8 13h8M8 17h5"/>
                    </svg>
                    {{ __('app.guidelines.download_pdf', [], 'en') }}
                </a>
                <a href="{{ route('apply.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#F5C518] px-5 py-3 text-sm font-semibold text-brand-dark hover:bg-white">{{ __('app.apply.open', [], 'en') }}</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-white/10 px-5 py-3 text-sm font-medium text-white ring-1 ring-white/20 hover:bg-white/15">{{ __('app.nav.dashboard', [], 'en') }}</a>
                @endauth
            </div>
        </div>
    </section>

    <section class="mt-10">
        <div class="mb-5">
            <h2 class="text-lg font-semibold tracking-tight text-gray-900">{{ __('app.guidelines.required_title', [], 'en') }}</h2>
        </div>

        <ol class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04),0_16px_40px_rgba(16,24,40,0.05)]">
            @foreach ($documents as $i => $document)
                <li class="grid gap-5 border-b border-gray-100 p-6 last:border-b-0 sm:grid-cols-[auto_1fr] sm:gap-8 sm:p-8">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand text-sm font-semibold text-white shadow-[inset_0_-1px_0_rgba(0,0,0,0.12)]">
                        {{ sprintf('%02d', $i + 1) }}
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold tracking-tight text-gray-900 sm:text-lg">{{ $document['title'] }}</h3>
                        <p class="mt-2 text-sm leading-7 text-gray-600">{{ $document['body'] }}</p>
                        @if (! empty($document['bullets']))
                            <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                                @foreach ($document['bullets'] as $bullet)
                                    <li class="flex gap-2.5 rounded-xl bg-[#F4F8F6] px-3.5 py-2.5 text-sm leading-6 text-gray-700">
                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span>
                                        <span>{{ $bullet }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="mt-8 overflow-hidden rounded-2xl border border-brand/10 bg-white">
        <div class="flex">
            <div class="w-1.5 shrink-0 bg-[#F5C518]"></div>
            <div class="p-5 sm:p-6">
                <h2 class="text-sm font-semibold uppercase tracking-[0.12em] text-brand">{{ __('app.guidelines.next_title', [], 'en') }}</h2>
                <p class="mt-2 text-sm leading-7 text-gray-700">{{ __('app.guidelines.next_body', [], 'en') }}</p>
                <p class="mt-3 text-sm font-semibold text-gray-900">{{ __('app.guidelines.compulsory', [], 'en') }}</p>
                <a href="{{ route('apply.create') }}" class="mt-4 inline-flex rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">{{ __('app.apply.open', [], 'en') }}</a>
            </div>
        </div>
    </section>
</x-guidelines-layout>
