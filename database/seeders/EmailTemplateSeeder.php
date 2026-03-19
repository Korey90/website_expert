<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'      => 'Welcome Email',
                'slug'      => 'welcome_email',
                'subject'   => 'Welcome to WebsiteExpert – Your Project Journey Starts Here',
                'variables' => ['client_name', 'project_title', 'manager_name', 'portal_url'],
                'is_active' => true,
                'body_html' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>Thank you for choosing <strong>WebsiteExpert</strong>. We're thrilled to be working with you on <strong>{{project_title}}</strong>.</p>

<p>Your dedicated project manager is <strong>{{manager_name}}</strong>, who will be your primary point of contact throughout the project.</p>

<p>You can track your project progress, review milestones, and send us messages via your secure client portal:</p>

<p><a href="{{portal_url}}" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Access Your Portal</a></p>

<p>If you have any questions, please don't hesitate to reach out to us at <a href="mailto:hello@websiteexpert.co.uk">hello@websiteexpert.co.uk</a>.</p>

<p>Warm regards,<br>
<strong>The WebsiteExpert Team</strong></p>
HTML,
                'body_text' => "Dear {{client_name}},\n\nThank you for choosing WebsiteExpert. We're thrilled to be working with you on {{project_title}}.\n\nYour dedicated project manager is {{manager_name}}.\n\nTrack your project: {{portal_url}}\n\nWarm regards,\nThe WebsiteExpert Team",
            ],
            [
                'name'      => 'Invoice Sent',
                'slug'      => 'invoice_sent',
                'subject'   => 'Invoice {{invoice_number}} from WebsiteExpert – Payment Due {{due_date}}',
                'variables' => ['client_name', 'invoice_number', 'invoice_total', 'due_date', 'invoice_url', 'payment_link'],
                'is_active' => true,
                'body_html' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>Please find attached invoice <strong>{{invoice_number}}</strong> for the amount of <strong>{{invoice_total}}</strong> (inc. VAT).</p>

<p><strong>Payment is due by: {{due_date}}</strong></p>

<p>You can pay securely online using the button below:</p>

<p><a href="{{payment_link}}" style="background:#10B981;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Pay Now</a></p>

<p>Alternatively, you can make a BACS payment using the details below:</p>
<ul>
  <li><strong>Account Name:</strong> WebsiteExpert Ltd</li>
  <li><strong>Sort Code:</strong> 20-00-00</li>
  <li><strong>Account Number:</strong> 12345678</li>
  <li><strong>Reference:</strong> {{invoice_number}}</li>
</ul>

<p>If you have any queries regarding this invoice, please contact us at <a href="mailto:accounts@websiteexpert.co.uk">accounts@websiteexpert.co.uk</a>.</p>

<p>Kind regards,<br>
<strong>WebsiteExpert Accounts Team</strong></p>
HTML,
                'body_text' => "Dear {{client_name}},\n\nPlease find attached invoice {{invoice_number}} for {{invoice_total}} (inc. VAT).\n\nPayment due: {{due_date}}\n\nPay online: {{payment_link}}\n\nBACS: WebsiteExpert Ltd | Sort: 20-00-00 | Acc: 12345678 | Ref: {{invoice_number}}\n\nKind regards,\nWebsiteExpert Accounts Team",
            ],
            [
                'name'      => 'Invoice Overdue Reminder',
                'slug'      => 'invoice_overdue',
                'subject'   => 'REMINDER: Invoice {{invoice_number}} is Overdue – Action Required',
                'variables' => ['client_name', 'invoice_number', 'invoice_total', 'due_date', 'days_overdue', 'payment_link'],
                'is_active' => true,
                'body_html' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>This is a friendly reminder that invoice <strong>{{invoice_number}}</strong> for <strong>{{invoice_total}}</strong> was due on <strong>{{due_date}}</strong> and is now <strong>{{days_overdue}} days overdue</strong>.</p>

<p>Please arrange payment at your earliest convenience:</p>

<p><a href="{{payment_link}}" style="background:#EF4444;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Pay Now</a></p>

<p>If you have already made this payment, please disregard this email and accept our apologies for the inconvenience.</p>

<p>If you are experiencing difficulties making payment, please contact us immediately at <a href="mailto:accounts@websiteexpert.co.uk">accounts@websiteexpert.co.uk</a> so we can discuss payment arrangements.</p>

<p>Kind regards,<br>
<strong>WebsiteExpert Accounts Team</strong></p>
HTML,
                'body_text' => "Dear {{client_name}},\n\nInvoice {{invoice_number}} for {{invoice_total}} was due on {{due_date}} and is now {{days_overdue}} days overdue.\n\nPay now: {{payment_link}}\n\nIf you have any questions, contact accounts@websiteexpert.co.uk\n\nKind regards,\nWebsiteExpert Accounts Team",
            ],
            [
                'name'      => 'Quote Sent',
                'slug'      => 'quote_sent',
                'subject'   => 'Your Quote from WebsiteExpert – Ref: {{quote_number}}',
                'variables' => ['client_name', 'quote_number', 'quote_total', 'valid_until', 'quote_url', 'manager_name'],
                'is_active' => true,
                'body_html' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>Thank you for your enquiry. Please find attached your personalised quote <strong>{{quote_number}}</strong> from WebsiteExpert.</p>

<p><strong>Quote Summary:</strong><br>
Total: <strong>{{quote_total}}</strong> (inc. VAT)<br>
Valid Until: <strong>{{valid_until}}</strong></p>

<p>You can view and accept your quote online:</p>

<p><a href="{{quote_url}}" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">View Quote</a></p>

<p>I'd love to jump on a quick call to walk you through everything — feel free to book a time that suits you at your convenience.</p>

<p>I look forward to hearing from you.</p>

<p>Best regards,<br>
<strong>{{manager_name}}</strong><br>
WebsiteExpert</p>
HTML,
                'body_text' => "Dear {{client_name}},\n\nPlease find attached your quote {{quote_number}}.\n\nTotal: {{quote_total}} (inc. VAT)\nValid Until: {{valid_until}}\n\nView quote: {{quote_url}}\n\nBest regards,\n{{manager_name}}\nWebsiteExpert",
            ],
            [
                'name'      => 'Quote Accepted – Project Kickoff',
                'slug'      => 'quote_accepted',
                'subject'   => 'Brilliant News! Your Quote is Confirmed – Next Steps Inside',
                'variables' => ['client_name', 'quote_number', 'project_title', 'manager_name', 'deposit_amount', 'invoice_url'],
                'is_active' => true,
                'body_html' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>Fantastic news — your quote <strong>{{quote_number}}</strong> has been confirmed and we're ready to get started on <strong>{{project_title}}</strong>!</p>

<p><strong>Next Steps:</strong></p>
<ol>
  <li>Pay your project deposit of <strong>{{deposit_amount}}</strong> — <a href="{{invoice_url}}">view deposit invoice</a></li>
  <li>We'll schedule a kick-off call within 48 hours of receiving your deposit</li>
  <li>We'll send you access to your client portal to track progress</li>
</ol>

<p>We're genuinely excited to work with you. If you have any questions before the kick-off call, please feel free to reach out.</p>

<p>Warm regards,<br>
<strong>{{manager_name}}</strong><br>
WebsiteExpert</p>
HTML,
                'body_text' => "Dear {{client_name}},\n\nYour quote {{quote_number}} has been confirmed for {{project_title}}.\n\nNext steps:\n1. Pay deposit of {{deposit_amount}}: {{invoice_url}}\n2. Kick-off call within 48 hours\n3. Client portal access\n\nWarm regards,\n{{manager_name}}\nWebsiteExpert",
            ],
            [
                'name'      => 'Project Phase Complete',
                'slug'      => 'project_phase_complete',
                'subject'   => 'Update on {{project_title}}: {{phase_name}} Complete ✓',
                'variables' => ['client_name', 'project_title', 'phase_name', 'next_phase', 'portal_url', 'manager_name'],
                'is_active' => true,
                'body_html' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>Great progress update for <strong>{{project_title}}</strong> — we've completed the <strong>{{phase_name}}</strong> phase!</p>

<p>We're now moving on to <strong>{{next_phase}}</strong>.</p>

<p>You can review the latest progress and provide any feedback in your client portal:</p>

<p><a href="{{portal_url}}" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">View Project Progress</a></p>

<p>If you have any feedback or questions, don't hesitate to drop us a message.</p>

<p>Best regards,<br>
<strong>{{manager_name}}</strong><br>
WebsiteExpert</p>
HTML,
                'body_text' => "Dear {{client_name}},\n\nWe've completed the {{phase_name}} phase of {{project_title}}.\n\nNext up: {{next_phase}}\n\nView progress: {{portal_url}}\n\nBest regards,\n{{manager_name}}\nWebsiteExpert",
            ],
            [
                'name'      => 'Project Launched',
                'slug'      => 'project_launched',
                'subject'   => '🚀 {{project_title}} is LIVE! Congratulations!',
                'variables' => ['client_name', 'project_title', 'website_url', 'manager_name', 'support_email'],
                'is_active' => true,
                'body_html' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>We are absolutely thrilled to announce that <strong>{{project_title}}</strong> is now LIVE! 🎉</p>

<p><a href="{{website_url}}">{{website_url}}</a></p>

<p>It's been a pleasure working with you on this project. Here's what's included in your handover pack:</p>
<ul>
  <li>Login credentials for your CMS / admin panel</li>
  <li>Hosting control panel access</li>
  <li>Google Analytics access</li>
  <li>Training video recording</li>
  <li>User guide PDF</li>
</ul>

<p>For ongoing support, please contact us at <a href="mailto:{{support_email}}">{{support_email}}</a>.</p>

<p>Don't forget to share the website on your social channels — we'd love a tag!</p>

<p>Warmest congratulations,<br>
<strong>{{manager_name}} & The WebsiteExpert Team</strong></p>
HTML,
                'body_text' => "Dear {{client_name}},\n\n{{project_title}} is now LIVE!\n\n{{website_url}}\n\nHandover pack sent separately.\n\nFor support: {{support_email}}\n\nCongratulations!\n{{manager_name}} & The WebsiteExpert Team",
            ],
        ];

        foreach ($templates as $data) {
            EmailTemplate::firstOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
