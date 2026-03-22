<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 15px; line-height: 1.7; color: #374151; background: #f1f5f9; margin: 0; padding: 0; }
        .outer { padding: 40px 16px; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .header { background: #1e293b; padding: 24px 40px; }
        .header-brand { color: #ffffff; font-size: 17px; font-weight: 700; letter-spacing: 0.5px; text-decoration: none; }
        .header-tagline { color: #94a3b8; font-size: 12px; margin-top: 2px; }
        .accent-bar { height: 3px; background: linear-gradient(90deg, #4F46E5 0%, #7C3AED 50%, #06B6D4 100%); }
        .body { padding: 36px 40px; }
        .body p { margin: 0 0 16px; color: #374151; }
        .body ul, .body ol { margin: 12px 0 16px; padding-left: 24px; }
        .body li { margin-bottom: 6px; color: #374151; }
        .body h2 { font-size: 16px; color: #1e293b; margin: 24px 0 8px; font-weight: 700; }
        .body strong { color: #1e293b; }
        .body a { color: #4F46E5; }
        .footer { padding: 20px 40px; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center; line-height: 1.6; }
        .footer a { color: #94a3b8; }
        @media only screen and (max-width: 620px) {
            .outer { padding: 16px 8px; }
            .header, .body, .footer { padding-left: 24px; padding-right: 24px; }
        }
    </style>
</head>
<body>
<div class="outer">
    <div class="wrapper">
        <div class="header">
            <div class="header-brand">{{ config('mail.from.name', config('app.name')) }}</div>
            <div class="header-tagline">Professional Web Services</div>
        </div>
        <div class="accent-bar"></div>
        <div class="body">{!! $emailBody !!}</div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('mail.from.name', config('app.name')) }} &nbsp;&middot;&nbsp;
            <a href="{{ config('app.url') }}">{{ config('app.url') }}</a><br>
            This email was sent to you because you are a client or enquirer. Please do not reply directly to this email.
        </div>
    </div>
</div>
</body>
</html>
