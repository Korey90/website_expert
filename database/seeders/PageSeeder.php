<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@websiteexpert.co.uk')->first();

        $pages = [
            [
                'title'            => 'Privacy Policy',
                'slug'             => 'privacy-policy',
                'meta_title'       => 'Privacy Policy | WebsiteExpert',
                'meta_description' => 'How WebsiteExpert collects, uses, and protects your personal data in compliance with UK GDPR.',
                'status'           => 'published',
                'type'             => 'policy',
                'show_in_footer'   => true,
                'sort_order'       => 1,
                'created_by'       => $admin?->id,
                'published_at'     => now()->subMonths(6),
                'content'          => <<<'HTML'
<h2>Privacy Policy</h2>
<p><strong>Last updated: January 2025</strong></p>

<p>WebsiteExpert Ltd ("we", "us", or "our") is committed to protecting your personal data and respecting your privacy in accordance with the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018.</p>

<h3>1. Who We Are</h3>
<p>WebsiteExpert Ltd is a company registered in England and Wales. Our registered address is [Address], Manchester. We are the data controller for the personal data we collect and process about you.</p>

<h3>2. What Personal Data We Collect</h3>
<p>We may collect and process the following personal data about you:</p>
<ul>
  <li><strong>Contact information:</strong> name, email address, phone number, business address</li>
  <li><strong>Business information:</strong> company name, Companies House number, VAT number</li>
  <li><strong>Communications:</strong> emails, enquiry form submissions, live chat transcripts</li>
  <li><strong>Technical data:</strong> IP address, browser type, operating system, pages visited, time spent on our website</li>
  <li><strong>Financial data:</strong> invoice and payment records (no card details stored — payments processed via Stripe)</li>
</ul>

<h3>3. How We Use Your Personal Data</h3>
<p>We use your personal data for the following purposes:</p>
<ul>
  <li>To provide and manage our web development and digital marketing services</li>
  <li>To respond to enquiries and provide quotes</li>
  <li>To send invoices and manage payments</li>
  <li>To send project updates and notifications (with your consent)</li>
  <li>To improve our website and services</li>
  <li>To comply with our legal and regulatory obligations</li>
</ul>

<h3>4. Legal Basis for Processing</h3>
<p>We rely on the following lawful bases under UK GDPR:</p>
<ul>
  <li><strong>Contract:</strong> processing necessary for the performance of a contract with you</li>
  <li><strong>Legitimate interests:</strong> improving our services, fraud prevention</li>
  <li><strong>Legal obligation:</strong> tax and accounting records</li>
  <li><strong>Consent:</strong> marketing communications (you may withdraw consent at any time)</li>
</ul>

<h3>5. Data Retention</h3>
<p>We retain personal data for as long as necessary to fulfil the purposes for which it was collected, including for legal, accounting, and reporting requirements. Financial records are retained for 7 years in compliance with HMRC requirements.</p>

<h3>6. Your Rights</h3>
<p>Under UK GDPR, you have the right to:</p>
<ul>
  <li>Access your personal data</li>
  <li>Rectify inaccurate data</li>
  <li>Erase your data ("right to be forgotten")</li>
  <li>Restrict or object to processing</li>
  <li>Data portability</li>
  <li>Withdraw consent at any time</li>
</ul>
<p>To exercise any of these rights, please contact us at <a href="mailto:privacy@websiteexpert.co.uk">privacy@websiteexpert.co.uk</a>.</p>

<h3>7. Cookies</h3>
<p>We use cookies to improve your experience on our website. Please see our <a href="/cookies">Cookie Policy</a> for full details.</p>

<h3>8. Third Parties</h3>
<p>We may share your data with trusted third-party service providers, including:</p>
<ul>
  <li>Stripe (payment processing) — <a href="https://stripe.com/gb/privacy" rel="noopener">Stripe Privacy Policy</a></li>
  <li>Google Analytics (website analytics)</li>
  <li>Our hosting providers (data stored in the UK/EEA)</li>
</ul>

<h3>9. Contact Us</h3>
<p>If you have any questions about this Privacy Policy or how we handle your personal data, please contact:</p>
<p><strong>WebsiteExpert Ltd</strong><br>
Email: <a href="mailto:privacy@websiteexpert.co.uk">privacy@websiteexpert.co.uk</a></p>

<h3>10. Complaints</h3>
<p>If you believe we have not handled your personal data correctly, you have the right to lodge a complaint with the Information Commissioner's Office (ICO): <a href="https://www.ico.org.uk" rel="noopener">www.ico.org.uk</a></p>
HTML,
            ],
            [
                'title'            => 'Terms & Conditions',
                'slug'             => 'terms-and-conditions',
                'meta_title'       => 'Terms & Conditions | WebsiteExpert',
                'meta_description' => 'Terms and conditions governing the provision of web development and digital marketing services by WebsiteExpert Ltd.',
                'status'           => 'published',
                'type'             => 'terms',
                'show_in_footer'   => true,
                'sort_order'       => 2,
                'created_by'       => $admin?->id,
                'published_at'     => now()->subMonths(6),
                'content'          => <<<'HTML'
<h2>Terms & Conditions</h2>
<p><strong>Last updated: January 2025</strong></p>

<p>These Terms and Conditions ("Terms") govern all services provided by WebsiteExpert Ltd ("the Company", "we", "us") to our clients ("Client", "you"). By engaging our services or accepting a quote, you agree to be bound by these Terms.</p>

<h3>1. Services</h3>
<p>WebsiteExpert Ltd provides web design, web development, digital marketing, hosting, and related services as outlined in individual project quotes or service agreements.</p>

<h3>2. Quotes & Proposals</h3>
<p>All quotes are valid for 30 days from the date of issue unless otherwise stated. Quotes are based on information provided at the time of enquiry. Scope changes may result in revised pricing.</p>

<h3>3. Project Kickoff & Deposits</h3>
<p>Projects commence only upon receipt of a signed agreement (or written confirmation) and the agreed deposit (typically 40–50% of the project total). Work will not begin until the deposit has cleared.</p>

<h3>4. Payment Terms</h3>
<ul>
  <li>Invoices are due within 14 days of issue unless otherwise agreed in writing</li>
  <li>Late payments may incur interest at 8% above the Bank of England base rate per annum, in accordance with the Late Payment of Commercial Debts (Interest) Act 1998</li>
  <li>Work may be suspended on accounts more than 30 days overdue</li>
</ul>

<h3>5. Intellectual Property</h3>
<p>Upon receipt of full payment, copyright in the final deliverables transfers to the Client. The Company retains the right to display the work in its portfolio unless otherwise agreed in writing. Third-party assets (stock images, fonts, plugins) are subject to their own licences.</p>

<h3>6. Client Responsibilities</h3>
<p>The Client is responsible for:</p>
<ul>
  <li>Providing accurate content, materials, and timely feedback</li>
  <li>Ensuring content supplied does not infringe third-party rights</li>
  <li>Obtaining any required third-party licences or consents</li>
  <li>Compliance with relevant laws (GDPR, Consumer Rights Act, etc.)</li>
</ul>

<h3>7. Project Timelines</h3>
<p>Estimated timelines are provided in good faith but are not binding unless a specific deadline is agreed and stated in the contract. Delays caused by late content or feedback from the Client may extend the timeline.</p>

<h3>8. Revisions</h3>
<p>All projects include a defined number of revision rounds as stated in the quote. Additional revisions beyond the agreed scope will be charged at our current hourly rate (£65/hour).</p>

<h3>9. Hosting & Maintenance</h3>
<p>Hosting and maintenance services are provided on a rolling monthly or annual basis. Either party may terminate with 30 days' written notice following any minimum term. The Company is not liable for downtime caused by third-party hosting providers, force majeure, or circumstances beyond our control.</p>

<h3>10. Limitation of Liability</h3>
<p>The Company's total liability to the Client shall not exceed the total fees paid for the specific service giving rise to the claim. The Company is not liable for loss of profits, data, business opportunity, or indirect losses.</p>

<h3>11. Governing Law</h3>
<p>These Terms are governed by the laws of England and Wales. Any disputes shall be subject to the exclusive jurisdiction of the courts of England and Wales.</p>

<h3>12. Contact</h3>
<p>WebsiteExpert Ltd — <a href="mailto:hello@websiteexpert.co.uk">hello@websiteexpert.co.uk</a></p>
HTML,
            ],
            [
                'title'            => 'Cookie Policy',
                'slug'             => 'cookies',
                'meta_title'       => 'Cookie Policy | WebsiteExpert',
                'meta_description' => 'Information about the cookies used on the WebsiteExpert website and how to manage your preferences.',
                'status'           => 'published',
                'type'             => 'cookie_policy',
                'show_in_footer'   => true,
                'sort_order'       => 3,
                'created_by'       => $admin?->id,
                'published_at'     => now()->subMonths(6),
                'content'          => <<<'HTML'
<h2>Cookie Policy</h2>
<p><strong>Last updated: January 2025</strong></p>

<p>This Cookie Policy explains how WebsiteExpert Ltd uses cookies and similar tracking technologies on our website (<a href="https://websiteexpert.co.uk">websiteexpert.co.uk</a>).</p>

<h3>What Are Cookies?</h3>
<p>Cookies are small text files placed on your device when you visit a website. They are widely used to make websites work more efficiently, provide a better user experience, and give site owners information about how the site is used.</p>

<h3>Types of Cookies We Use</h3>

<h4>Strictly Necessary Cookies</h4>
<p>These cookies are essential for the website to function and cannot be disabled. They include session management, security tokens, and CSRF protection. No personal data is stored in these cookies.</p>

<h4>Analytics Cookies</h4>
<p>We use Google Analytics (GA4) to understand how visitors interact with our website. Data collected includes pages visited, time on site, and general location (country/city level). IP addresses are anonymised. You can opt out of Google Analytics at <a href="https://tools.google.com/dlpage/gaoptout" rel="noopener">tools.google.com/dlpage/gaoptout</a>.</p>

<h4>Functional Cookies</h4>
<p>These cookies remember your preferences (e.g. contact form data, language settings) to improve your experience.</p>

<h4>Marketing Cookies</h4>
<p>If you have consented, we may use marketing cookies (e.g. Google Ads, LinkedIn Insight Tag) to show relevant advertisements. These are only placed with your explicit consent.</p>

<h3>Managing Cookies</h3>
<p>You can control and/or delete cookies at any time using our cookie consent banner or through your browser settings. Please note that disabling certain cookies may affect the functionality of our website.</p>

<p>For more information about managing cookies, visit <a href="https://www.allaboutcookies.org" rel="noopener">allaboutcookies.org</a>.</p>

<h3>Contact</h3>
<p>If you have questions about our use of cookies, please contact us at <a href="mailto:privacy@websiteexpert.co.uk">privacy@websiteexpert.co.uk</a>.</p>
HTML,
            ],
            [
                'title'            => 'Accessibility Statement',
                'slug'             => 'accessibility',
                'meta_title'       => 'Accessibility Statement | WebsiteExpert',
                'meta_description' => 'WebsiteExpert is committed to making its website accessible to all users.',
                'status'           => 'published',
                'type'             => 'other',
                'show_in_footer'   => false,
                'sort_order'       => 4,
                'created_by'       => $admin?->id,
                'published_at'     => now()->subMonths(3),
                'content'          => <<<'HTML'
<h2>Accessibility Statement</h2>
<p><strong>Last updated: January 2025</strong></p>

<p>WebsiteExpert Ltd is committed to ensuring digital accessibility for people with disabilities. We continually work to improve the user experience for everyone according to the Web Content Accessibility Guidelines (WCAG) 2.1 Level AA.</p>

<h3>Our Commitment</h3>
<ul>
  <li>All images include descriptive alt text</li>
  <li>Colour contrast meets WCAG 2.1 AA minimum ratios</li>
  <li>The website is navigable by keyboard alone</li>
  <li>Screen reader compatibility has been tested</li>
  <li>Forms include clear labels and error messages</li>
  <li>Text can be resized up to 200% without loss of content or functionality</li>
</ul>

<h3>Reporting Accessibility Issues</h3>
<p>If you experience any accessibility barriers on our website, please contact us:</p>
<p>Email: <a href="mailto:hello@websiteexpert.co.uk">hello@websiteexpert.co.uk</a></p>
<p>We aim to respond to all accessibility queries within 5 working days.</p>
HTML,
            ],
        ];

        foreach ($pages as $data) {
            Page::firstOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
