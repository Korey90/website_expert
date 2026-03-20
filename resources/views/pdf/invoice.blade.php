<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #1a1a2e;
            background: #ffffff;
        }

        /* ── Header ───────────────────────────────────────── */
        .header {
            display: table;
            width: 100%;
            padding: 36px 40px 24px;
            border-bottom: 3px solid #6366f1;
        }
        .header-left  { display: table-cell; width: 60%; vertical-align: top; }
        .header-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }

        .company-name {
            font-size: 22px;
            font-weight: 700;
            color: #6366f1;
            letter-spacing: -0.5px;
        }
        .company-meta {
            margin-top: 6px;
            font-size: 11px;
            color: #64748b;
            line-height: 1.6;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .invoice-number {
            margin-top: 4px;
            font-size: 13px;
            color: #64748b;
        }

        /* ── Status badge ────────────────────────────────── */
        .status-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-draft   { background: #e2e8f0; color: #475569; }
        .status-sent    { background: #dbeafe; color: #1d4ed8; }
        .status-paid    { background: #dcfce7; color: #166534; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #fef3c7; color: #92400e; }

        /* ── Addresses ───────────────────────────────────── */
        .addresses {
            display: table;
            width: 100%;
            padding: 28px 40px;
            border-bottom: 1px solid #e2e8f0;
        }
        .address-block { display: table-cell; width: 50%; vertical-align: top; }
        .address-block.right { text-align: right; padding-left: 20px; }

        .address-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .address-name {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .address-detail {
            font-size: 12px;
            color: #475569;
            line-height: 1.7;
        }

        /* ── Dates row ───────────────────────────────────── */
        .dates-row {
            display: table;
            width: 100%;
            padding: 16px 40px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .date-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
        }
        .date-cell-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .date-cell-value {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }

        /* ── Items table ─────────────────────────────────── */
        .items-section { padding: 28px 40px; }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table thead tr {
            background: #6366f1;
            color: #ffffff;
        }
        .items-table thead th {
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            text-align: left;
        }
        .items-table thead th.right { text-align: right; }

        .items-table tbody tr { border-bottom: 1px solid #f1f5f9; }
        .items-table tbody tr:last-child { border-bottom: none; }
        .items-table tbody tr:nth-child(even) { background: #f8fafc; }

        .items-table td {
            padding: 10px 14px;
            font-size: 13px;
            color: #334155;
            vertical-align: top;
        }
        .items-table td.right { text-align: right; }
        .item-desc { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        /* ── Totals ──────────────────────────────────────── */
        .totals-wrapper {
            padding: 0 40px 28px;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 6px 14px;
            font-size: 13px;
            color: #475569;
        }
        .totals-table td.label { text-align: left; }
        .totals-table td.value { text-align: right; font-weight: 600; color: #1e293b; }
        .totals-divider td { border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .totals-total td {
            font-size: 15px;
            font-weight: 700;
            color: #6366f1;
            padding-top: 4px;
            padding-bottom: 4px;
        }
        .totals-due td {
            background: #6366f1;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            border-radius: 6px;
            padding: 10px 14px;
        }

        /* ── Notes / Terms ───────────────────────────────── */
        .notes-section {
            padding: 0 40px 28px;
        }
        .notes-block {
            background: #f8fafc;
            border-left: 3px solid #6366f1;
            border-radius: 4px;
            padding: 14px 18px;
            margin-bottom: 14px;
        }
        .notes-block-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6366f1;
            margin-bottom: 6px;
        }
        .notes-block-body {
            font-size: 12px;
            color: #475569;
            line-height: 1.6;
        }

        /* ── Footer ──────────────────────────────────────── */
        .footer {
            border-top: 1px solid #e2e8f0;
            padding: 16px 40px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

{{-- ── Header ────────────────────────────────────────────── --}}
<div class="header">
    <div class="header-left">
        <div class="company-name">WebsiteExpert</div>
        <div class="company-meta">
            web@websiteexpert.co.uk<br>
            www.websiteexpert.co.uk
        </div>
    </div>
    <div class="header-right">
        <div class="invoice-title">Invoice</div>
        <div class="invoice-number">{{ $invoice->number }}</div>
        @php
            $statusClass = match($invoice->status) {
                'paid'      => 'status-paid',
                'sent'      => 'status-sent',
                'overdue'   => 'status-overdue',
                'cancelled' => 'status-cancelled',
                default     => 'status-draft',
            };
        @endphp
        <div><span class="status-badge {{ $statusClass }}">{{ ucfirst($invoice->status) }}</span></div>
    </div>
</div>

{{-- ── Bill To / From ──────────────────────────────────────── --}}
<div class="addresses">
    <div class="address-block">
        <div class="address-label">Bill To</div>
        <div class="address-name">{{ $invoice->client->company_name }}</div>
        <div class="address-detail">
            @if($invoice->client->primary_contact_name)
                {{ $invoice->client->primary_contact_name }}<br>
            @endif
            @if($invoice->client->address_line1)
                {{ $invoice->client->address_line1 }}<br>
            @endif
            @if($invoice->client->address_line2)
                {{ $invoice->client->address_line2 }}<br>
            @endif
            @if($invoice->client->city || $invoice->client->postcode)
                {{ implode(', ', array_filter([$invoice->client->city, $invoice->client->postcode])) }}<br>
            @endif
            @if($invoice->client->country)
                {{ $invoice->client->country }}<br>
            @endif
            @if($invoice->client->vat_number)
                VAT: {{ $invoice->client->vat_number }}<br>
            @endif
            @if($invoice->client->primary_contact_email)
                {{ $invoice->client->primary_contact_email }}
            @endif
        </div>
    </div>
    <div class="address-block right">
        <div class="address-label">Issued By</div>
        <div class="address-name">WebsiteExpert Ltd</div>
        <div class="address-detail">
            @if($invoice->createdBy)
                {{ $invoice->createdBy->name }}<br>
            @endif
            @if($invoice->project)
                Project: {{ $invoice->project->title }}<br>
            @endif
        </div>
    </div>
</div>

{{-- ── Dates ────────────────────────────────────────────────── --}}
<div class="dates-row">
    <div class="date-cell">
        <div class="date-cell-label">Issue Date</div>
        <div class="date-cell-value">{{ $invoice->issue_date ? $invoice->issue_date->format('d M Y') : '—' }}</div>
    </div>
    <div class="date-cell">
        <div class="date-cell-label">Due Date</div>
        <div class="date-cell-value">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}</div>
    </div>
    <div class="date-cell">
        <div class="date-cell-label">Currency</div>
        <div class="date-cell-value">{{ $invoice->currency }}</div>
    </div>
</div>

{{-- ── Line Items ───────────────────────────────────────────── --}}
<div class="items-section">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:50%">Description</th>
                <th class="right" style="width:12%">Qty</th>
                <th class="right" style="width:18%">Unit Price</th>
                <th class="right" style="width:20%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>
                    {{ $item->description }}
                </td>
                <td class="right">{{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }}</td>
                <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ── Totals ───────────────────────────────────────────────── --}}
<div class="totals-wrapper">
    <table class="totals-table">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->discount_amount > 0)
        <tr>
            <td class="label">Discount</td>
            <td class="value">− {{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</td>
        </tr>
        @endif
        @if($invoice->vat_rate > 0)
        <tr>
            <td class="label">VAT ({{ rtrim(rtrim(number_format($invoice->vat_rate, 2), '0'), '.') }}%)</td>
            <td class="value">{{ $invoice->currency }} {{ number_format($invoice->vat_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="totals-divider">
            <td class="label" style="font-weight:700;color:#1e293b">Total</td>
            <td class="value" style="color:#6366f1;font-size:15px">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
        </tr>
        @if($invoice->amount_paid > 0)
        <tr>
            <td class="label">Amount Paid</td>
            <td class="value">− {{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</td>
        </tr>
        @endif
        <tr><td colspan="2" style="padding:6px 0"></td></tr>
        <tr class="totals-due">
            <td class="label">Amount Due</td>
            <td class="value" style="color:#fff;text-align:right">{{ $invoice->currency }} {{ number_format($invoice->amount_due, 2) }}</td>
        </tr>
    </table>
</div>

{{-- ── Notes & Terms ────────────────────────────────────────── --}}
@if($invoice->notes || $invoice->terms)
<div class="notes-section">
    @if($invoice->notes)
    <div class="notes-block">
        <div class="notes-block-title">Notes</div>
        <div class="notes-block-body">{{ $invoice->notes }}</div>
    </div>
    @endif
    @if($invoice->terms)
    <div class="notes-block">
        <div class="notes-block-title">Payment Terms</div>
        <div class="notes-block-body">{{ $invoice->terms }}</div>
    </div>
    @endif
</div>
@endif

{{-- ── Footer ───────────────────────────────────────────────── --}}
<div class="footer">
    WebsiteExpert Ltd &nbsp;·&nbsp; web@websiteexpert.co.uk &nbsp;·&nbsp; www.websiteexpert.co.uk
    &nbsp;·&nbsp; Invoice {{ $invoice->number }}
</div>

</body>
</html>
