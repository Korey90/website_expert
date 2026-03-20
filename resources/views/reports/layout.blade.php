<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — WebsiteExpert Reports</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #374151; margin: 0; padding: 16px; }
        .report-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #dc2626; padding-bottom: 12px; margin-bottom: 16px; }
        .report-header h1 { margin: 0; font-size: 18px; color: #dc2626; }
        .report-header .meta { font-size: 10px; color: #6b7280; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        thead tr { background: #dc2626; color: #fff; }
        thead th { padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .badge-draft     { background: #e5e7eb; color: #6b7280; }
        .badge-sent      { background: #dbeafe; color: #1e40af; }
        .badge-paid      { background: #dcfce7; color: #166534; }
        .badge-overdue   { background: #fee2e2; color: #991b1b; }
        .badge-cancelled { background: #f3f4f6; color: #6b7280; }
        .badge-new       { background: #ede9fe; color: #5b21b6; }
        .badge-won       { background: #dcfce7; color: #166534; }
        .badge-lost      { background: #fee2e2; color: #991b1b; }
        .badge-planning    { background: #dbeafe; color: #1e40af; }
        .badge-in_progress { background: #fef9c3; color: #854d0e; }
        .badge-review      { background: #ede9fe; color: #5b21b6; }
        .badge-completed   { background: #dcfce7; color: #166534; }
        .badge-on_hold     { background: #f3f4f6; color: #374151; }
        .totals { margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap; }
        .total-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 16px; min-width: 120px; }
        .total-box .label { font-size: 9px; text-transform: uppercase; color: #6b7280; }
        .total-box .value { font-size: 16px; font-weight: 700; color: #111827; margin-top: 2px; }
        .export-bar { margin-bottom: 16px; display: flex; gap: 8px; flex-wrap: wrap; }
        .export-bar a { display: inline-block; padding: 6px 14px; background: #374151; color: #fff; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .export-bar a.active { background: #dc2626; }
        .export-bar a:hover { opacity: .85; }
        @media print { .export-bar { display: none; } }
    </style>
</head>
<body>
<div class="report-header">
    <h1>@yield('title')</h1>
    <div class="meta">
        WebsiteExpert<br>
        Generated: {{ now()->format('d M Y H:i') }}
    </div>
</div>

@yield('content')
</body>
</html>
