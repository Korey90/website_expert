<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $document['title'] }}</title>
    <style>
        @page { margin: 26px 34px 34px; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        .watermark {
            position: fixed; top: 43%; left: 9%; width: 82%; text-align: center;
            transform: rotate(-30deg); font-size: 37px; font-weight: 700;
            color: rgba(127, 29, 29, .07); letter-spacing: 3px; z-index: -1;
        }
        .demo-bar { padding: 7px 12px; background: #7f1d1d; color: #fff; text-align: center; font-size: 9px; font-weight: 700; letter-spacing: 1px; }
        .header { display: table; width: 100%; padding: 20px 0 15px; border-bottom: 3px solid #7C7072; }
        .header-left, .header-right { display: table-cell; vertical-align: top; }
        .header-left { width: 66%; }
        .header-right { width: 34%; text-align: right; }
        .company { color: #51373B; font-size: 18px; font-weight: 700; }
        .company-meta { margin-top: 4px; color: #64748b; font-size: 9px; line-height: 1.55; }
        h1 { margin: 0; color: #0f172a; font-size: 22px; line-height: 1.15; }
        .subtitle { margin-top: 5px; color: #64748b; font-size: 10px; }
        .badge { display: inline-block; margin-top: 8px; padding: 4px 9px; border-radius: 12px; background: #e2e8f0; color: #334155; font-size: 8px; font-weight: 700; letter-spacing: .6px; }
        .badge.current { background: #dcfce7; color: #166534; }
        .badge.preview { background: #fef3c7; color: #92400e; }
        .badge.external { background: #dbeafe; color: #1e40af; }
        .meta-grid { display: table; width: 100%; margin: 15px 0 18px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .meta-item { display: table-cell; padding: 10px; border-right: 1px solid #e2e8f0; }
        .meta-item:last-child { border-right: none; }
        .label { color: #94a3b8; font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; }
        .value { margin-top: 4px; font-size: 10px; font-weight: 700; }
        .intro { margin: 0 0 13px; padding: 10px 12px; border-left: 3px solid #7C7072; background: #f8fafc; color: #475569; line-height: 1.5; }
        .section { margin: 0 0 16px; page-break-inside: avoid; }
        .section.allow-break { page-break-inside: auto; }
        h2 { margin: 0 0 7px; padding-bottom: 4px; border-bottom: 1px solid #cbd5e1; color: #51373B; font-size: 12px; }
        p { margin: 4px 0; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 6px 7px; background: #7C7072; color: #fff; font-size: 8px; text-align: left; text-transform: uppercase; }
        td { padding: 5px 7px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        td.right, th.right { text-align: right; }
        tr.subtotal td { font-weight: 700; background: #f1f5f9; border-top: 1px solid #94a3b8; }
        tr.total td { font-weight: 800; color: #51373B; background: #ede9e9; border-top: 2px solid #7C7072; }
        tr.muted td { color: #64748b; }
        .notes { margin-top: 12px; padding: 10px 12px; background: #fff7ed; border: 1px solid #fed7aa; color: #7c2d12; }
        .notes strong { display: block; margin-bottom: 4px; }
        .notes ul { margin: 3px 0 0 15px; padding: 0; }
        .notes li { margin-bottom: 3px; }
        .source { margin-top: 12px; color: #64748b; font-size: 8px; line-height: 1.4; }
        .footer { position: fixed; bottom: -23px; left: 0; right: 0; border-top: 1px solid #e2e8f0; padding-top: 6px; color: #94a3b8; font-size: 7px; text-align: center; }
    </style>
</head>
<body>
<div class="watermark">DEMO / NOT FOR SUBMISSION</div>
<div class="demo-bar">DEMONSTRATION ONLY · FICTIONAL TRANSACTIONS · NOT FOR SUBMISSION TO HMRC OR COMPANIES HOUSE</div>

<div class="header">
    <div class="header-left">
        <div class="company">{{ $company['name'] }}</div>
        <div class="company-meta">
            @if($company['company_number'])Company no. {{ $company['company_number'] }}@endif
            @if($company['company_number'] && $company['vat_number']) · @endif
            @if($company['vat_number'])VAT registration no. {{ $company['vat_number'] }}@endif
            @if($company['company_number'] || $company['vat_number'])<br>@endif
            {{ $company['address_inline'] }}
        </div>
    </div>
    <div class="header-right">
        <h1>{{ $document['title'] }}</h1>
        @if(!empty($document['subtitle']))<div class="subtitle">{{ $document['subtitle'] }}</div>@endif
        <span class="badge {{ $document['badge_tone'] ?? '' }}">{{ $document['classification'] }}</span>
    </div>
</div>

@if(!empty($document['metadata']))
<div class="meta-grid">
    @foreach($document['metadata'] as $item)
        <div class="meta-item">
            <div class="label">{{ $item['label'] }}</div>
            <div class="value">{{ $item['value'] }}</div>
        </div>
    @endforeach
</div>
@endif

@if(!empty($document['intro']))<div class="intro">{{ $document['intro'] }}</div>@endif

@foreach($document['sections'] ?? [] as $section)
<div class="section {{ ($section['allow_break'] ?? false) ? 'allow-break' : '' }}">
    <h2>{{ $section['heading'] }}</h2>
    @foreach($section['paragraphs'] ?? [] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
    @if(!empty($section['table']))
        @php($rightColumns = $section['table']['right_columns'] ?? [])
        <table>
            <thead>
            <tr>
                @foreach($section['table']['headers'] as $index => $header)
                    <th class="{{ in_array($index, $rightColumns, true) ? 'right' : '' }}">{{ $header }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($section['table']['rows'] as $row)
                <tr class="{{ $row['type'] ?? '' }}">
                    @foreach($row['cells'] as $index => $cell)
                        <td class="{{ in_array($index, $rightColumns, true) ? 'right' : '' }}">{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endforeach

@if(!empty($document['notes']))
<div class="notes">
    <strong>Reviewer notes</strong>
    <ul>@foreach($document['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul>
</div>
@endif

@if(!empty($document['sources']))
<div class="source">
    <strong>Reference basis:</strong> {{ implode(' · ', $document['sources']) }}
</div>
@endif

<div class="footer">
    {{ $company['name'] }} · UK accountant review pack · generated 5 November 2026 · {{ $document['title'] }}
</div>
</body>
</html>
