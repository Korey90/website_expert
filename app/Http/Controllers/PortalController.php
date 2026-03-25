<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\Quote;
use App\Models\User;
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
}
