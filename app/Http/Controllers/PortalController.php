<?php

namespace App\Http\Controllers;

use App\Models\Client;
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
