<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 10px; line-height: 1.45; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        h2 { font-size: 14px; margin: 18px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #e5e7eb; }
        .notice { padding: 8px; border: 1px solid #9ca3af; background: #f3f4f6; }
        .hash { font-family: DejaVu Sans Mono, monospace; font-size: 8px; word-break: break-all; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="notice">{{ $notice }}</p>

    <table>
        @foreach ($metadata as $label => $value)
            <tr><th>{{ $label }}</th><td class="{{ str_contains(strtolower((string) $label), 'hash') ? 'hash' : '' }}">{{ $value }}</td></tr>
        @endforeach
    </table>

    @foreach ($sections as $section)
        <h2>{{ $section['title'] }}</h2>
        @if (isset($section['text']))
            <p>{{ $section['text'] }}</p>
        @endif
        @if (isset($section['rows']))
            <table>
                @foreach ($section['rows'] as $row)
                    <tr><th>{{ $row[0] }}</th><td>{{ $row[1] }}</td></tr>
                @endforeach
            </table>
        @endif
    @endforeach
</body>
</html>
