<!DOCTYPE html>
<html lang="{{ $offer->language ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $offer->language === 'pl' ? 'Potwierdzenie zainteresowania' : 'Interest Confirmed' }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f5; margin: 0; padding: 32px 0; }
        .wrapper { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .header { background: linear-gradient(135deg, #059669, #0d9488); padding: 32px; color: #fff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 6px 0 0; opacity: .85; font-size: 14px; }
        .body { padding: 32px; color: #374151; line-height: 1.7; }
        .body h2 { margin: 0 0 12px; font-size: 18px; color: #111827; }
        .body p { margin: 0 0 14px; font-size: 15px; }
        .highlight { background: #ecfdf5; border-left: 4px solid #059669; padding: 16px 20px; border-radius: 4px; margin: 20px 0; color: #065f46; font-size: 15px; }
        .footer { padding: 20px 32px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>{{ $offer->business?->name ?? 'Website Expert' }}</h1>
        <p>
            @if($offer->language === 'pl')
                ✅ Twoje zainteresowanie zostało zarejestrowane
            @else
                ✅ Your interest has been registered
            @endif
        </p>
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
            <p>Dziękujemy za zainteresowanie naszą ofertą. Otrzymaliśmy Twoje zgłoszenie i wkrótce się z Tobą skontaktujemy, aby omówić szczegóły.</p>
            <div class="highlight">
                <strong>Twoja oferta:</strong> {{ $offer->title }}
            </div>
            <p>Możesz spodziewać się kontaktu w ciągu 1 dnia roboczego.</p>
            <p>Jeśli masz pytania, odpowiedz na tego maila — chętnie pomożemy.</p>
        @else
            <p>Thank you for your interest in our offer. We've received your request and will be in touch shortly to discuss the details.</p>
            <div class="highlight">
                <strong>Your offer:</strong> {{ $offer->title }}
            </div>
            <p>You can expect to hear from us within 1 business day.</p>
            <p>If you have any questions in the meantime, simply reply to this email — we're happy to help.</p>
        @endif
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} {{ $offer->business?->name ?? 'Website Expert' }}.
        {{ $offer->language === 'pl' ? 'Wszelkie prawa zastrzeżone.' : 'All rights reserved.' }}
    </div>
</div>
</body>
</html>
