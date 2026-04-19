<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klient zainteresowany ofertą</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f5; margin: 0; padding: 32px 0; }
        .wrapper { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .header { background: #0ea5e9; padding: 32px; color: #fff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 6px 0 0; opacity: .85; font-size: 14px; }
        .body { padding: 32px; color: #374151; line-height: 1.7; }
        .body h2 { margin: 0 0 12px; font-size: 18px; color: #111827; }
        .body p { margin: 0 0 14px; font-size: 15px; }
        .meta-grid { background: #f0f9ff; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .meta-grid dt { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px; }
        .meta-grid dd { font-size: 15px; color: #111827; font-weight: 600; margin: 0 0 12px; }
        .btn { display: inline-block; background: #0ea5e9; color: #fff !important; text-decoration: none; padding: 13px 32px; border-radius: 6px; font-weight: 600; margin: 20px 0; font-size: 15px; }
        .footer { padding: 20px 32px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>🔥 Klient zainteresowany ofertą</h1>
        <p>{{ $offer->business?->name ?? 'Website Expert' }} — panel CRM</p>
    </div>
    <div class="body">
        <h2>Cześć, {{ $recipient->name }},</h2>
        <p>Klient kliknął CTA w ofercie sprzedażowej i wyraził zainteresowanie rozmową.</p>

        <dl class="meta-grid">
            <dt>Klient</dt>
            <dd>{{ $offer->lead?->client?->company_name ?? $offer->lead?->client?->primary_contact_name ?? '—' }}</dd>

            <dt>Kontakt</dt>
            <dd>{{ $offer->lead?->client?->primary_contact_name ?? '—' }}
                @if($offer->lead?->client?->primary_contact_email)
                    ({{ $offer->lead->client->primary_contact_email }})
                @endif
            </dd>

            <dt>Oferta</dt>
            <dd>{{ $offer->title }}</dd>

            <dt>Język oferty</dt>
            <dd>{{ strtoupper($offer->language) }}</dd>

            <dt>Wysłana</dt>
            <dd>{{ $offer->sent_at?->format('d.m.Y H:i') ?? '—' }}</dd>
        </dl>

        <a href="{{ route('filament.admin.resources.leads.view', ['record' => $offer->lead_id]) }}" class="btn">
            Przejdź do leada →
        </a>

        <p style="font-size:13px;color:#6b7280;">Ten e-mail został wygenerowany automatycznie. Nie odpowiadaj na niego bezpośrednio.</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} {{ $offer->business?->name ?? 'Website Expert' }}. Wszelkie prawa zastrzeżone.
    </div>
</div>
</body>
</html>
