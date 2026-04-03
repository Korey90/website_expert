<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New lead assigned — WebsiteExpert</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f5; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #ff2b17; padding: 28px 32px; }
        .header h1 { color: #fff; margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
        .body { padding: 28px 32px; }
        .label { font-size: 11px; text-transform: uppercase; letter-spacing: .8px; color: #9ca3af; margin-bottom: 2px; }
        .value { font-size: 15px; color: #111827; margin-bottom: 16px; word-break: break-word; }
        .message-box { background: #f9fafb; border-left: 4px solid #ff2b17; padding: 14px 18px; border-radius: 4px; font-size: 15px; color: #374151; line-height: 1.6; margin-bottom: 24px; }
        .btn { display: inline-block; background: #ff2b17; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .badge { display: inline-block; background: #eff6ff; color: #1d4ed8; border-radius: 4px; padding: 2px 8px; font-size: 12px; font-weight: 600; }
        .footer { background: #f4f4f5; padding: 18px 32px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>👋 New lead assigned to you</h1>
        </div>
        <div class="body">
            <p style="color:#374151; margin-bottom:24px;">
                Hi {{ $assignee->name }},<br>
                a new lead has been assigned to you from a landing page.
            </p>

            <p class="label">Lead</p>
            <p class="value">{{ $lead->title }} <span class="badge">landing_page</span></p>

            @if($lead->client)
            <p class="label">Contact</p>
            <p class="value">
                {{ $lead->client->primary_contact_name }}<br>
                <a href="mailto:{{ $lead->client->primary_contact_email }}" style="color:#ff2b17">
                    {{ $lead->client->primary_contact_email }}
                </a>
                @if($lead->client->primary_contact_phone)
                    &nbsp;·&nbsp;
                    <a href="tel:{{ $lead->client->primary_contact_phone }}" style="color:#ff2b17">
                        {{ $lead->client->primary_contact_phone }}
                    </a>
                @endif
            </p>

            @if($lead->client->company_name)
            <p class="label">Company</p>
            <p class="value">{{ $lead->client->company_name }}</p>
            @endif
            @endif

            @if($lead->landingPage)
            <p class="label">Landing Page</p>
            <p class="value">{{ $lead->landingPage->title }}</p>
            @endif

            @if($lead->notes)
            <p class="label">Message</p>
            <div class="message-box">{{ $lead->notes }}</div>
            @endif

            @if($lead->budget_min || $lead->budget_max)
            <p class="label">Budget</p>
            <p class="value">
                {{ $lead->budget_min ? '£'.number_format($lead->budget_min) : '' }}
                {{ ($lead->budget_min && $lead->budget_max) ? '–' : '' }}
                {{ $lead->budget_max ? '£'.number_format($lead->budget_max) : '' }}
            </p>
            @endif

            @if($lead->utm_campaign)
            <p class="label">Campaign</p>
            <p class="value">{{ $lead->utm_campaign }}</p>
            @endif

            <p class="label">Received</p>
            <p class="value">{{ $lead->created_at->format('d M Y, H:i') }}</p>

            <br>
            <a href="{{ config('app.url') }}/admin/leads/{{ $lead->id }}" class="btn">
                View lead in CRM →
            </a>
        </div>
        <div class="footer">
            WebsiteExpert · This email was sent because you are assigned to this lead.
        </div>
    </div>
</body>
</html>
