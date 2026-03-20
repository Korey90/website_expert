<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote {{ $quote->number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f5; margin: 0; padding: 32px 0; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .header { background: #dc2626; padding: 32px; color: #fff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 4px 0 0; opacity: .85; font-size: 14px; }
        .body { padding: 32px; color: #374151; }
        .body h2 { margin: 0 0 8px; font-size: 18px; color: #111827; }
        .amount { font-size: 28px; font-weight: 700; color: #dc2626; margin: 16px 0 4px; }
        .btn { display: inline-block; background: #dc2626; color: #fff !important; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; margin: 20px 0; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #e5e7eb; padding: 8px 0; }
        td { padding: 10px 0; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #374151; }
        .footer { padding: 20px 32px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>WebsiteExpert</h1>
        <p>Quote {{ $quote->number }}</p>
    </div>
    <div class="body">
        <h2>Hello {{ $quote->client->company_name ?? $quote->client->primary_contact_name }},</h2>
        <p>Thank you for your interest. Please find your quote below. This quote is valid for <strong>30 days</strong>.</p>

        <table>
            <tr>
                <th>Quote #</th>
                <th>Date</th>
                <th>Valid Until</th>
            </tr>
            <tr>
                <td>{{ $quote->number }}</td>
                <td>{{ $quote->created_at->format('d M Y') }}</td>
                <td>{{ $quote->created_at->addDays(30)->format('d M Y') }}</td>
            </tr>
        </table>

        <div class="amount">
            {{ strtoupper($quote->currency ?? 'GBP') }} {{ number_format($quote->total, 2) }}
        </div>
        <p style="font-size:13px;color:#6b7280;">Estimated total (ex. VAT)</p>

        <a href="{{ config('app.url') }}/portal/quotes/{{ $quote->id }}" class="btn">
            View &amp; Accept Quote
        </a>

        <p style="font-size:13px;color:#6b7280;">To accept this quote, click the button above or reply to this email. If you have any questions, we're happy to chat.</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} WebsiteExpert. All rights reserved.
    </div>
</div>
</body>
</html>
