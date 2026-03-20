<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Update: {{ $project->title }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f5; margin: 0; padding: 32px 0; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .header { background: #dc2626; padding: 32px; color: #fff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 4px 0 0; opacity: .85; font-size: 14px; }
        .body { padding: 32px; color: #374151; }
        .body h2 { margin: 0 0 8px; font-size: 18px; color: #111827; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 13px; font-weight: 600; margin: 12px 0; }
        .badge-planning   { background: #dbeafe; color: #1e40af; }
        .badge-in_progress { background: #fef9c3; color: #854d0e; }
        .badge-review     { background: #ede9fe; color: #5b21b6; }
        .badge-completed  { background: #dcfce7; color: #166534; }
        .badge-on_hold    { background: #f3f4f6; color: #374151; }
        .badge-cancelled  { background: #fee2e2; color: #991b1b; }
        .btn { display: inline-block; background: #dc2626; color: #fff !important; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; margin: 20px 0; font-size: 15px; }
        .footer { padding: 20px 32px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>WebsiteExpert</h1>
        <p>Project Status Update</p>
    </div>
    <div class="body">
        <h2>Hello {{ $project->client->company_name ?? $project->client->primary_contact_name }},</h2>
        <p>We have an update on your project <strong>{{ $project->title }}</strong>.</p>

        <p>
            Status changed from
            <span class="badge badge-{{ $oldStatus }}">{{ ucfirst(str_replace('_', ' ', $oldStatus)) }}</span>
            to
            <span class="badge badge-{{ $project->status }}">{{ ucfirst(str_replace('_', ' ', $project->status)) }}</span>
        </p>

        @if($project->description)
            <p style="font-size:14px;color:#6b7280;">{{ Str::limit($project->description, 200) }}</p>
        @endif

        <a href="{{ config('app.url') }}/portal/projects/{{ $project->id }}" class="btn">
            View Project
        </a>

        <p style="font-size:13px;color:#6b7280;">If you have any questions, please reply to this email or contact your project manager directly.</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} WebsiteExpert. All rights reserved.
    </div>
</div>
</body>
</html>
