<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $receipt->reference_number }} — {{ $allocation->operator->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/frequency-receipt.css') }}">
    <style>
        body.frq-receipt-print-body { margin: 0; background: #eef2f0; font-family: system-ui, sans-serif; }
        .frq-receipt-print-toolbar { position: sticky; top: 0; z-index: 10; border-bottom: 1px solid #d1d5db; background: rgba(255,255,255,.96); padding: 12px 16px; }
        .frq-receipt-print-toolbar__inner { max-width: 118mm; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .frq-receipt-print-toolbar p { margin: 0; font-size: 14px; color: #374151; }
        .frq-receipt-print-toolbar button { border: 0; border-radius: 10px; background: #1b4d3e; color: #fff; font-size: 14px; font-weight: 600; padding: 10px 16px; cursor: pointer; }
        .frq-receipt-print-page { margin: 24px auto; max-width: 118mm; box-shadow: 0 10px 40px rgba(0,0,0,.12); }
    </style>
</head>
<body class="frq-receipt-print-body">
    <div class="frq-receipt-print-toolbar">
        <div class="frq-receipt-print-toolbar__inner">
            <p>{{ __('app.frequency_receipts.print_hint') }}</p>
            <button type="button" onclick="window.print()">{{ __('app.approval_letters.print') }}</button>
        </div>
    </div>
    <main class="frq-receipt-print-page">
        @include('frequency-receipts._document', ['receipt' => $receipt])
    </main>
</body>
</html>
