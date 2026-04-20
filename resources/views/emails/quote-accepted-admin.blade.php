<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wycena zaakceptowana</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f5; margin: 0; padding: 32px 0; }
        .wrapper { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .header { background: linear-gradient(135deg, #059669, #0d9488); padding: 32px; color: #fff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 6px 0 0; opacity: .85; font-size: 14px; }
        .body { padding: 32px; color: #374151; line-height: 1.7; }
        .body h2 { margin: 0 0 12px; font-size: 18px; color: #111827; }
        .body p { margin: 0 0 14px; font-size: 15px; }
        .meta-grid { background: #ecfdf5; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .meta-grid dt { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px; }
        .meta-grid dd { font-size: 15px; color: #111827; font-weight: 600; margin: 0 0 12px; }
        .btn { display: inline-block; background: #059669; color: #fff !important; text-decoration: none; padding: 13px 32px; border-radius: 6px; font-weight: 600; margin: 20px 0; font-size: 15px; }
        .footer { padding: 20px 32px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>✅ Wycena zaakceptowana</h1>
        <p>{{ config('app.name', 'Website Expert') }} — panel CRM</p>
    </div>
    <div class="body">
        <h2>Cześć, {{ $recipient->name }},</h2>
        <p>Klient zaakceptował wycenę w portalu klienta. Kontrakt został automatycznie utworzony i wysłany do klienta.</p>

        <dl class="meta-grid">
            <dt>Klient</dt>
            <dd>{{ $quote->client?->company_name ?? $quote->client?->primary_contact_name ?? '—' }}</dd>

            <dt>Kontakt</dt>
            <dd>{{ $quote->client?->primary_contact_name ?? '—' }}
                @if($quote->client?->primary_contact_email)
                    ({{ $quote->client->primary_contact_email }})
                @endif
            </dd>

            <dt>Numer wyceny</dt>
            <dd>{{ $quote->number }}</dd>

            <dt>Wartość</dt>
            <dd>{{ strtoupper($quote->currency ?? 'GBP') }} {{ number_format($quote->total, 2) }}</dd>

            <dt>Data akceptacji</dt>
            <dd>{{ $quote->accepted_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i') }}</dd>
        </dl>

        @if($quote->lead_id)
            <a href="{{ route('filament.admin.resources.leads.view', $quote->lead_id) }}" class="btn">
                Zobacz leada →
            </a>
        @endif
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name', 'Website Expert') }}. Wszelkie prawa zastrzeżone.
    </div>
</div>
</body>
</html>
