@php
    $tower = $letter->tower->loadMissing(['region', 'district', 'subDistrict', 'operator']);
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
        <h1 class="approval-letter__title">Warqadda Oggolaanshaha</h1>
        <p class="approval-letter__subtitle">Tower Registration Approval Letter</p>
    </div>

    <div class="approval-letter__addressee">
        <p><strong>Ku:</strong> Shirkadda <span class="approval-letter__fill">{{ $tower->operator->name }}</span></p>
        <p><strong>Ujeeddo:</strong> Oggolaanshaha Diiwaangelinta Taawarka</p>
    </div>

    <p class="approval-letter__body">
        Wasaaradda Isgaadhsiinta iyo Teknoolojiyadda, iyada oo loo marayo Waaxda Isgaadhsiinta, waxay halkan ku caddaynaysaa
        in kadib dib-u-eegista xogta goobta iyo warbixinta kormeerka, la oggolaaday in taawarka hoos ku xusan loo diiwaangeliyo si
        waafaqsan xeerarka, shuruudaha iyo habraacyada Wasaaradda.
    </p>

    <h2 class="approval-letter__section-title">Faahfaahinta Taawarka iyo Goobta</h2>
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
                <td>Magaca shirkadda / operator-ka</td>
                <td class="approval-letter__data">{{ $tower->operator->name }}</td>
            </tr>
            <tr>
                <td>Goobta</td>
                <td>Gobolka / Degmada / Magaalada / Xaafadda</td>
                <td class="approval-letter__data">{{ $tower->letterLocationLine() ?: '—' }}</td>
            </tr>
            <tr>
                <td>GPS</td>
                <td>Coordinates-ka goobta</td>
                <td class="approval-letter__data approval-letter__mono">{{ number_format($tower->latitude, 6) }}, {{ number_format($tower->longitude, 6) }}</td>
            </tr>
            <tr>
                <td>Cabbirka Dhulka</td>
                <td>Baaxadda goobta</td>
                <td class="approval-letter__data">{{ $tower->land_area ?: '—' }}</td>
            </tr>
            <tr>
                <td>Joogga Taawarka</td>
                <td>Dhererka taawarka</td>
                <td class="approval-letter__data">{{ rtrim(rtrim(number_format($tower->height_m, 1), '0'), '.') }} m</td>
            </tr>
            <tr>
                <td>Aqoonsiga Taawarka</td>
                <td>Nooca / Tower ID</td>
                <td class="approval-letter__data">{{ $tower->letterTowerIdentity() }}</td>
            </tr>
        </tbody>
    </table>

    <h2 class="approval-letter__section-title">Statuska Oggolaanshaha</h2>
    <div class="approval-letter__status-grid">
        <div class="approval-letter__status-option">
            <span class="approval-letter__checkbox {{ $letter->isApproved() ? 'approval-letter__checkbox--checked' : '' }}">
                @if ($letter->isApproved()) ✓ @endif
            </span>
            <span>WAA LA OGGOLAADAY</span>
        </div>
        <div class="approval-letter__status-option">
            <span class="approval-letter__checkbox {{ ! $letter->isApproved() ? 'approval-letter__checkbox--checked' : '' }}">
                @if (! $letter->isApproved()) ✓ @endif
            </span>
            <span>WAA LA DIIDAY</span>
        </div>
    </div>

    <p class="approval-letter__note">Go'aankan wuxuu ku salaysan yahay xogta goobta, kormeerka iyo shuruudaha Wasaaradda.</p>

    <h2 class="approval-letter__section-title">Shuruudaha iyo Mas'uuliyadda</h2>
    <ol class="approval-letter__conditions">
        <li>Taawarku waa inuu ku yaallaa goobta iyo xogta lagu sheegay warqaddan.</li>
        <li>Shirkaddu waa inay u hoggaansantaa shuruudaha badbaadada iyo xeerarka isgaadhsiinta.</li>
        <li>Isbeddel kasta oo muhiim ah oo lagu sameeyo goobta ama taawarka waa in Wasaaradda lala socodsiiyo.</li>
        <li>Warqaddani ma beddelayso ruqsad ama oggolaansho kale oo sharci ahaan looga baahan yahay hay'adaha ay khusayso.</li>
    </ol>

    <p class="approval-letter__closing">
        Sidaas darteed, taawarka kor ku xusan waxaa loo oggolaaday in lagu diiwaangeliyo Diiwaanka Taawarrada ee Waaxda Isgaadhsiinta.
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
