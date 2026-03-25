<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Received</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f5; margin: 0; padding: 32px 0; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .header { background: #16a34a; padding: 32px; color: #fff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 4px 0 0; opacity: .85; font-size: 14px; }
        .body { padding: 32px; color: #374151; }
        .body h2 { margin: 0 0 8px; font-size: 18px; color: #111827; }
        .amount { font-size: 32px; font-weight: 700; color: #16a34a; margin: 16px 0 4px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #e5e7eb; padding: 8px 0; }
        td { padding: 10px 0; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #374151; }
        .badge { display: inline-block; background: #f0fdf4; color: #15803d; border-radius: 20px; padding: 2px 10px; font-size: 12px; font-weight: 600; }
        .btn { display: inline-block; background: #16a34a; color: #fff !important; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; margin: 20px 0; font-size: 15px; }
        .footer { padding: 20px 32px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>WebsiteExpert</h1>
        <p>Payment Confirmation</p>
    </div>
    <div class="body">
        @php
            $invoice = $payment->invoice;
            $client  = $invoice?->client;
            $methodMap = ['stripe' => 'Stripe', 'payu' => 'PayU', 'bank_transfer' => 'Bank Transfer', 'cash' => 'Cash', 'cheque' => 'Cheque', 'other' => 'Other'];
        @endphp

        <h2>Hello {{ $client?->primary_contact_name ?? $client?->company_name ?? 'there' }},</h2>
        <p>We have received your payment. Thank you!</p>

        <div class="amount">
            {{ strtoupper($payment->currency ?? 'GBP') }} {{ number_format($payment->amount, 2) }}
        </div>
        <p style="font-size:13px;color:#6b7280;margin-top:0;">Payment confirmed <span class="badge">&#10003; Paid</span></p>

        <table>
            <tr>
                <th>Invoice</th>
                <th>Method</th>
                <th>Date</th>
                <th>Reference</th>
            </tr>
            <tr>
                <td>{{ $invoice?->number ?? '—' }}</td>
                <td>{{ $methodMap[$payment->method] ?? ucfirst($payment->method) }}</td>
                <td>{{ $payment->paid_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i') }}</td>
                <td style="font-family:monospace;font-size:12px;">{{ $payment->reference ?: '—' }}</td>
            </tr>
        </table>

        @if($invoice && $invoice->amount_due > 0)
        <p style="background:#fef9c3;border:1px solid #fef08a;border-radius:6px;padding:12px 16px;font-size:13px;color:#713f12;">
            <strong>Remaining balance:</strong>
            {{ strtoupper($invoice->currency ?? 'GBP') }} {{ number_format($invoice->amount_due, 2) }}
        </p>
        @endif

        <a href="{{ config('app.url') }}/portal/invoices/{{ $invoice?->id }}" class="btn">
            View Invoice
        </a>

        <p style="font-size:13px;color:#6b7280;">If you have any questions, please reply to this email or contact us at <a href="mailto:{{ config('mail.from.address') }}" style="color:#16a34a;">{{ config('mail.from.address') }}</a>.</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} WebsiteExpert. All rights reserved.
    </div>
</div>
</body>
</html>
