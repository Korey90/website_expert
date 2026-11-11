<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>HMRC VAT submission receipt {{ $return->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 32px; font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #172033; }
        h1 { margin: 0 0 4px; font-size: 22px; color: #111827; }
        h2 { margin: 22px 0 8px; font-size: 14px; color: #1f2937; border-bottom: 1px solid #d1d5db; padding-bottom: 5px; }
        .subtitle { color: #4b5563; }
        .notice { margin: 18px 0; padding: 12px; border: 1px solid #d97706; background: #fffbeb; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 7px 8px; border: 1px solid #d1d5db; text-align: left; vertical-align: top; }
        th { width: 32%; background: #f3f4f6; font-weight: bold; }
        .events th { width: auto; }
        .hash { font-family: DejaVu Sans Mono, monospace; font-size: 8px; word-break: break-all; }
        .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #d1d5db; color: #6b7280; font-size: 9px; line-height: 1.5; }
    </style>
</head>
<body>
    <h1>VAT Return submission receipt</h1>
    <div class="subtitle">{{ $business->name }} · {{ strtoupper($submission->environment) }}</div>

    <div class="notice">
        <strong>System-generated evidence.</strong>
        This document records the successful HTTP 201 response received by Website Expert NI software.
        It is not an HMRC-issued document and does not replace records visible in the HMRC account.
    </div>

    <h2>Submission</h2>
    <table>
        <tr><th>VAT Return</th><td>{{ $return->number }}</td></tr>
        <tr><th>Period</th><td>{{ $return->period_from->format('d F Y') }} – {{ $return->period_to->format('d F Y') }}</td></tr>
        <tr><th>Declared at</th><td>{{ $submission->declared_at->utc()->format('Y-m-d H:i:s') }} UTC</td></tr>
        <tr><th>Submitted at</th><td>{{ $submission->submitted_at->utc()->format('Y-m-d H:i:s') }} UTC</td></tr>
        <tr><th>HMRC processing date</th><td>{{ $submission->processing_date->utc()->format('Y-m-d H:i:s') }} UTC</td></tr>
        <tr><th>Submitted by</th><td>{{ $submission->submittedBy?->name ?? 'User no longer available' }}</td></tr>
        <tr><th>Form bundle number</th><td>{{ $submission->form_bundle_number }}</td></tr>
        <tr><th>Payment indicator</th><td>{{ $submission->payment_indicator ?? 'Not returned' }}</td></tr>
        <tr><th>Charge reference</th><td>{{ $submission->charge_reference ?? 'Not returned' }}</td></tr>
    </table>

    <h2>Integrity</h2>
    <table>
        <tr><th>VAT snapshot SHA-256</th><td class="hash">{{ $submission->snapshot_hash }}</td></tr>
        <tr><th>Submitted payload SHA-256</th><td class="hash">{{ $submission->payload_hash }}</td></tr>
        <tr><th>Audit events included</th><td>{{ $audit['count'] }}</td></tr>
        <tr><th>Audit chain head SHA-256</th><td class="hash">{{ $audit['head_hash'] }}</td></tr>
    </table>

    <h2>Audit trail</h2>
    <table class="events">
        <thead>
            <tr><th>#</th><th>Event</th><th>Status</th><th>Occurred</th><th>Actor</th><th>Event SHA-256</th></tr>
        </thead>
        <tbody>
            @foreach ($events as $event)
                <tr>
                    <td>{{ $event->sequence }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($event->event_type)) }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($event->status)) }}</td>
                    <td>{{ $event->occurred_at->utc()->format('Y-m-d H:i:s') }} UTC</td>
                    <td>{{ $event->actor?->name ?? 'System / unavailable' }}</td>
                    <td class="hash">{{ $event->event_hash }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated {{ $generatedAt->format('Y-m-d H:i:s') }} UTC by {{ $generatedBy->name }}.
        No OAuth token, full VRN, HMRC period key, MFA secret or raw API response is included in this document.
    </div>
</body>
</html>
