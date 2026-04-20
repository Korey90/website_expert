<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twój kontrakt jest gotowy</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f5; margin: 0; padding: 32px 0; }
        .wrapper { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .header { background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 32px; color: #fff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 6px 0 0; opacity: .85; font-size: 14px; }
        .body { padding: 32px; color: #374151; line-height: 1.7; }
        .body h2 { margin: 0 0 12px; font-size: 18px; color: #111827; }
        .body p { margin: 0 0 14px; font-size: 15px; }
        .meta-grid { background: #f5f3ff; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .meta-grid dt { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px; }
        .meta-grid dd { font-size: 15px; color: #111827; font-weight: 600; margin: 0 0 12px; }
        .btn { display: inline-block; background: #4f46e5; color: #fff !important; text-decoration: none; padding: 13px 32px; border-radius: 6px; font-weight: 600; margin: 20px 0; font-size: 15px; }
        .footer { padding: 20px 32px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>{{ config('app.name', 'Website Expert') }}</h1>
        <p>📄 Twój kontrakt jest gotowy do podpisania</p>
    </div>
    <div class="body">
        <h2>Witaj, {{ $contract->client?->primary_contact_name ?? 'Kliencie' }},</h2>
        <p>Dziękujemy za zaakceptowanie wyceny. Przygotowaliśmy kontrakt, który możesz przejrzeć i podpisać w portalu klienta.</p>

        <dl class="meta-grid">
            <dt>Numer kontraktu</dt>
            <dd>{{ $contract->number }}</dd>

            <dt>Tytuł</dt>
            <dd>{{ $contract->title }}</dd>

            @if($contract->value)
                <dt>Wartość</dt>
                <dd>{{ strtoupper($contract->currency ?? 'GBP') }} {{ number_format($contract->value, 2) }}</dd>
            @endif

            @if($contract->expires_at)
                <dt>Ważny do</dt>
                <dd>{{ $contract->expires_at->format('d M Y') }}</dd>
            @endif
        </dl>

        <a href="{{ config('app.url') }}/portal/contracts/{{ $contract->id }}" class="btn">
            Przejrzyj i podpisz kontrakt →
        </a>

        <p style="font-size: 13px; color: #6b7280;">
            Jeśli masz pytania dotyczące kontraktu, odpowiedz na tego maila lub skontaktuj się z nami bezpośrednio.
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name', 'Website Expert') }}. Wszelkie prawa zastrzeżone.
    </div>
</div>
</body>
</html>
