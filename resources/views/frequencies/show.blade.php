<x-app-layout>
    <div class="p-4 lg:p-8 max-w-4xl">
        <a href="{{ route('frequencies.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            {{ __('app.frequencies.dashboard_title') }}
        </a>

        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-brand">{{ $allocation->operator->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $allocation->band_label }} · {{ $allocation->frequency_range }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($allocation->currentLetter)
                    <a href="{{ route('frequencies.letter.show', $allocation) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-sm font-semibold rounded-xl hover:border-brand hover:text-brand">
                        {{ __('app.frequency_letters.title') }}
                    </a>
                @endif
                @if ($allocation->currentReceipt)
                    <a href="{{ route('frequencies.receipt.show', $allocation) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-sm font-semibold rounded-xl hover:border-brand hover:text-brand">
                        {{ __('app.frequency_receipts.title') }}
                    </a>
                @endif
                @can('renew', $allocation)
                    <form method="POST" action="{{ route('frequencies.renew', $allocation) }}" onsubmit="return confirm(@json(__('app.frequencies.confirm_renew')))">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                            {{ __('app.frequencies.renew') }}
                        </button>
                    </form>
                @endcan
                @can('update', $allocation)
                    <a href="{{ route('frequencies.edit', $allocation) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-sm font-semibold rounded-xl hover:border-brand hover:text-brand">
                        {{ __('app.edit') }}
                    </a>
                @endcan
            </div>
        </div>

        @if (session('status'))
            <p class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</p>
        @endif

        <div class="mt-6 grid sm:grid-cols-2 gap-4">
            <div class="data-card p-5">
                <p class="stat-label">{{ __('app.frequencies.coverage') }}</p>
                <p class="stat-value text-lg">{{ $allocation->coverageLabel() }}</p>
            </div>
            <div class="data-card p-5">
                <p class="stat-label">{{ __('app.frequencies.state') }}</p>
                <p class="mt-2"><x-status-badge :value="$allocation->display_status" /></p>
            </div>
            <div class="data-card p-5">
                <p class="stat-label">{{ __('app.frequencies.issued') }}</p>
                <p class="stat-value text-lg">{{ $allocation->issued_at->format('d M Y') }}</p>
            </div>
            <div class="data-card p-5">
                <p class="stat-label">{{ __('app.frequencies.expires') }}</p>
                <p class="stat-value text-lg">{{ $allocation->expires_at->format('d M Y') }}</p>
            </div>
        </div>

        @if ($documentTimeline->isNotEmpty())
            <section class="mt-5 data-card overflow-hidden border-brand/15">
                <div class="px-5 py-4 border-b border-brand/10 bg-gradient-to-r from-brand/[0.08] via-brand/[0.05] to-emerald-50/80">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-brand text-white shadow-sm">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 0H5.625A2.25 2.25 0 0 0 3.25 6.75v12A2.25 2.25 0 0 0 5.625 21h12.75A2.25 2.25 0 0 0 20.75 18.75v-12A2.25 2.25 0 0 0 18.375 4.5H16.5m-3 0V3.375A1.125 1.125 0 0 0 12.375 2.25h-1.5A1.125 1.125 0 0 0 9.75 3.375V4.5m3 0h3"/>
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-brand">{{ __('app.frequencies.documents_section') }}</h2>
                            <p class="text-xs text-brand/70">{{ __('app.frequencies.documents_history') }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-5 sm:p-6 bg-[#f8fbf9] space-y-5">
                    @if ($allocation->currentLetter || $allocation->currentReceipt)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand/70 mb-3">{{ __('app.frequencies.documents_current') }}</p>
                            <div class="grid sm:grid-cols-2 gap-4">
                                @if ($allocation->currentLetter)
                                    <div class="rounded-xl border border-brand/20 bg-white p-4 shadow-sm">
                                        <div class="flex items-start gap-3">
                                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                                </svg>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-brand/70">{{ __('app.frequency_letters.title') }}</p>
                                                <p class="mt-1 font-semibold text-brand break-all">{{ $allocation->currentLetter->reference_number }}</p>
                                                <p class="mt-1 text-xs text-gray-500">{{ $allocation->currentLetter->issued_at->format('d M Y') }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <a href="{{ route('frequencies.letter.show', $allocation) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-brand text-white text-sm font-semibold hover:bg-brand-dark">{{ __('app.view') }}</a>
                                            <a href="{{ route('frequencies.letter.print', $allocation) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-brand/20 bg-brand/5 text-sm font-semibold text-brand hover:bg-brand/10">{{ __('app.approval_letters.print') }}</a>
                                        </div>
                                    </div>
                                @endif
                                @if ($allocation->currentReceipt)
                                    <div class="rounded-xl border border-amber-200/80 bg-white p-4 shadow-sm">
                                        <div class="flex items-start gap-3">
                                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-800">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25 6 12.75m3 1.5 3-1.5M9 9.75h6M4.5 9.75h15M5.25 6.75h13.5a1.5 1.5 0 0 1 1.5 1.5v9.75a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V8.25a1.5 1.5 0 0 1 1.5-1.5Z"/>
                                                </svg>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-amber-800/80">{{ __('app.frequency_receipts.title') }}</p>
                                                <p class="mt-1 font-semibold text-gray-900 break-all">{{ $allocation->currentReceipt->reference_number }}</p>
                                                <p class="mt-1 text-xs text-gray-500">{{ $allocation->currentReceipt->issued_at->format('d M Y') }} · {{ number_format(\App\Models\FrequencyRenewalReceipt::renewalFeeAmount()) }} {{ __('app.frequencies.revenue.currency') }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <a href="{{ route('frequencies.receipt.show', $allocation) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700">{{ __('app.view') }}</a>
                                            <a href="{{ route('frequencies.receipt.print', $allocation) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-amber-200 bg-amber-50 text-sm font-semibold text-amber-900 hover:bg-amber-100">{{ __('app.approval_letters.print') }}</a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @php
                        $historicalDocuments = $documentTimeline->filter(function (array $row) use ($allocation) {
                            if ($row['kind'] === 'letter' && $allocation->currentLetter && $row['document']->is($allocation->currentLetter)) {
                                return false;
                            }

                            if ($row['kind'] === 'receipt' && $allocation->currentReceipt && $row['document']->is($allocation->currentReceipt)) {
                                return false;
                            }

                            return true;
                        });
                    @endphp

                    @if ($historicalDocuments->isNotEmpty())
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand/70 mb-3">{{ __('app.frequencies.documents_history') }}</p>
                            <div class="space-y-4">
                                @foreach ($historicalDocuments->groupBy(fn (array $row) => $row['document']->issued_at->year) as $year => $yearDocuments)
                                    <div class="rounded-xl border border-gray-200/80 bg-white overflow-hidden">
                                        <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100 text-xs font-bold uppercase tracking-wider text-gray-500">{{ $year }}</div>
                                        <ul class="divide-y divide-gray-100">
                                            @foreach ($yearDocuments as $row)
                                                @php
                                                    $doc = $row['document'];
                                                    $docAllocation = $row['allocation'];
                                                    $isLetter = $row['kind'] === 'letter';
                                                @endphp
                                                <li class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-semibold uppercase tracking-wide {{ $isLetter ? 'text-brand/70' : 'text-amber-800/80' }}">
                                                            {{ $isLetter ? __('app.frequency_letters.title_short') : __('app.frequency_receipts.title_short') }}
                                                        </p>
                                                        <p class="mt-0.5 font-semibold text-gray-900 break-all">{{ $doc->reference_number }}</p>
                                                        <p class="mt-0.5 text-xs text-gray-500">{{ $doc->issued_at->format('d M Y') }} · {{ $docAllocation->band_label }}</p>
                                                    </div>
                                                    <div class="flex flex-wrap gap-2 shrink-0">
                                                        @if ($isLetter)
                                                            <a href="{{ route('frequencies.letter.show', $docAllocation) }}" class="text-sm font-medium text-brand hover:underline">{{ __('app.view') }}</a>
                                                            <a href="{{ route('frequencies.letter.print', $docAllocation) }}" target="_blank" class="text-sm font-medium text-brand hover:underline">{{ __('app.approval_letters.print') }}</a>
                                                        @else
                                                            <a href="{{ route('frequencies.receipt.show', $docAllocation) }}" class="text-sm font-medium text-amber-800 hover:underline">{{ __('app.view') }}</a>
                                                            <a href="{{ route('frequencies.receipt.print', $docAllocation) }}" target="_blank" class="text-sm font-medium text-amber-800 hover:underline">{{ __('app.approval_letters.print') }}</a>
                                                        @endif
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <section class="mt-5 data-card p-5 sm:p-6 space-y-4">
            <h2 class="text-sm font-semibold text-brand">{{ __('app.frequencies.details') }}</h2>
            <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">{{ __('app.frequencies.channels') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ $allocation->channel_details ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('app.frequencies.notes') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900 whitespace-pre-wrap">{{ $allocation->notes ?: '—' }}</dd>
                </div>
            </dl>
            @if ($allocation->renewedFrom)
                <p class="text-xs text-gray-500">
                    {{ __('app.frequencies.renewed_from', ['ref' => $allocation->renewedFrom->band_label.' · '.$allocation->renewedFrom->expires_at->format('d M Y')]) }}
                    @if ($allocation->renewedFrom->currentLetter)
                        · {{ $allocation->renewedFrom->currentLetter->reference_number }}
                    @endif
                </p>
            @endif
            @if ($allocation->documentList())
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ __('app.frequencies.documents') }}</p>
                    <ul class="mt-2 space-y-1">
                        @foreach ($allocation->documentList() as $document)
                            <li><a href="{{ $document['url'] }}" target="_blank" rel="noopener" class="text-sm font-medium text-brand hover:underline">{{ $document['name'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        @if ($allocation->renewals->isNotEmpty())
            <section class="mt-5 data-card p-5 sm:p-6">
                <h2 class="text-sm font-semibold text-brand">{{ __('app.frequencies.renewal_history') }}</h2>
                <ul class="mt-3 divide-y divide-gray-100">
                    @foreach ($allocation->renewals as $renewal)
                        <li class="py-2 flex items-center justify-between gap-3 text-sm">
                            <a href="{{ route('frequencies.show', $renewal) }}" class="font-medium text-brand hover:underline">{{ $renewal->expires_at->format('d M Y') }}</a>
                            <x-status-badge :value="$renewal->display_status" />
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @can('create', [App\Models\FrequencyAllocationLetter::class, $allocation])
            @unless ($allocation->currentLetter)
                <section class="mt-5 data-card p-5 text-center">
                    <p class="text-sm text-gray-600">{{ __('app.frequency_letters.none_yet') }}</p>
                    <form method="POST" action="{{ route('frequencies.letter.store', $allocation) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand-dark">
                            {{ __('app.frequency_letters.generate') }}
                        </button>
                    </form>
                </section>
            @endunless
        @endcan
    </div>
</x-app-layout>
