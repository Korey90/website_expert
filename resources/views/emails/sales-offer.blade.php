<!DOCTYPE html>
<html lang="{{ $offer->language ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $offer->title }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f5; margin: 0; padding: 32px 0; }
        .wrapper { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .header { background: #0ea5e9; padding: 32px; color: #fff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 6px 0 0; opacity: .85; font-size: 14px; }
        .body { padding: 32px; color: #374151; line-height: 1.7; }
        .body h2 { margin: 0 0 12px; font-size: 18px; color: #111827; }
        .body p { margin: 0 0 14px; font-size: 15px; }
        .offer-preview { background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 16px 20px; border-radius: 4px; margin: 20px 0; color: #0369a1; font-size: 14px; line-height: 1.6; }
        .btn { display: inline-block; background: #0ea5e9; color: #fff !important; text-decoration: none; padding: 13px 32px; border-radius: 6px; font-weight: 600; margin: 20px 0; font-size: 15px; }
        .footer { padding: 20px 32px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>{{ $offer->business?->name ?? 'Website Expert' }}</h1>
        <p>{{ $offer->language === 'pl' ? 'Oferta dla:' : 'Offer for:' }} {{ $offer->lead?->client?->company_name ?? $offer->lead?->client?->primary_contact_name ?? '' }}</p>
    </div>
    <div class="body">
        <h2>
            @if($offer->language === 'pl')
                Witaj, {{ $offer->lead?->client?->primary_contact_name ?? '' }},
            @else
                Hello, {{ $offer->lead?->client?->primary_contact_name ?? '' }},
            @endif
        </h2>

        @if($offer->language === 'pl')
            <p>Przygotowaliśmy dla Ciebie indywidualną ofertę. Kliknij poniższy przycisk, aby zapoznać się z pełną wersją.</p>
        @else
            <p>We've prepared a personalised offer for you. Click the button below to view the full version.</p>
        @endif

        <div class="offer-preview">
            <strong>{{ $offer->title }}</strong>
        </div>

        <a href="{{ url('/offers/' . $offer->client_token) }}" class="btn">
            {{ $offer->language === 'pl' ? 'Otwórz pełną ofertę' : 'View Full Offer' }}
        </a>

        @if($offer->language === 'pl')
            <p style="font-size:13px;color:#6b7280;">Masz pytania? Odpowiedz na tę wiadomość — chętnie porozmawiamy.</p>
        @else
            <p style="font-size:13px;color:#6b7280;">Got questions? Simply reply to this email — we're happy to chat.</p>
        @endif
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} {{ $offer->business?->name ?? 'Website Expert' }}. All rights reserved.
    </div>
</div>
</body>
</html>
