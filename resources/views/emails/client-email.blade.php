<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.7; color: #374151; background: #f9fafb; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .body { padding: 32px 40px; }
        .footer { padding: 20px 40px; background: #f3f4f6; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; text-align: center; }
        a { color: #6366f1; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="body">{!! $emailBody !!}</div>
        <div class="footer">
            WebsiteExpert &mdash; <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
        </div>
    </div>
</body>
</html>
