<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('finance.accountant_pack.export.title', [], 'en') }} — {{ $return->number }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.35; }
        h1 { font-size: 19px; margin: 0 0 3px; }
        h2 { border-bottom: 1px solid #cbd5e1; font-size: 12px; margin: 18px 0 7px; padding-bottom: 4px; }
        .classification { background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; font-weight: bold; margin: 10px 0 14px; padding: 7px; text-align: center; }
        .meta { border-collapse: collapse; width: 100%; }
        .meta td { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; vertical-align: top; }
        .meta td:first-child { color: #475569; font-weight: bold; width: 32%; }
        table.data { border-collapse: collapse; width: 100%; }
        table.data th, table.data td { border: 1px solid #cbd5e1; padding: 4px; text-align: right; }
        table.data th { background: #f1f5f9; color: #334155; }
        table.data th:first-child, table.data td:first-child, table.data th:nth-child(2), table.data td:nth-child(2) { text-align: left; }
        .hash { font-family: DejaVu Sans Mono, monospace; font-size: 7px; word-break: break-all; }
        .footer { color: #64748b; font-size: 7px; margin-top: 18px; }
    </style>
</head>
<body>
    <h1>{{ __('finance.accountant_pack.export.title', [], 'en') }}</h1>
    <div>{{ $business->name }} · {{ $return->number }}</div>
    <div class="classification">{{ __('finance.accountant_pack.export.classification', [], 'en') }}</div>

    <table class="meta">
        <tr><td>{{ __('finance.accountant_pack.export.return_number', [], 'en') }}</td><td>{{ $return->number }}</td></tr>
        <tr><td>{{ __('finance.accountant_pack.export.period', [], 'en') }}</td><td>{{ $return->period_from->toDateString() }} — {{ $return->period_to->toDateString() }}</td></tr>
        <tr><td>{{ __('finance.accountant_pack.export.status', [], 'en') }}</td><td>{{ strtoupper($return->status) }}</td></tr>
        <tr><td>{{ __('finance.accountant_pack.export.vat_scheme', [], 'en') }}</td><td>{{ data_get($snapshot, 'profile.vat_scheme', '—') }}</td></tr>
        <tr><td>{{ __('finance.accountant_pack.export.vat_last_four', [], 'en') }}</td><td>{{ data_get($snapshot, 'profile.vat_number_last_four', '—') }}</td></tr>
        <tr><td>{{ __('finance.accountant_pack.export.source_journals', [], 'en') }}</td><td>{{ $return->source_journal_count }}</td></tr>
        <tr><td>{{ __('finance.accountant_pack.export.source_lines', [], 'en') }}</td><td>{{ $return->source_line_count }}</td></tr>
        <tr><td>{{ __('finance.accountant_pack.export.warnings', [], 'en') }}</td><td>{{ $return->warning_count }}</td></tr>
        <tr><td>{{ __('finance.accountant_pack.export.generated_at', [], 'en') }}</td><td>{{ $generatedAt }}</td></tr>
        <tr><td>{{ __('finance.accountant_pack.export.generated_by', [], 'en') }}</td><td>{{ $actor->name }}</td></tr>
        <tr><td>{{ __('finance.accountant_pack.export.snapshot_hash', [], 'en') }}</td><td class="hash">{{ $return->snapshot_hash }}</td></tr>
    </table>

    <h2>{{ __('finance.accountant_pack.export.sheet_boxes', [], 'en') }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('finance.accountant_pack.export.box', [], 'en') }}</th>
                <th>{{ __('finance.accountant_pack.export.hmrc_field', [], 'en') }}</th>
                <th>{{ __('finance.accountant_pack.export.raw_gbp', [], 'en') }}</th>
                <th>{{ __('finance.accountant_pack.export.review_value', [], 'en') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach (data_get($snapshot, 'calculation.boxes', []) as $box)
                <tr>
                    <td>{{ $box['number'] }}</td>
                    <td>{{ $box['hmrc_field'] }}</td>
                    <td>{{ $box['raw_gbp'] }}</td>
                    <td>{{ $box['submission_value'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ __('finance.accountant_pack.export.review_checks', [], 'en') }}</h2>
    <table class="meta">
        @foreach (data_get($snapshot, 'calculation.checks', []) as $check => $passed)
            <tr><td>{{ str_replace('_', ' ', ucfirst($check)) }}</td><td>{{ $passed ? 'PASS' : 'FAIL' }}</td></tr>
        @endforeach
    </table>

    <div class="footer">
        {{ __('finance.accountant_pack.export.footer', [], 'en') }}
    </div>
</body>
</html>
