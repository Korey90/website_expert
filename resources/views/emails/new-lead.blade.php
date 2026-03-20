<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nowe zapytanie — WebsiteExpert</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f5; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #ff2b17; padding: 28px 32px; }
        .header h1 { color: #fff; margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
        .body { padding: 28px 32px; }
        .label { font-size: 11px; text-transform: uppercase; letter-spacing: .8px; color: #9ca3af; margin-bottom: 2px; }
        .value { font-size: 15px; color: #111827; margin-bottom: 16px; word-break: break-word; }
        .message-box { background: #f9fafb; border-left: 4px solid #ff2b17; padding: 14px 18px; border-radius: 4px; font-size: 15px; color: #374151; line-height: 1.6; margin-bottom: 24px; }
        .btn { display: inline-block; background: #ff2b17; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .footer { background: #f4f4f5; padding: 18px 32px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>📩 Nowe zapytanie z formularza kontaktowego</h1>
        </div>
        <div class="body">
            <p class="label">Lead #</p>
            <p class="value">#{{ $leadId }}</p>

            <p class="label">Imię i nazwisko</p>
            <p class="value">{{ $data['name'] }}</p>

            @if(!empty($data['company']))
            <p class="label">Firma</p>
            <p class="value">{{ $data['company'] }}{{ !empty($data['nip']) ? ' (NIP: '.$data['nip'].')' : '' }}</p>
            @endif

            <p class="label">E-mail</p>
            <p class="value"><a href="mailto:{{ $data['email'] }}" style="color:#ff2b17">{{ $data['email'] }}</a></p>

            @if(!empty($data['phone']))
            <p class="label">Telefon</p>
            <p class="value"><a href="tel:{{ $data['phone'] }}" style="color:#ff2b17">{{ $data['phone'] }}</a></p>
            @endif

            @if(!empty($data['project_type']))
            <p class="label">Rodzaj projektu</p>
            <p class="value">{{ $data['project_type'] }}</p>
            @endif

            @if(!empty($data['contact_preference']))
            <p class="label">Preferowany kontakt</p>
            <p class="value">{{ $data['contact_preference'] }}</p>
            @endif

            <p class="label">Wiadomość</p>
            <div class="message-box">{{ $data['message'] }}</div>

            <a href="{{ config('app.url') }}/admin/leads/{{ $leadId }}" class="btn">
                Otwórz lead w panelu →
            </a>
        </div>
        <div class="footer">
            WebsiteExpert · Wiadomość wygenerowana automatycznie
        </div>
    </div>
</body>
</html>
