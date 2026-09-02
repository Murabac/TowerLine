@php
    $itemKey = 'app.reports.items.'.$report->langKey();
    $titleSo = trans($itemKey, [], 'so');
    $titleEn = trans($itemKey, [], 'en');
@endphp

<style>
    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
    }
</style>

<div class="flex flex-wrap items-end justify-between gap-4 mb-5 no-print">
    <div>
        <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-brand">{{ __('app.reports.title') }}</a>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-brand">{{ $report->title() }}</h1>
        @if ($report->summary())
            <p class="mt-1 text-sm text-gray-500">{{ $report->summary() }}</p>
        @endif
    </div>
    <div class="flex items-center gap-2">
        @if (auth()->user()->canTask('reports.print') || auth()->user()->canTask('reports.view'))
            <button type="button" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold" onclick="window.print()">{{ __('app.reports.print') }}</button>
        @endif
        @if (auth()->user()->canTask('reports.export_excel'))
            <a href="{{ route('reports.excel', array_filter(['report' => $report->key, ...$filters])) }}" class="px-4 py-2 bg-brand text-white rounded-xl text-sm font-semibold hover:bg-brand-dark">{{ __('app.reports.excel') }}</a>
        @endif
    </div>
</div>

<div class="report-letterhead">
    <x-ministry-letterhead />

    <div class="report-letterhead__meta">
        <p class="report-letterhead__meta-item">
            <span class="report-letterhead__meta-label">{{ __('app.approval_letters.date') }}:</span>
            <span class="report-letterhead__meta-value">{{ now()->format('d/m/Y') }}</span>
        </p>
        <p class="report-letterhead__meta-item">
            <span class="report-letterhead__meta-label">{{ __('app.reports.printed_by') }}:</span>
            <span class="report-letterhead__meta-value">{{ auth()->user()->name }}</span>
        </p>
    </div>

    <div class="report-letterhead__title-block">
        <p class="report-letterhead__doc-type">{{ __('app.reports.official_document') }}</p>
        <h1 class="report-letterhead__title">{{ $titleSo }}</h1>
        @if ($titleEn !== $titleSo)
            <p class="report-letterhead__subtitle">{{ $titleEn }}</p>
        @endif
    </div>
</div>
