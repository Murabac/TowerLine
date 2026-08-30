<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $letter->reference_number }} — {{ $allocation->operator->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/approval-letter.css') }}">
    <style>
        body.approval-letter-print-body { margin: 0; background: #eef2f0; font-family: system-ui, sans-serif; }
        .approval-letter-print-toolbar { position: sticky; top: 0; z-index: 10; border-bottom: 1px solid #d1d5db; background: rgba(255,255,255,.96); padding: 12px 16px; }
        .approval-letter-print-toolbar__inner { max-width: 210mm; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .approval-letter-print-toolbar p { margin: 0; font-size: 14px; color: #374151; }
        .approval-letter-print-toolbar button { border: 0; border-radius: 10px; background: #1b4d3e; color: #fff; font-size: 14px; font-weight: 600; padding: 10px 16px; cursor: pointer; }
        .approval-letter-print-page { margin: 24px auto; box-shadow: 0 10px 40px rgba(0,0,0,.12); }
    </style>
</head>
<body class="approval-letter-print-body">
    <div class="approval-letter-print-toolbar">
        <div class="approval-letter-print-toolbar__inner">
            <p>{{ __('app.approval_letters.print_hint') }}</p>
            <button type="button" onclick="window.print()">{{ __('app.approval_letters.print') }}</button>
        </div>
    </div>
    <main class="approval-letter-print-page">
        @include('frequency-letters._document', ['letter' => $letter])
    </main>
</body>
</html>
