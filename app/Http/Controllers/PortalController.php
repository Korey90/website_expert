<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use App\Services\PayuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    private function clientForUser(): ?Client
    {
        return Client::where('portal_user_id', auth()->id())->first();
    }

    public function dashboard(): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            return redirect()->route('dashboard')
                ->with('error', 'No client profile linked to your account.');
        }

        $projects = Project::where('client_id', $client->id)
            ->withCount([
                'tasks',
                'tasks as tasks_done_count' => fn ($q) => $q->where('status', 'done'),
                'phases',
                'phases as phases_done_count' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->latest()
            ->take(5)
            ->get(['id', 'title', 'status', 'deadline', 'start_date']);

        $invoices = Invoice::where('client_id', $client->id)
            ->latest()
            ->take(5)
            ->get(['id', 'number', 'status', 'total', 'amount_due', 'due_date', 'issue_date']);

        $quotes = Quote::where('client_id', $client->id)
            ->latest()
            ->take(5)
            ->get(['id', 'number', 'status', 'total', 'valid_until']);

        return Inertia::render('Portal/Dashboard', [
            'client'   => $client->only('id', 'company_name', 'primary_contact_name'),
            'projects' => $projects,
            'invoices' => $invoices,
            'quotes'   => $quotes,
        ]);
    }

    public function projects(): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            return redirect()->route('dashboard');
        }

        $projects = Project::where('client_id', $client->id)
            ->latest()
            ->get(['id', 'title', 'status', 'service_type', 'deadline', 'start_date', 'budget', 'currency']);

        return Inertia::render('Portal/Projects', [
            'client'   => $client->only('id', 'company_name'),
            'projects' => $projects,
        ]);
    }

    public function project(Project $project): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $project->client_id !== $client->id) {
            abort(403);
        }

        $project->load([
            'phases.tasks',
            'messages' => fn ($q) => $q->orderBy('created_at'),
        ]);

        // Mark unread messages as read
        $project->messages()
            ->whereNull('read_at')
            ->where('sender_type', '!=', User::class)
            ->update(['read_at' => now()]);

        return Inertia::render('Portal/Project', [
            'client'  => $client->only('id', 'company_name'),
            'project' => $project,
        ]);
    }

    public function invoices(): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            return redirect()->route('dashboard');
        }

        $invoices = Invoice::where('client_id', $client->id)
            ->latest()
            ->get(['id', 'number', 'status', 'total', 'amount_due', 'amount_paid', 'due_date', 'issue_date', 'currency', 'stripe_payment_link']);

        return Inertia::render('Portal/Invoices', [
            'client'   => $client->only('id', 'company_name'),
            'invoices' => $invoices,
        ]);
    }

    public function invoice(Invoice $invoice): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $invoice->client_id !== $client->id) {
            abort(403);
        }

        if ($invoice->status === 'draft') {
            abort(403);
        }

        $invoice->load('items');

        return Inertia::render('Portal/Invoice', [
            'client'  => $client->only('id', 'company_name'),
            'invoice' => $invoice,
        ]);
    }

    public function quotes(): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            return redirect()->route('dashboard');
        }

        $quotes = Quote::where('client_id', $client->id)
            ->latest()
            ->get(['id', 'number', 'status', 'total', 'valid_until', 'currency', 'sent_at', 'accepted_at']);

        return Inertia::render('Portal/Quotes', [
            'client' => $client->only('id', 'company_name'),
            'quotes' => $quotes,
        ]);
    }

    public function quote(Quote $quote): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $quote->client_id !== $client->id) {
            abort(403);
        }

        $quote->load('items');

        return Inertia::render('Portal/Quote', [
            'client' => $client->only('id', 'company_name'),
            'quote'  => $quote,
        ]);
    }

    public function acceptQuote(Quote $quote): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $quote->client_id !== $client->id) {
            abort(403);
        }

        if ($quote->status !== 'sent') {
            return redirect()->route('portal.quote', $quote)->with('error', 'This quote can no longer be accepted.');
        }

        $quote->update([
            'status'      => 'accepted',
            'accepted_at' => now(),
        ]);

        return redirect()->route('portal.quote', $quote)->with('success', 'Quote accepted! We will be in touch shortly.');
    }

    public function rejectQuote(Quote $quote): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $quote->client_id !== $client->id) {
            abort(403);
        }

        if ($quote->status !== 'sent') {
            return redirect()->route('portal.quote', $quote)->with('error', 'This quote can no longer be rejected.');
        }

        $quote->update([
            'status'      => 'rejected',
            'rejected_at' => now(),
        ]);

        return redirect()->route('portal.quote', $quote)->with('success', 'Quote rejected.');
    }

    public function contracts(): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            return redirect()->route('dashboard');
        }

        $contracts = Contract::where('client_id', $client->id)
            ->whereIn('status', ['sent', 'signed', 'expired', 'cancelled'])
            ->latest()
            ->get(['id', 'number', 'title', 'status', 'value', 'currency', 'starts_at', 'expires_at', 'signed_at']);

        return Inertia::render('Portal/Contracts', [
            'client'    => $client->only('id', 'company_name'),
            'contracts' => $contracts,
        ]);
    }

    public function contract(Contract $contract): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $contract->client_id !== $client->id) {
            abort(403);
        }

        if (! in_array($contract->status, ['sent', 'signed', 'expired', 'cancelled'])) {
            abort(403);
        }

        return Inertia::render('Portal/Contract', [
            'client'   => $client->only('id', 'company_name'),
            'contract' => $contract->only(
                'id', 'number', 'title', 'status', 'value', 'currency',
                'terms', 'notes', 'starts_at', 'expires_at', 'sent_at', 'signed_at',
                'signer_name', 'signer_ip'
            ),
        ]);
    }

    public function signContract(Request $request, Contract $contract): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $contract->client_id !== $client->id) {
            abort(403);
        }

        if ($contract->status !== 'sent') {
            return redirect()->route('portal.contract', $contract)
                ->with('error', 'This contract can no longer be signed.');
        }

        $validated = $request->validate([
            'signer_name'    => ['required', 'string', 'max:200'],
            'signature_data' => ['nullable', 'string'],
            'confirmed'      => ['required', 'accepted'],
        ]);

        $contract->update([
            'status'         => 'signed',
            'signed_at'      => now(),
            'signer_name'    => $validated['signer_name'],
            'signer_ip'      => $request->ip(),
            'signature_data' => $validated['signature_data'] ?? null,
        ]);

        return redirect()->route('portal.contract', $contract)
            ->with('success', 'Contract signed successfully. Thank you!');
    }

    public function postMessage(Request $request, Project $project): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $project->client_id !== $client->id) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        ProjectMessage::create([
            'project_id'  => $project->id,
            'sender_type' => Client::class,
            'sender_id'   => $client->id,
            'content'     => $validated['content'],
        ]);

        return redirect()->back();
    }

    // ─── Payments ─────────────────────────────────────────────────────────────

    public function selectPaymentMethod(Invoice $invoice): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $invoice->client_id !== $client->id) {
            abort(403);
        }

        if (in_array($invoice->status, ['draft', 'cancelled', 'paid'])) {
            return redirect()->route('portal.invoice', $invoice)
                ->with('error', 'This invoice cannot be paid online.');
        }

        return Inertia::render('Portal/PayInvoice', [
            'invoice'        => $invoice->load('items'),
            'client'         => $client->only('id', 'company_name'),
            'stripeEnabled'  => (bool) Setting::get('stripe_enabled', false),
            'payuEnabled'    => (bool) Setting::get('payu_enabled', false),
            'stripePk'       => Setting::get('stripe_pk', ''),
        ]);
    }

    public function stripeCheckout(Invoice $invoice): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $invoice->client_id !== $client->id) {
            abort(403);
        }

        if (in_array($invoice->status, ['draft', 'cancelled', 'paid'])) {
            abort(422, 'Invoice cannot be paid.');
        }

        $sk = Setting::get('stripe_sk', config('services.stripe.secret', ''));
        abort_if(empty($sk) || ! Setting::get('stripe_enabled'), 503, 'Stripe payments are not enabled.');

        \Stripe\Stripe::setApiKey($sk);

        $amountDue = (int) round(($invoice->amount_due ?? 0) * 100);
        $currency  = strtolower($invoice->currency ?? Setting::get('payment_currency', 'GBP'));

        $session = \Stripe\Checkout\Session::create([
            'mode'           => 'payment',
            'customer_email' => $invoice->client?->primary_contact_email,
            'line_items'     => [[
                'price_data' => [
                    'currency'     => $currency,
                    'product_data' => ['name' => 'Invoice ' . $invoice->number],
                    'unit_amount'  => $amountDue,
                ],
                'quantity' => 1,
            ]],
            'metadata'    => [
                'invoice_id' => $invoice->id,
                'client_id'  => $invoice->client_id,
            ],
            'success_url' => route('portal.invoice', $invoice) . '?payment=success',
            'cancel_url'  => route('portal.invoice', $invoice),
        ]);

        return redirect($session->url);
    }

    public function payuInitiate(Invoice $invoice): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $invoice->client_id !== $client->id) {
            abort(403);
        }

        if (in_array($invoice->status, ['draft', 'cancelled', 'paid'])) {
            abort(422, 'Invoice cannot be paid.');
        }

        abort_if(! Setting::get('payu_enabled'), 503, 'PayU payments are not enabled.');

        $payu = new PayuService();

        $returnUrl = route('portal.invoice', $invoice) . '?payment=success';
        $notifyUrl = route('payu.notify');

        $result = $payu->createOrder($invoice, $client, $returnUrl, $notifyUrl);

        return redirect($result['redirectUri']);
    }

    // ─── Communication Preferences ───────────────────────────────────────

    public function notificationSettings(): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            return redirect()->route('portal.dashboard');
        }

        return Inertia::render('Portal/NotificationSettings', [
            'client' => $client->only('id', 'company_name'),
            'prefs'  => [
                'notify_email_transactional' => (bool) $client->notify_email_transactional,
                'notify_email_projects'      => (bool) $client->notify_email_projects,
                'notify_email_marketing'     => (bool) $client->notify_email_marketing,
                'notify_sms'                 => (bool) $client->notify_sms,
                'updated_at'                 => $client->communication_prefs_updated_at?->toISOString(),
            ],
        ]);
    }

    public function updateNotificationSettings(Request $request): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            abort(403);
        }

        $validated = $request->validate([
            'notify_email_transactional' => ['required', 'boolean'],
            'notify_email_projects'      => ['required', 'boolean'],
            'notify_email_marketing'     => ['required', 'boolean'],
            'notify_sms'                 => ['required', 'boolean'],
        ]);

        $client->update(array_merge($validated, [
            'communication_prefs_updated_at' => now(),
        ]));

        return redirect()->route('portal.settings.notifications')
            ->with('success', 'Communication preferences saved.');
    }
}
