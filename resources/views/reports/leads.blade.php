@extends('reports.layout')

@section('title', 'Leads Report')

@section('content')
<div class="export-bar">
    <a href="{{ route('reports.leads.html') }}" class="active">HTML</a>
    <a href="{{ route('reports.leads.pdf') }}">PDF</a>
    <a href="{{ route('reports.leads.xlsx') }}">Excel</a>
    <a href="{{ route('reports.leads.csv') }}">CSV</a>
</div>

<p>Total leads: <strong>{{ $leads->count() }}</strong></p>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Company</th>
            <th>Source</th>
            <th>Status</th>
            <th>Stage</th>
            <th>Value</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($leads as $lead)
        <tr>
            <td>{{ $lead->id }}</td>
            <td>{{ $lead->name ?? $lead->client?->primary_contact_name }}</td>
            <td>{{ $lead->email ?? $lead->client?->primary_contact_email }}</td>
            <td>{{ $lead->company ?? $lead->client?->company_name }}</td>
            <td>{{ $lead->source }}</td>
            <td><span class="badge badge-{{ $lead->status }}">{{ $lead->status }}</span></td>
            <td>{{ $lead->stage?->name }}</td>
            <td>{{ $lead->value ? '£' . number_format($lead->value, 0) : '—' }}</td>
            <td>{{ $lead->created_at?->format('d M Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
