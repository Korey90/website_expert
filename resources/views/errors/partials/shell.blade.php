@php
    $code        = $code ?? 500;
    $title       = __("errors.{$code}.title");
    $message     = __("errors.{$code}.message");
    $homeUrl     = url('/');
    $supportMail = 'sales@website-expert.uk';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $code }} — {{ $title }} | {{ config('app.name', 'Website Expert') }}</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #0a0a0a;
            background-image: radial-gradient(circle at 20% 20%, rgba(255,43,23,0.12), transparent 45%),
                               radial-gradient(circle at 80% 80%, rgba(255,43,23,0.08), transparent 45%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #e5e5e5;
        }
        .card {
            max-width: 560px;
            width: 100%;
            text-align: center;
        }
        .code {
            font-size: 96px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #ff6657, #ff2b17);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin: 0 0 8px;
        }
        h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 12px;
            color: #fafafa;
        }
        p.message {
            font-size: 15px;
            line-height: 1.6;
            color: #a3a3a3;
            margin: 0 0 32px;
        }
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .btn:hover { opacity: 0.85; }
        .btn-primary {
            background: #ff2b17;
            color: #fff;
        }
        .support {
            margin-top: 28px;
            font-size: 13px;
            color: #737373;
        }
        .support a { color: #ff6657; text-decoration: none; }
        .support a:hover { text-decoration: underline; }
        .brand {
            margin-top: 40px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: #525252;
        }
    </style>
</head>
<body>
    <div class="card">
        <p class="code">{{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p class="message">{{ $message }}</p>
        <div class="actions">
            <a href="{{ $homeUrl }}" class="btn btn-primary">{{ __('errors.home_link') }}</a>
        </div>
        <p class="support">
            {{ __('errors.support_text') }} <a href="mailto:{{ $supportMail }}">{{ $supportMail }}</a>
        </p>
        <p class="brand">WebsiteExpert</p>
    </div>
</body>
</html>
