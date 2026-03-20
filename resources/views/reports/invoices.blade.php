@extends('reports.layout')

@section('title', 'Invoices Report')

@section('content')
<div class="export-bar">
    <a href="{{ route('reports.invoices.html') }}" class="active">HTML</a>
    <a href="{{ route('reports.invoices.pdf') }}">PDF</a>
    <a href="{{ route('reports.invoices.xlsx') }}">Excel</a>
    <a href="{{ route('reports.invoices.csv') }}">CSV</a>
</div>

<div class="totals">
    <div class="total-box"><div class="label">Paid</div><div class="value">£{{ number_format($totals['paid'], 0) }}</div></div>
    <div class="total-box"><div class="label">Sent</div><div class="value">£{{ number_format($totals['sent'], 0) }}</div></div>
    <div class="total-box"><div class="label">Overdue</div><div class="value">£{{ number_format($totals['overdue'], 0) }}</div></div>
    <div class="total-box"><div class="label">Draft</div><div class="value">£{{ number_format($totals['draft'], 0) }}</div></div>
    <div class="total-box"><div class="label">Total Invoices</div><div class="value">{{ collect($totals)->count() > 0 ? $invoices->count() : 0 }}</div></div>
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
            <td>{{ number_format($invoice->subtotal, 2) }}</td>
            <td><strong>{{ number_format($invoice->total, 2) }}</strong></td>
            <td>{{ $invoice->due_date?->format('d M Y') }}</td>
            <td>{{ $invoice->created_at?->format('d M Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
