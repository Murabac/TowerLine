@php
    $allocation = $letter->allocation->loadMissing(['operator', 'region']);
    $director = \App\Support\MinistrySettings::approvalLetterDirector();
@endphp
<article class="approval-letter">
    <header class="approval-letter__header">
        <img src="{{ asset('images/mocit-logo.jpg') }}" alt="" class="approval-letter__logo">
        <p class="approval-letter__header-line approval-letter__header-line--so">Jamhuuriyadda Somaliland</p>
        <p class="approval-letter__header-line">Republic of Somaliland</p>
        <p class="approval-letter__header-line approval-letter__header-line--ministry">Wasaaradda Isgaadhsiinta iyo Teknoolojiyadda</p>
        <p class="approval-letter__header-line">Ministry of Communication and Technology</p>
        <p class="approval-letter__header-line approval-letter__header-line--dept">Waaxda Isgaadhsiinta</p>
    </header>

    <div class="approval-letter__meta">
        <p class="approval-letter__meta-item">
            <span class="approval-letter__meta-label">{{ __('app.approval_letters.ref_no') }}:</span>
            <span class="approval-letter__meta-value">{{ $letter->reference_number }}</span>
        </p>
        <p class="approval-letter__meta-item">
            <span class="approval-letter__meta-label">{{ __('app.approval_letters.date') }}:</span>
            <span class="approval-letter__meta-value">{{ $letter->issued_at->format('d/m/Y') }}</span>
        </p>
    </div>

    <div class="approval-letter__title-block">
        <h1 class="approval-letter__title">Warqadda Oggolaanshaha Frequenci</h1>
        <p class="approval-letter__subtitle">Frequency Allocation Approval Letter</p>
    </div>

    <div class="approval-letter__addressee">
        <p><strong>Ku:</strong> Shirkadda <span class="approval-letter__fill">{{ $allocation->operator->name }}</span></p>
        <p><strong>Ujeeddo:</strong> Oggolaanshaha Lahaanshaha Frequenci</p>
    </div>

    <p class="approval-letter__body">
        Wasaaradda Isgaadhsiinta iyo Teknoolojiyadda, iyada oo loo marayo Waaxda Isgaadhsiinta, waxay halkan ku caddaynaysaa
        in shirkadda kor ku xusan loo oggolaaday inay isticmaasho frequenci-yada hoos ku xusan si waafaqsan xeerarka
        isgaadhsiinta, shuruudaha qaranka iyo habraacyada Wasaaradda inta lagu jiro muddada lagu sheegay warqaddan.
    </p>

    <h2 class="approval-letter__section-title">Faahfaahinta Frequenci</h2>
    <table class="approval-letter__table">
        <thead>
            <tr>
                <th style="width:18%">Qodob</th>
                <th style="width:32%">Faahfaahin</th>
                <th>Xogta</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Shirkadda</td>
                <td>Magaca operator-ka</td>
                <td class="approval-letter__data">{{ $allocation->operator->name }}</td>
            </tr>
            <tr>
                <td>Baar / Band</td>
                <td>Magaca band-ka</td>
                <td class="approval-letter__data">{{ $allocation->band_label }}</td>
            </tr>
            <tr>
                <td>Kala duwanaansho</td>
                <td>MHz range / channels</td>
                <td class="approval-letter__data">{{ $allocation->frequency_range }}</td>
            </tr>
            <tr>
                <td>Faahfaahin</td>
                <td>Channel / ARFCN details</td>
                <td class="approval-letter__data">{{ $allocation->channel_details ?: '—' }}</td>
            </tr>
            <tr>
                <td>Dhulka</td>
                <td>Gobol ama qaranka oo dhan</td>
                <td class="approval-letter__data">{{ $allocation->coverageLabel() }}</td>
            </tr>
            <tr>
                <td>Muddada</td>
                <td>Bilow ilaa dhamaad</td>
                <td class="approval-letter__data">{{ $allocation->issued_at->format('d/m/Y') }} — {{ $allocation->expires_at->format('d/m/Y') }}</td>
            </tr>
        </tbody>
    </table>

    <h2 class="approval-letter__section-title">Shuruudaha iyo Mas'uuliyadda</h2>
    <ol class="approval-letter__conditions">
        <li>Shirkaddu waa inay isticmaashaa frequenci-yada kaliya inta lagu jiro muddada oggolaanshaha.</li>
        <li>Waa in laga fogaado faragelinta shirkadaha kale iyo adeegyada muhiimka ah ee qaranka.</li>
        <li>Isbeddel kasta oo muhiim ah waa in Wasaaradda lala socodsiiyo ka hor inta aan la hirgelin.</li>
        <li>Oggolaanshahan waa in la cusboonaysiiyaa sanad walba ka hor dhamaadka muddada.</li>
    </ol>

    <p class="approval-letter__closing">
        Sidaas darteed, shirkadda kor ku xusan waxaa loo oggolaaday inay isticmaasho frequenci-yada kor ku xusan ilaa {{ $allocation->expires_at->format('d/m/Y') }}.
    </p>

    <section>
        <h2 class="approval-letter__signatures-title">Ansixinta iyo Saxeexyada</h2>
        <div class="approval-letter__signature-box">
            @if (filled($director['name']))
                <p class="approval-letter__signature-name">{{ $director['name'] }}</p>
            @endif
            <p class="approval-letter__signature-title">{{ $director['title_so'] }}</p>
            <p class="approval-letter__signature-role">{{ $director['title_en'] }}</p>
            <div class="approval-letter__signature-space">{{ __('app.approval_letters.signature_placeholder') }}</div>
        </div>
    </section>

    <footer class="approval-letter__footer">
        Jamhuuriyadda Somaliland — Wasaaradda Isgaadhsiinta iyo Teknoolojiyadda
    </footer>
</article>
