@extends('reports.layout')

@section('title', 'Invoices Report')

@section('content')
@php($money = app(\App\Services\Currency\CurrencySummaryFormatter::class))

<div class="export-bar">
    <a href="{{ route('reports.invoices.html') }}" class="active">HTML</a>
    <a href="{{ route('reports.invoices.pdf') }}">PDF</a>
    <a href="{{ route('reports.invoices.xlsx') }}">Excel</a>
    <a href="{{ route('reports.invoices.csv') }}">CSV</a>
</div>

<div class="totals">
    <div class="total-box"><div class="label">Paid</div><div class="value">{{ $money->formatGrouped($totals['paid'] ?? []) }}</div></div>
    <div class="total-box"><div class="label">Sent</div><div class="value">{{ $money->formatGrouped($totals['sent'] ?? []) }}</div></div>
    <div class="total-box"><div class="label">Overdue</div><div class="value">{{ $money->formatGrouped($totals['overdue'] ?? []) }}</div></div>
    <div class="total-box"><div class="label">Draft</div><div class="value">{{ $money->formatGrouped($totals['draft'] ?? []) }}</div></div>
    <div class="total-box"><div class="label">Total Invoices</div><div class="value">{{ $invoices->count() }}</div></div>
</div>

<table>
    <thead>
        <tr>
            <th>Invoice #</th>
            <th>Client</th>
            <th>Project</th>
            <th>Status</th>
            <th>Currency</th>
            <th>Subtotal</th>
            <th>Total</th>
            <th>Due Date</th>
            <th>Created</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $invoice)
        <tr>
            <td>{{ $invoice->number }}</td>
            <td>{{ $invoice->client?->company_name ?? $invoice->client?->primary_contact_name }}</td>
            <td>{{ $invoice->project?->title }}</td>
            <td><span class="badge badge-{{ $invoice->status }}">{{ $invoice->status }}</span></td>
            <td>{{ $invoice->currency ?? 'GBP' }}</td>
            <td>{{ $money->format($invoice->subtotal, $invoice->currency) }}</td>
            <td><strong>{{ $money->format($invoice->total, $invoice->currency) }}</strong></td>
            <td>{{ $invoice->due_date?->format('d M Y') }}</td>
            <td>{{ $invoice->created_at?->format('d M Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
