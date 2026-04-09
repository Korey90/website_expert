<p>Hi {{ $name }},</p>

<p>We're writing to confirm that your account on <strong>{{ config('app.name') }}</strong> has been
<strong>permanently deleted</strong> as requested.</p>

<p>The following data has been removed from our systems:</p>
<ul>
    <li>Your personal profile (name, email address, avatar)</li>
    <li>Your social login connections (Google, Facebook)</li>
    <li>Your business account and all associated settings</li>
</ul>

<p>In accordance with our legitimate business interests and applicable law, we retain anonymised
records of financial transactions (invoices, payments) for the legally required period.</p>

<p>If you did <strong>not</strong> request this deletion, please contact us immediately at
<a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>

<p>Thank you for using {{ config('app.name') }}. We're sorry to see you go.</p>
