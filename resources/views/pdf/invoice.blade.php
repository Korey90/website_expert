<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #1a1a2e; background: #fff; }

        .page { padding: 40px 48px; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .brand-name { font-size: 24px; font-weight: 700; color: #ff2b17; letter-spacing: -0.5px; }
        .brand-tagline { font-size: 11px; color: #888; margin-top: 2px; }
        .invoice-meta { text-align: right; }
        .invoice-meta h1 { font-size: 28px; font-weight: 800; color: #ff2b17; letter-spacing: -1px; }
        .invoice-meta p { font-size: 12px; color: #555; margin-top: 4px; }

        /* Divider */
        .divider { height: 2px; background: #ff2b17; margin-bottom: 32px; border-radius: 2px; }

        /* Parties */
        .parties { display: flex; justify-content: space-between; margin-bottom: 36px; gap: 24px; }
        .party { flex: 1; }
        .party-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #ff2b17; margin-bottom: 8px; }
        .party-name { font-size: 15px; font-weight: 700; color: #111; }
        .party-detail { font-size: 12px; color: #555; margin-top: 3px; line-height: 1.5; }

        /* Invoice details box */
        .details-box { background: #f9f9f9; border: 1px solid #eee; border-radius: 6px; padding: 16px 20px; margin-bottom: 32px; }
        .details-grid { display: flex; gap: 48px; }
        .detail-item label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #888; display: block; margin-bottom: 4px; }
        .detail-item span { font-size: 13px; font-weight: 600; color: #111; }
        .status-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-sent { background: #dbeafe; color: #1d4ed8; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f3f4f6; color: #6b7280; }
        .status-partially_paid { background: #ede9fe; color: #5b21b6; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead th { background: #1a1a2e; color: #fff; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; padding: 10px 12px; text-align: left; }
        thead th:last-child, thead th.right { text-align: right; }
        tbody tr { border-bottom: 1px solid #f0f0f0; }
        tbody tr:last-child { border-bottom: none; }
        tbody td { padding: 10px 12px; font-size: 13px; color: #333; vertical-align: top; }
        tbody td.right { text-align: right; }
        tbody tr:nth-child(even) { background: #fafafa; }

        /* Totals */
        .totals-wrapper { display: flex; justify-content: flex-end; margin-bottom: 32px; }
        .totals { width: 280px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: #444; border-bottom: 1px solid #f0f0f0; }
        .totals-row:last-child { border-bottom: none; }
        .totals-row.total { font-size: 16px; font-weight: 800; color: #111; padding: 10px 0 0; }
        .totals-row span:first-child { color: #888; }
        .totals-row.total span:first-child { color: #111; }

        /* Notes & Terms */
        .notes-section { display: flex; gap: 32px; margin-bottom: 40px; }
        .notes-block { flex: 1; }
        .notes-block h4 { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #ff2b17; margin-bottom: 8px; }
        .notes-block p { font-size: 12px; color: #555; line-height: 1.6; }

        /* Footer */
        .footer { border-top: 1px solid #eee; padding-top: 16px; text-align: center; font-size: 11px; color: #aaa; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="brand-name">WebsiteExpert</div>
            <div class="brand-tagline">Professional Web Development · UK</div>
        </div>
        <div class="invoice-meta">
            <h1>INVOICE</h1>
            <p>{{ $invoice->number }}</p>
        </div>
    </div>

    <div class="divider"></div>

    {{-- Parties --}}
    <div class="parties">
        <div class="party">
            <div class="party-label">From</div>
            <div class="party-name">WebsiteExpert Ltd</div>
            <div class="party-detail">
                hello@websiteexpert.co.uk<br>
                websiteexpert.co.uk<br>
                United Kingdom
            </div>
        </div>
        <div class="party">
            <div class="party-label">Bill To</div>
            @if($invoice->client)
                <div class="party-name">{{ $invoice->client->company_name }}</div>
                <div class="party-detail">
                    {{ $invoice->client->contact_name }}<br>
                    @if($invoice->client->email) {{ $invoice->client->email }}<br> @endif
                    @if($invoice->client->phone) {{ $invoice->client->phone }}<br> @endif
                    @if($invoice->client->address) {{ $invoice->client->address }}<br> @endif
                    @if($invoice->client->city) {{ $invoice->client->city }}, @endif
                    @if($invoice->client->postcode) {{ $invoice->client->postcode }} @endif
                </div>
            @else
                <div class="party-name">—</div>
            @endif
        </div>
    </div>

    {{-- Invoice Details --}}
    <div class="details-box">
        <div class="details-grid">
            <div class="detail-item">
                <label>Issue Date</label>
                <span>{{ $invoice->issue_date->format('d M Y') }}</span>
            </div>
            <div class="detail-item">
                <label>Due Date</label>
                <span>{{ $invoice->due_date->format('d M Y') }}</span>
            </div>
            <div class="detail-item">
                <label>Status</label>
                <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</span>
            </div>
            @if($invoice->project)
            <div class="detail-item">
                <label>Project</label>
                <span>{{ $invoice->project->title }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Line Items --}}
    <table>
        <thead>
            <tr>
                <th style="width:50%">Description</th>
                <th class="right" style="width:12%">Qty</th>
                <th class="right" style="width:18%">Unit Price</th>
                <th class="right" style="width:20%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="right">£{{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">£{{ number_format($item->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; color:#aaa; padding: 20px;">No items</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals-wrapper">
        <div class="totals">
            <div class="totals-row">
                <span>Subtotal</span>
                <span>£{{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if($invoice->discount_amount > 0)
            <div class="totals-row">
                <span>Discount</span>
                <span>− £{{ number_format($invoice->discount_amount, 2) }}</span>
            </div>
            @endif
            <div class="totals-row">
                <span>VAT ({{ number_format($invoice->vat_rate, 0) }}%)</span>
                <span>£{{ number_format($invoice->vat_amount, 2) }}</span>
            </div>
            <div class="totals-row total">
                <span>Total</span>
                <span>£{{ number_format($invoice->total, 2) }}</span>
            </div>
            @if($invoice->amount_paid > 0)
            <div class="totals-row">
                <span>Amount Paid</span>
                <span>− £{{ number_format($invoice->amount_paid, 2) }}</span>
            </div>
            <div class="totals-row total">
                <span>Balance Due</span>
                <span>£{{ number_format($invoice->amount_due, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Notes & Terms --}}
    @if($invoice->notes || $invoice->terms)
    <div class="notes-section">
        @if($invoice->notes)
        <div class="notes-block">
            <h4>Notes</h4>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif
        @if($invoice->terms)
        <div class="notes-block">
            <h4>Payment Terms</h4>
            <p>{{ $invoice->terms }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        WebsiteExpert Ltd &nbsp;·&nbsp; hello@websiteexpert.co.uk &nbsp;·&nbsp; websiteexpert.co.uk
    </div>

</div>
</body>
</html>
