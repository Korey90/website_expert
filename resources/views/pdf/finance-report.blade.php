<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document['title'] }}</title>
    <style>
        @page { margin: 24px 24px 34px; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { color: #17365d; font-size: 20px; margin: 0 0 4px; }
        .business { font-size: 12px; font-weight: bold; margin-bottom: 10px; }
        .meta { border-collapse: collapse; margin-bottom: 14px; width: auto; }
        .meta td { border: 0; padding: 2px 10px 2px 0; }
        .meta td:first-child { color: #6b7280; font-weight: bold; }
        h2 { background: #d9eaf7; color: #17365d; font-size: 11px; margin: 14px 0 0; padding: 6px; }
        table.report { border-collapse: collapse; page-break-inside: auto; width: 100%; }
        .report th { background: #4472c4; border: 1px solid #31558f; color: #fff; font-size: 7px; padding: 4px 3px; text-align: left; }
        .report td { border: 1px solid #d1d5db; padding: 3px; vertical-align: top; word-break: break-word; }
        .report tr { page-break-inside: avoid; }
        .report tr.subtotal td { background: #ededed; font-weight: bold; }
        .report tr.total td { background: #d9ead3; font-weight: bold; }
        .numeric { text-align: right; white-space: nowrap; }
        .negative { color: #b91c1c; }
        .empty { color: #6b7280; font-style: italic; padding: 12px !important; text-align: center; }
        .footer { bottom: -24px; color: #6b7280; font-size: 7px; left: 0; position: fixed; right: 0; text-align: center; }
        .page-break { page-break-before: auto; }
    </style>
</head>
<body>
@php
    $formatValue = static function (mixed $value, string $type): string {
        if ($value === null || $value === '') return '';
        if (! is_numeric($value)) return (string) $value;
        return match ($type) {
            'money' => '£'.number_format((float) $value, 2),
            'number' => number_format((float) $value, 2),
            'integer' => number_format((float) $value, 0),
            'percent' => number_format((float) $value, 2).'%',
            default => (string) $value,
        };
    };
@endphp

<h1>{{ $document['title'] }}</h1>
<div class="business">{{ $document['business_name'] }}</div>
<table class="meta">
    <tr><td>{{ __('finance.exports.generated_at') }}</td><td>{{ $document['generated_at'] }}</td></tr>
    @foreach ($document['metadata'] as $label => $value)
        <tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>
    @endforeach
</table>

@foreach ($document['tables'] as $table)
    @if (filled($table['title'] ?? null))
        <h2>{{ $table['title'] }}</h2>
    @endif
    <table class="report">
        <thead>
        <tr>
            @foreach ($table['columns'] as $column)
                <th class="{{ ($column['type'] ?? 'text') !== 'text' ? 'numeric' : '' }}">{{ $column['label'] }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @forelse ($table['rows'] as $row)
            <tr class="{{ $row['style'] ?? 'data' }}">
                @foreach ($table['columns'] as $index => $column)
                    @php
                        $value = $row['values'][$index] ?? null;
                        $type = $column['type'] ?? 'text';
                    @endphp
                    <td class="{{ $type !== 'text' ? 'numeric' : '' }} {{ is_numeric($value) && (float) $value < 0 ? 'negative' : '' }}">
                        {{ $formatValue($value, $type) }}
                    </td>
                @endforeach
            </tr>
        @empty
            <tr><td class="empty" colspan="{{ count($table['columns']) }}">{{ __('finance.exports.no_rows') }}</td></tr>
        @endforelse
        </tbody>
    </table>
@endforeach

<div class="footer">{{ $document['business_name'] }} | {{ $document['title'] }}</div>
</body>
</html>
