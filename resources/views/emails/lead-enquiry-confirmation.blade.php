<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $locale === 'pl' ? 'Dziękujemy za zapytanie' : 'Thank you for your enquiry' }} — Website Expert</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f5; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #ff2b17; padding: 28px 32px; }
        .header h1 { color: #fff; margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
        .body { padding: 28px 32px; color: #374151; font-size: 15px; line-height: 1.7; }
        .body p { margin: 0 0 16px; }
        .highlight { background: #fef2f2; border-left: 4px solid #ff2b17; padding: 14px 18px; border-radius: 4px; margin: 20px 0; }
        .highlight ul { margin: 8px 0 0 0; padding-left: 20px; }
        .highlight li { margin-bottom: 6px; }
        .btn { display: inline-block; background: #ff2b17; color: #fff !important; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; margin-top: 8px; }
        .footer { background: #f4f4f5; padding: 18px 32px; font-size: 12px; color: #9ca3af; text-align: center; }
        .footer a { color: #9ca3af; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        @if($locale === 'pl')
            <h1>Dziękujemy za zapytanie!</h1>
        @else
            <h1>Thank you for your enquiry!</h1>
        @endif
    </div>
    <div class="body">
        @if($locale === 'pl')
            <p>Cześć{{ $lead->contact?->first_name ? ', ' . $lead->contact->first_name : '' }},</p>
            <p>
                Otrzymałem Twoje zapytanie i zapoznam się z nim w ciągu <strong>jednego dnia roboczego</strong>.
                Skontaktuję się bezpośrednio, żeby omówić szczegóły i przygotować bezpłatną wycenę.
            </p>
            <div class="highlight">
                <strong>Co dzieje się dalej:</strong>
                <ul>
                    <li>Przeglądam Twoje zapytanie i obecną stronę (jeśli podałeś)</li>
                    <li>Kontaktuję się w ciągu jednego dnia roboczego</li>
                    <li>Umawiam krótką rozmowę, żeby lepiej zrozumieć Twoje potrzeby</li>
                    <li>Przygotowuję bezpłatną wycenę z ustalonym zakresem i ceną</li>
                </ul>
            </div>
            <p>Bez zobowiązań i bez presji sprzedażowej.</p>
            @if($senderPhone)
                <p>W pilnych sprawach możesz zadzwonić bezpośrednio: <a href="tel:{{ $senderPhone }}" style="color:#ff2b17;font-weight:600">{{ $senderPhone }}</a></p>
            @endif
            <a href="{{ config('app.url') }}/pl/strony-internetowe-uk" class="btn">Wróć do strony →</a>
        @else
            <p>Hi{{ $lead->contact?->first_name ? ' ' . $lead->contact->first_name : '' }},</p>
            <p>
                I've received your enquiry and will review it within <strong>one business day</strong>.
                I'll get back to you directly to discuss the details and prepare a free proposal.
            </p>
            <div class="highlight">
                <strong>What happens next:</strong>
                <ul>
                    <li>I review your enquiry and current website (if provided)</li>
                    <li>I get back to you within one business day</li>
                    <li>We arrange a short call to understand your needs</li>
                    <li>I prepare a free proposal with a defined scope and fixed price</li>
                </ul>
            </div>
            <p>No obligation and no sales pressure.</p>
            @if($senderPhone)
                <p>For urgent questions, call: <a href="tel:{{ $senderPhone }}" style="color:#ff2b17;font-weight:600">{{ $senderPhone }}</a></p>
            @endif
            <a href="{{ config('app.url') }}/en/web-design-northern-ireland" class="btn">Back to website →</a>
        @endif
    </div>
    <div class="footer">
        <p>Website Expert · Northern Ireland · <a href="{{ config('app.url') }}">website-expert.uk</a></p>
        <p style="margin-top:6px;font-size:11px">
            @if($locale === 'pl')
                Otrzymałeś tę wiadomość, ponieważ wypełniłeś formularz kontaktowy na naszej stronie.
            @else
                You received this email because you submitted an enquiry form on our website.
            @endif
        </p>
    </div>
</div>
</body>
</html>
