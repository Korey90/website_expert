@extends('reports.layout')

@section('title', 'Projects Report')

@section('content')
<div class="export-bar">
    <a href="{{ route('reports.projects.html') }}" class="active">HTML</a>
    <a href="{{ route('reports.projects.pdf') }}">PDF</a>
    <a href="{{ route('reports.projects.xlsx') }}">Excel</a>
    <a href="{{ route('reports.projects.csv') }}">CSV</a>
</div>

<p>Total projects: <strong>{{ $projects->count() }}</strong></p>

<table>
    <thead>
        <tr>
            <th>Title</th>
            <th>Client</th>
            <th>Service</th>
            <th>Status</th>
            <th>Budget</th>
            <th>Currency</th>
            <th>Start</th>
            <th>Due</th>
        </tr>
    </thead>
    <tbody>
        @foreach($projects as $project)
        <tr>
            <td>{{ $project->title }}</td>
            <td>{{ $project->client?->company_name ?? $project->client?->primary_contact_name }}</td>
            <td>{{ $project->service_type }}</td>
            <td><span class="badge badge-{{ $project->status }}">{{ str_replace('_', ' ', $project->status) }}</span></td>
            <td>{{ $project->budget ? '£' . number_format($project->budget, 0) : '—' }}</td>
            <td>{{ $project->currency ?? 'GBP' }}</td>
            <td>{{ $project->start_date?->format('d M Y') }}</td>
            <td>{{ $project->due_date?->format('d M Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
