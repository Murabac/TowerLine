<x-app-layout>
    <div class="p-4 lg:p-8 space-y-5">
        @include('frequencies._header', [
            'current' => 'registry',
            'title' => __('app.frequencies.registry_title'),
            'subtitle' => __('app.frequencies.nav.registry_desc'),
        ])

        <div class="data-card">
            @if ($activeReceiptMonth)
                <div class="px-4 py-3 border-b border-brand/10 bg-brand/[0.04] text-sm text-brand">
                    {{ __('app.frequencies.filter_active_receipt_month', ['month' => $activeReceiptMonth->format('M Y')]) }}
                </div>
            @endif
            <form method="GET" class="data-toolbar">
                <select name="operator_id" class="field lg:w-48">
                    <option value="">{{ __('app.frequencies.operator') }}</option>
                    @foreach ($operators as $operator)
                        <option value="{{ $operator->id }}" @selected((string) request('operator_id') === (string) $operator->id)>{{ $operator->name }}</option>
                    @endforeach
                </select>
                <select name="state" class="field lg:w-48">
                    <option value="">{{ __('app.frequencies.state') }}</option>
                    @foreach (['active', 'expiring_soon', 'expiring_60', 'expired', 'outstanding'] as $state)
                        <option value="{{ $state }}" @selected(request('state') === $state)>{{ __('app.frequencies.state_'.$state) }}</option>
                    @endforeach
                </select>
                <select name="payment" class="field lg:w-48">
                    <option value="">{{ __('app.frequencies.filter_payment') }}</option>
                    @foreach (['with_receipt', 'this_year'] as $payment)
                        <option value="{{ $payment }}" @selected(request('payment') === $payment)>{{ __('app.frequencies.payment_'.$payment) }}</option>
                    @endforeach
                </select>
                <input type="month" name="receipt_month" value="{{ request('receipt_month') }}" class="field lg:w-44" title="{{ __('app.frequencies.filter_receipt_month') }}">
                <div class="flex items-center gap-2">
                    <button class="inline-flex items-center px-4 py-2 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand-dark">{{ __('app.filter') }}</button>
                    @if (request()->hasAny(['operator_id', 'state', 'payment', 'receipt_month']))
                        <a href="{{ route('frequencies.registry') }}" class="text-sm text-gray-500 hover:text-brand">{{ __('app.reset') }}</a>
                    @endif
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.frequencies.operator') }}</th>
                            <th>{{ __('app.frequencies.band') }}</th>
                            <th>{{ __('app.frequencies.range') }}</th>
                            <th>{{ __('app.frequencies.coverage') }}</th>
                            <th>{{ __('app.frequencies.expires') }}</th>
                            <th>{{ __('app.frequencies.state') }}</th>
                            <th>{{ __('app.frequency_letters.title_short') }}</th>
                            <th>{{ __('app.frequency_receipts.title_short') }}</th>
                            <th class="text-right">{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($allocations as $allocation)
                            <tr>
                                <td class="font-semibold text-gray-900">{{ $allocation->operator->name }}</td>
                                <td>{{ $allocation->band_label }}</td>
                                <td>{{ $allocation->frequency_range }}</td>
                                <td>{{ $allocation->coverageLabel() }}</td>
                                <td class="whitespace-nowrap">{{ $allocation->expires_at->format('d M Y') }}</td>
                                <td><x-status-badge :value="$allocation->display_status" /></td>
                                <td>
                                    @if ($allocation->currentLetter)
                                        <a href="{{ route('frequencies.letter.show', $allocation) }}" class="text-sm font-medium text-brand hover:underline">{{ $allocation->currentLetter->reference_number }}</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($allocation->currentReceipt)
                                        <a href="{{ route('frequencies.receipt.show', $allocation) }}" class="text-sm font-medium text-brand hover:underline">{{ $allocation->currentReceipt->reference_number }}</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="actions">
                                    <div class="inline-flex items-center gap-0.5">
                                        <a href="{{ route('frequencies.show', $allocation) }}" class="icon-btn" title="{{ __('app.view') }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                        </a>
                                        @can('update', $allocation)
                                            <a href="{{ route('frequencies.edit', $allocation) }}" class="icon-btn" title="{{ __('app.edit') }}">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L8.193 18.46a4.5 4.5 0 0 1-1.897 1.13L4.5 20.25l.66-1.796a4.5 4.5 0 0 1 1.13-1.897Z"/></svg>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="!py-16 text-center text-sm text-gray-500">
                                    @if ($activeReceiptMonth)
                                        {{ __('app.frequencies.empty_receipt_month', ['month' => $activeReceiptMonth->format('M Y')]) }}
                                    @else
                                        {{ __('app.frequencies.empty') }}
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($allocations->total())
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-gray-100 bg-gray-50/50">
                    <p class="text-sm text-gray-500">{{ $allocations->firstItem() }}–{{ $allocations->lastItem() }} / {{ $allocations->total() }}</p>
                    {{ $allocations->onEachSide(1)->links('pagination.towerline') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
