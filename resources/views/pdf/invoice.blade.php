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
            border-bottom: 3px solid #7C7072;
        }
        .header-left  { display: table-cell; width: 60%; vertical-align: top; }
        .header-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }

        .company-name {
            font-size: 22px;
            font-weight: 700;
            color: #51373B;
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
            width: 25%;
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
            background: #7C7072;
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
        .items-table thead th.right { text-align: right; font-weight: 800; }

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

        .vat-breakdown {
            width: 440px;
            margin: 0 40px 24px auto;
            border-collapse: collapse;
        }
        .vat-breakdown caption {
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #7C7072;
            padding-bottom: 6px;
        }
        .vat-breakdown th, .vat-breakdown td {
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 8px;
            font-size: 11px;
            text-align: right;
        }
        .vat-breakdown th:first-child, .vat-breakdown td:first-child { text-align: left; }

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
            font-weight: 800;
            color: #7C7072;
            padding-top: 4px;
            padding-bottom: 4px;
        }
        .totals-due td {
            background: #7C7072;
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
            border-left: 3px solid #7C7072;
            border-radius: 4px;
            padding: 14px 18px;
            margin-bottom: 14px;
        }
        .notes-block-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #7C7072;
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
        .demo-watermark {
            position: fixed; top: 43%; left: 8%; width: 84%; text-align: center;
            transform: rotate(-30deg); font-size: 38px; font-weight: 700;
            color: rgba(127, 29, 29, .07); letter-spacing: 3px; z-index: -1;
        }
        .demo-banner {
            padding: 7px 12px; background: #7f1d1d; color: #fff; text-align: center;
            font-size: 9px; font-weight: 700; letter-spacing: 1px;
        }
    </style>
</head>
<body>
@if($demo ?? false)
<div class="demo-watermark">DEMO / NOT FOR SUBMISSION</div>
<div class="demo-banner">{{ $demoLabel ?? 'CURRENT SYSTEM OUTPUT' }} · FICTIONAL DATA · NOT FOR SUBMISSION</div>
@endif
@php
    $money = app(\App\Services\Currency\MoneyFormatter::class);
    $currency = $invoice->currency ?? 'GBP';
    $profile = $invoice->business?->accountingProfile;
    $sellerName = $profile?->legal_name ?: $invoice->business?->name ?: config('app.name');
    $sellerAddress = $profile?->registered_address ?? [];
    $hasReverseCharge = $invoice->items->contains(fn ($item) => $item->taxCode?->type === 'reverse_charge');
    $vatBreakdown = $invoice->items
        ->groupBy(fn ($item) => ($item->taxCode?->code ?? 'VAT').'|'.number_format((float) $item->vat_rate_snapshot, 2, '.', ''))
        ->map(function ($items, $key) {
            [$code, $rate] = explode('|', $key, 2);

            return [
                'code' => $code,
                'rate' => $rate,
                'net' => $items->sum('net_amount'),
                'vat' => $items->sum('vat_amount'),
            ];
        });
@endphp

{{-- ── Header ────────────────────────────────────────────── --}}
<div class="header">
    <div class="header-left">
        <div class="company-name">{{ $sellerName }}</div>
        <div class="company-meta">
            @if($profile?->company_number)Company no: {{ $profile->company_number }}<br>@endif
            @if($profile?->vat_registered && $profile?->vat_number)VAT registration no: {{ $profile->vat_number }}@endif
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
        <div class="address-name">{{ $invoice->client?->company_name }}</div>
        <div class="address-detail">
            @if($invoice->client?->primary_contact_name)
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
        <div class="address-name">{{ $sellerName }}</div>
        <div class="address-detail">
            @if($sellerAddress['line_1'] ?? null){{ $sellerAddress['line_1'] }}<br>@endif
            @if($sellerAddress['line_2'] ?? null){{ $sellerAddress['line_2'] }}<br>@endif
            @if(($sellerAddress['city'] ?? null) || ($sellerAddress['postcode'] ?? null))
                {{ implode(', ', array_filter([$sellerAddress['city'] ?? null, $sellerAddress['postcode'] ?? null])) }}<br>
            @endif
            @if($sellerAddress['country'] ?? null){{ $sellerAddress['country'] }}<br>@endif
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
        <div class="date-cell-label">Tax Point</div>
        <div class="date-cell-value">{{ $invoice->tax_point ? $invoice->tax_point->format('d M Y') : '—' }}</div>
    </div>
    <div class="date-cell">
        <div class="date-cell-label">Currency</div>
        <div class="date-cell-value">{{ $currency }}</div>
    </div>
</div>

{{-- ── Line Items ───────────────────────────────────────────── --}}
<div class="items-section">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:34%">Description</th>
                <th class="right" style="width:9%">Qty</th>
                <th class="right" style="width:15%">Unit</th>
                <th class="right" style="width:14%">Net</th>
                <th class="right" style="width:12%">VAT</th>
                <th class="right" style="width:16%">Gross</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>
                    {{ $item->description }}
                    <div class="item-desc">
                        {{ $item->taxCode?->code ?? 'VAT' }} · {{ number_format((float) $item->vat_rate_snapshot, 2) }}%
                        @if($item->supply_date) · Supply: {{ $item->supply_date->format('d M Y') }}@endif
                    </div>
                </td>
                <td class="right">{{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }}</td>
                <td class="right">{{ $money->format($item->unit_price, $currency) }}</td>
                <td class="right">{{ $money->format($item->net_amount, $currency) }}</td>
                <td class="right">{{ $money->format($item->vat_amount, $currency) }}</td>
                <td class="right">{{ $money->format($item->gross_amount, $currency) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($vatBreakdown->isNotEmpty())
<table class="vat-breakdown">
    <caption>VAT breakdown</caption>
    <thead>
        <tr><th>Tax code / rate</th><th>Taxable net</th><th>VAT</th></tr>
    </thead>
    <tbody>
        @foreach($vatBreakdown as $vatRow)
        <tr>
            <td>{{ $vatRow['code'] }} · {{ number_format((float) $vatRow['rate'], 2) }}%</td>
            <td>{{ $money->format($vatRow['net'], $currency) }}</td>
            <td>{{ $money->format($vatRow['vat'], $currency) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ── Totals ───────────────────────────────────────────────── --}}
<div class="totals-wrapper">
    <table class="totals-table">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ $money->format($invoice->subtotal, $currency) }}</td>
        </tr>
        @if($invoice->discount_amount > 0)
        <tr>
            <td class="label">Discount</td>
            <td class="value">− {{ $money->format($invoice->discount_amount, $currency) }}</td>
        </tr>
        @endif
        @if($invoice->vat_amount > 0)
        <tr>
            <td class="label">VAT</td>
            <td class="value">{{ $money->format($invoice->vat_amount, $currency) }}</td>
        </tr>
        @if($currency !== 'GBP' && $invoice->vat_amount_gbp !== null)
        <tr>
            <td class="label">VAT in GBP</td>
            <td class="value">{{ $money->format($invoice->vat_amount_gbp, 'GBP') }}</td>
        </tr>
        @endif
        @endif
        <tr class="totals-divider">
            <td class="label" style="font-weight:700;color:#1e293b">Total</td>
            <td class="value" style="color:#7C7072;font-size:15px">{{ $money->format($invoice->total, $currency) }}</td>
        </tr>
        @if($invoice->amount_paid > 0)
        <tr>
            <td class="label">Amount Paid</td>
            <td class="value">− {{ $money->format($invoice->amount_paid, $currency) }}</td>
        </tr>
        @endif
        <tr><td colspan="2" style="padding:6px 0"></td></tr>
        <tr class="totals-due">
            <td class="label">Amount Due</td>
            <td class="value" style="color:#fff;text-align:right">{{ $money->format($invoice->amount_due, $currency) }}</td>
        </tr>
    </table>
</div>

{{-- ── Notes & Terms ────────────────────────────────────────── --}}
@if($hasReverseCharge || $invoice->notes || $invoice->terms)
<div class="notes-section">
    @if($hasReverseCharge)
    <div class="notes-block">
        <div class="notes-block-title">Reverse charge</div>
        <div class="notes-block-body">Customer to account to HMRC for the VAT due under the domestic reverse charge.</div>
    </div>
    @endif
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
    {{ $sellerName }}
    @if($profile?->company_number)&nbsp;·&nbsp; Company no: {{ $profile->company_number }}@endif
    @if($profile?->vat_registered && $profile?->vat_number)&nbsp;·&nbsp; VAT: {{ $profile->vat_number }}@endif
    &nbsp;·&nbsp; Invoice {{ $invoice->number }}
</div>

</body>
</html>
