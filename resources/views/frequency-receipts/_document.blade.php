@php
    $allocation = $receipt->allocation->loadMissing(['operator', 'region', 'renewedFrom']);
    $feeAmount = \App\Models\FrequencyRenewalReceipt::renewalFeeAmount();
    $feeFormatted = number_format($feeAmount).' SLSH';
@endphp
<article class="frq-receipt">
    <header class="frq-receipt__header">
        <img src="{{ asset('images/mocit-logo.jpg') }}" alt="" class="frq-receipt__logo">
        <div class="frq-receipt__issuer">
            <p class="frq-receipt__issuer-line">Jamhuuriyadda Somaliland</p>
            <p class="frq-receipt__issuer-line frq-receipt__issuer-line--main">Wasaaradda Isgaadhsiinta iyo Teknoolojiyadda</p>
            <p class="frq-receipt__issuer-line">Ministry of Communication &amp; Technology</p>
        </div>
    </header>

    <div class="frq-receipt__badge">
        {{ __('app.frequency_receipts.official_receipt') }}
        <span class="frq-receipt__badge-sub">{{ __('app.frequency_receipts.title_en') }}</span>
    </div>

    <div class="frq-receipt__meta">
        <p class="frq-receipt__meta-item">
            <span class="frq-receipt__meta-label">{{ __('app.frequency_receipts.receipt_no') }}</span>
            <span class="frq-receipt__meta-value">{{ $receipt->reference_number }}</span>
        </p>
        <p class="frq-receipt__meta-item">
            <span class="frq-receipt__meta-label">{{ __('app.approval_letters.date') }}</span>
            <span class="frq-receipt__meta-value">{{ $receipt->issued_at->format('d/m/Y') }}</span>
        </p>
    </div>

    <div class="frq-receipt__party">
        <p class="frq-receipt__party-label">{{ __('app.frequency_receipts.received_from') }}</p>
        <p class="frq-receipt__party-name">{{ $allocation->operator->name }}</p>
    </div>

    <table class="frq-receipt__lines">
        <thead>
            <tr>
                <th>{{ __('app.frequency_receipts.item') }}</th>
                <th>{{ __('app.frequency_receipts.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ __('app.frequency_receipts.fee_title') }}</strong>
                    <br>
                    <span style="font-size:8.5pt;color:#6b7280;">{{ $allocation->band_label }} · {{ $allocation->coverageLabel() }}</span>
                </td>
                <td class="frq-receipt__amount">{{ $feeFormatted }}</td>
            </tr>
        </tbody>
    </table>

    <div class="frq-receipt__total">
        <p class="frq-receipt__total-label">{{ __('app.frequency_receipts.total_paid') }}</p>
        <p class="frq-receipt__total-amount">{{ $feeFormatted }}</p>
    </div>

    <p class="frq-receipt__paid-stamp"><span>{{ __('app.frequency_receipts.paid') }}</span></p>

    <section class="frq-receipt__details">
        <h2 class="frq-receipt__details-title">{{ __('app.frequency_receipts.allocation_details') }}</h2>
        <dl class="frq-receipt__details-grid">
            <div class="frq-receipt__detail">
                <dt>{{ __('app.frequencies.band') }}</dt>
                <dd>{{ $allocation->band_label }}</dd>
            </div>
            <div class="frq-receipt__detail">
                <dt>{{ __('app.frequencies.range') }}</dt>
                <dd>{{ $allocation->frequency_range }}</dd>
            </div>
            <div class="frq-receipt__detail">
                <dt>{{ __('app.frequency_receipts.valid_period') }}</dt>
                <dd>{{ $allocation->issued_at->format('d/m/Y') }} — {{ $allocation->expires_at->format('d/m/Y') }}</dd>
            </div>
            @if ($receipt->letter)
                <div class="frq-receipt__detail">
                    <dt>{{ __('app.frequency_receipts.linked_letter') }}</dt>
                    <dd>{{ $receipt->letter->reference_number }}</dd>
                </div>
            @endif
        </dl>
    </section>

    <div class="frq-receipt__signatures">
        <div class="frq-receipt__sign-box">
            <p class="frq-receipt__sign-label">{{ __('app.frequency_receipts.cashier') }}</p>
            <p class="frq-receipt__sign-name">{{ $receipt->issuer?->name ?: __('app.approval_letters.system') }}</p>
        </div>
        <div class="frq-receipt__sign-box">
            <p class="frq-receipt__sign-label">{{ __('app.frequency_receipts.operator_rep') }}</p>
            <p class="frq-receipt__sign-name">&nbsp;</p>
        </div>
    </div>

    <footer class="frq-receipt__footer">
        {{ __('app.frequency_receipts.footer') }}
    </footer>
</article>
