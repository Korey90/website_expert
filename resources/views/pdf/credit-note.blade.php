<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Credit Note {{ $creditNote->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        .header { display: table; width: 100%; padding: 34px 40px 22px; border-bottom: 3px solid #7C7072; }
        .header-left, .header-right { display: table-cell; width: 50%; vertical-align: top; }
        .header-right { text-align: right; }
        .company-name { font-size: 21px; font-weight: 700; color: #51373B; }
        .company-meta { margin-top: 6px; color: #64748b; line-height: 1.6; font-size: 10px; }
        .title { font-size: 27px; font-weight: 700; text-transform: uppercase; }
        .number { margin-top: 5px; color: #64748b; }
        .status { display: inline-block; margin-top: 8px; padding: 3px 10px; border-radius: 12px; background: #e2e8f0; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .addresses { display: table; width: 100%; padding: 24px 40px; border-bottom: 1px solid #e2e8f0; }
        .address { display: table-cell; width: 50%; vertical-align: top; line-height: 1.6; color: #475569; }
        .address.right { text-align: right; }
        .label { color: #94a3b8; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; }
        .name { color: #1e293b; font-size: 14px; font-weight: 700; margin: 4px 0; }
        .metadata { display: table; width: 100%; padding: 14px 40px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .meta { display: table-cell; width: 25%; text-align: center; }
        .meta-value { margin-top: 4px; font-weight: 600; }
        .reason { margin: 22px 40px 0; padding: 12px 16px; border-left: 3px solid #7C7072; background: #f8fafc; }
        .items { padding: 24px 40px; }
        table { border-collapse: collapse; }
        .items-table { width: 100%; }
        .items-table th { padding: 9px 8px; background: #7C7072; color: #fff; font-size: 9px; text-transform: uppercase; text-align: left; }
        .items-table th.right, .items-table td.right { text-align: right; }
        .items-table td { padding: 9px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .item-meta { margin-top: 3px; color: #94a3b8; font-size: 9px; }
        .vat-table { width: 420px; margin: 0 40px 20px auto; }
        .vat-table caption { padding-bottom: 6px; text-align: left; color: #7C7072; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .vat-table th, .vat-table td { padding: 5px 7px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: right; }
        .vat-table th:first-child, .vat-table td:first-child { text-align: left; }
        .totals { width: 310px; margin: 0 40px 28px auto; }
        .totals td { padding: 6px 10px; }
        .totals td:last-child { text-align: right; font-weight: 600; }
        .totals .grand td { border-top: 1px solid #cbd5e1; color: #7C7072; font-size: 15px; font-weight: 800; }
        .footer { margin-top: 12px; padding: 15px 40px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 9px; }
    </style>
</head>
<body>
@php
    $money = app(\App\Services\Currency\MoneyFormatter::class);
    $currency = $creditNote->currency ?? 'GBP';
    $profile = $creditNote->business?->accountingProfile;
    $sellerName = $profile?->legal_name ?: $creditNote->business?->name ?: config('app.name');
    $sellerAddress = $profile?->registered_address ?? [];
    $hasReverseCharge = $creditNote->items->contains(fn ($item) => $item->taxCode?->type === 'reverse_charge');
    $vatBreakdown = $creditNote->items
        ->groupBy(fn ($item) => ($item->taxCode?->code ?? 'VAT').'|'.number_format((float) $item->vat_rate_snapshot, 2, '.', ''))
        ->map(function ($items, $key) {
            [$code, $rate] = explode('|', $key, 2);

            return ['code' => $code, 'rate' => $rate, 'net' => $items->sum('net_amount'), 'vat' => $items->sum('vat_amount')];
        });
@endphp

<div class="header">
    <div class="header-left">
        <div class="company-name">{{ $sellerName }}</div>
        <div class="company-meta">
            @if($profile?->company_number)Company no: {{ $profile->company_number }}<br>@endif
            @if($profile?->vat_registered && $profile?->vat_number)VAT registration no: {{ $profile->vat_number }}@endif
        </div>
    </div>
    <div class="header-right">
        <div class="title">Credit Note</div>
        <div class="number">{{ $creditNote->number }}</div>
        <span class="status">{{ str_replace('_', ' ', $creditNote->status) }}</span>
    </div>
</div>

<div class="addresses">
    <div class="address">
        <div class="label">Credit To</div>
        <div class="name">{{ $creditNote->client?->company_name }}</div>
        @if($creditNote->client?->address_line1){{ $creditNote->client->address_line1 }}<br>@endif
        @if($creditNote->client?->address_line2){{ $creditNote->client->address_line2 }}<br>@endif
        @if($creditNote->client?->city || $creditNote->client?->postcode)
            {{ implode(', ', array_filter([$creditNote->client?->city, $creditNote->client?->postcode])) }}<br>
        @endif
        @if($creditNote->client?->country){{ $creditNote->client->country }}<br>@endif
        @if($creditNote->client?->vat_number)VAT: {{ $creditNote->client->vat_number }}@endif
    </div>
    <div class="address right">
        <div class="label">Issued By</div>
        <div class="name">{{ $sellerName }}</div>
        @if($sellerAddress['line_1'] ?? null){{ $sellerAddress['line_1'] }}<br>@endif
        @if($sellerAddress['line_2'] ?? null){{ $sellerAddress['line_2'] }}<br>@endif
        @if(($sellerAddress['city'] ?? null) || ($sellerAddress['postcode'] ?? null))
            {{ implode(', ', array_filter([$sellerAddress['city'] ?? null, $sellerAddress['postcode'] ?? null])) }}<br>
        @endif
        @if($sellerAddress['country'] ?? null){{ $sellerAddress['country'] }}@endif
    </div>
</div>

<div class="metadata">
    <div class="meta"><div class="label">Original Invoice</div><div class="meta-value">{{ $creditNote->invoice?->number }}</div></div>
    <div class="meta"><div class="label">Issue Date</div><div class="meta-value">{{ $creditNote->issue_date?->format('d M Y') ?? '—' }}</div></div>
    <div class="meta"><div class="label">Tax Point</div><div class="meta-value">{{ $creditNote->tax_point?->format('d M Y') ?? '—' }}</div></div>
    <div class="meta"><div class="label">Currency</div><div class="meta-value">{{ $currency }}</div></div>
</div>

<div class="reason"><span class="label">Reason for credit</span><br>{{ $creditNote->reason }}</div>
@if($hasReverseCharge)
<div class="reason"><span class="label">Reverse charge</span><br>Customer to account to HMRC for the VAT due under the domestic reverse charge.</div>
@endif

<div class="items">
    <table class="items-table">
        <thead><tr>
            <th style="width:36%">Description</th><th class="right" style="width:9%">Qty</th>
            <th class="right" style="width:15%">Unit</th><th class="right" style="width:14%">Net</th>
            <th class="right" style="width:12%">VAT</th><th class="right" style="width:14%">Credit</th>
        </tr></thead>
        <tbody>
        @foreach($creditNote->items as $item)
            <tr>
                <td>{{ $item->description }}<div class="item-meta">{{ $item->taxCode?->code ?? 'VAT' }} · {{ number_format((float) $item->vat_rate_snapshot, 2) }}%@if($item->supply_date) · Supply: {{ $item->supply_date->format('d M Y') }}@endif</div></td>
                <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                <td class="right">{{ $money->format($item->unit_price, $currency) }}</td>
                <td class="right">{{ $money->format($item->net_amount, $currency) }}</td>
                <td class="right">{{ $money->format($item->vat_amount, $currency) }}</td>
                <td class="right">{{ $money->format($item->gross_amount, $currency) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<table class="vat-table">
    <caption>VAT breakdown</caption>
    <thead><tr><th>Tax code / rate</th><th>Taxable net</th><th>VAT</th></tr></thead>
    <tbody>
    @foreach($vatBreakdown as $vatRow)
        <tr><td>{{ $vatRow['code'] }} · {{ number_format((float) $vatRow['rate'], 2) }}%</td><td>{{ $money->format($vatRow['net'], $currency) }}</td><td>{{ $money->format($vatRow['vat'], $currency) }}</td></tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td>Net credit</td><td>{{ $money->format($creditNote->subtotal, $currency) }}</td></tr>
    <tr><td>VAT credit</td><td>{{ $money->format($creditNote->vat_amount, $currency) }}</td></tr>
    @if($currency !== 'GBP' && $creditNote->vat_amount_gbp !== null)
        <tr><td>VAT credit in GBP</td><td>{{ $money->format($creditNote->vat_amount_gbp, 'GBP') }}</td></tr>
    @endif
    <tr class="grand"><td>Total credit</td><td>{{ $money->format($creditNote->total, $currency) }}</td></tr>
</table>

<div class="footer">
    {{ $sellerName }}
    @if($profile?->company_number)&nbsp;·&nbsp; Company no: {{ $profile->company_number }}@endif
    @if($profile?->vat_registered && $profile?->vat_number)&nbsp;·&nbsp; VAT: {{ $profile->vat_number }}@endif
    &nbsp;·&nbsp; Credit Note {{ $creditNote->number }}
</div>
</body>
</html>
