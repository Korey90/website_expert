<!DOCTYPE html>
<html lang="{{ $offer->language ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $offer->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.6; }

        .header { border-bottom: 2px solid #0ea5e9; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { font-size: 16pt; color: #0c4a6e; }
        .header .meta { margin-top: 8px; font-size: 9pt; color: #6b7280; }
        .header .meta span { margin-right: 20px; }

        .meta-grid { display: table; width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .meta-grid .cell { display: table-cell; width: 25%; padding: 6px 8px; background: #f0f9ff; border: 1px solid #bae6fd; vertical-align: top; }
        .meta-grid .cell .label { font-size: 7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #0369a1; }
        .meta-grid .cell .value { font-size: 9pt; color: #1f2937; margin-top: 2px; }

        .body-section { margin-bottom: 20px; }
        .body-section p { margin-bottom: 8px; font-size: 10pt; }
        .body-section h1, .body-section h2, .body-section h3 { color: #0c4a6e; margin: 12px 0 6px; }
        .body-section h2 { font-size: 12pt; border-bottom: 1px solid #e0f2fe; padding-bottom: 3px; }
        .body-section h3 { font-size: 11pt; }
        .body-section ul, .body-section ol { margin-left: 18px; margin-bottom: 8px; }
        .body-section li { margin-bottom: 3px; }
        .body-section blockquote { border-left: 3px solid #0ea5e9; padding-left: 10px; color: #374151; margin: 8px 0; }
        .body-section code { background: #f3f4f6; padding: 1px 4px; border-radius: 3px; font-size: 9pt; }
        .body-section pre { background: #f3f4f6; padding: 8px; border-radius: 4px; margin: 8px 0; font-size: 8.5pt; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 9999px; font-size: 7pt; font-weight: bold; }
        .badge-draft     { background: #f3f4f6; color: #374151; }
        .badge-sent      { background: #e0f2fe; color: #0369a1; }
        .badge-viewed    { background: #e0e7ff; color: #3730a3; }
        .badge-converted { background: #d1fae5; color: #065f46; }

        .footer { border-top: 1px solid #e5e7eb; margin-top: 30px; padding-top: 8px; font-size: 7.5pt; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $offer->title }}</h1>
        <div class="meta">
            <span>Lead: {{ $offer->lead->title ?? '—' }}</span>
            <span>Client: {{ $offer->lead?->client?->company_name ?? $offer->lead?->client?->primary_contact_name ?? '—' }}</span>
            <span>Date: {{ ($offer->sent_at ?? $offer->created_at)?->format('d/m/Y') }}</span>
        </div>
    </div>

    <div class="meta-grid">
        <div class="cell">
            <div class="label">Status</div>
            <div class="value">
                <span class="badge badge-{{ $offer->status }}">{{ ucfirst($offer->status) }}</span>
            </div>
        </div>
        <div class="cell">
            <div class="label">Language</div>
            <div class="value">{{ strtoupper($offer->language) }}</div>
        </div>
        <div class="cell">
            <div class="label">Created by</div>
            <div class="value">{{ $offer->createdBy->name ?? '—' }}</div>
        </div>
        <div class="cell">
            <div class="label">{{ $offer->language === 'pl' ? 'Wysłano' : 'Sent' }}</div>
            <div class="value">{{ $offer->sent_at?->format('d/m/Y H:i') ?? '—' }}</div>
        </div>
    </div>

    <div class="body-section">
        {!! \Illuminate\Support\Str::markdown($offer->body ?? '') !!}
    </div>

    @if($offer->notes)
        <div style="background:#fef9c3;border:1px solid #fde047;padding:10px 12px;margin-top:16px;border-radius:4px;">
            <div style="font-size:9pt;font-weight:bold;color:#854d0e;margin-bottom:4px;">
                {{ $offer->language === 'pl' ? 'Notatki wewnętrzne' : 'Internal Notes' }}
            </div>
            <p style="font-size:8.5pt;color:#713f12;">{{ $offer->notes }}</p>
        </div>
    @endif

    <div class="footer">
        {{ $offer->business?->name ?? 'Website Expert' }} &mdash;
        {{ ($offer->sent_at ?? $offer->created_at)?->format('d/m/Y') }} &mdash;
        Confidential
    </div>
</body>
</html>
