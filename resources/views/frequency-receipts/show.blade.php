<x-app-layout>
    <div class="p-4 lg:p-8 max-w-4xl">
        <a href="{{ route('frequencies.show', $allocation) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            {{ $allocation->operator->name }} · {{ $allocation->band_label }}
        </a>

        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-brand">{{ __('app.frequency_receipts.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('app.frequency_receipts.subtitle') }}</p>
            </div>
            @if ($receipt)
                <a href="{{ route('frequencies.receipt.print', $allocation) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                    {{ __('app.approval_letters.print') }}
                </a>
            @endif
        </div>

        @if ($receipt)
            <link rel="stylesheet" href="{{ asset('css/frequency-receipt.css') }}">
            <div class="mt-6 data-card p-0 overflow-hidden bg-[#eef2f0]">
                <div class="p-4 sm:p-8 overflow-x-auto flex justify-center">
                    @include('frequency-receipts._document', ['receipt' => $receipt])
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-500">
                {{ __('app.frequency_receipts.meta', [
                    'ref' => $receipt->reference_number,
                    'issuer' => $receipt->issuer?->name ?: __('app.approval_letters.system'),
                    'date' => $receipt->created_at->format('d M Y H:i'),
                ]) }}
            </p>
        @else
            <div class="mt-6 data-card p-8 text-center">
                <p class="text-sm text-gray-600">{{ __('app.frequency_receipts.none_yet') }}</p>
            </div>
        @endif
    </div>
</x-app-layout>
