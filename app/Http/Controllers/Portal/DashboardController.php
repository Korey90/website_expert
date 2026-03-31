<?php

namespace App\Http\Controllers\Portal;

use App\Models\ClientActivity;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends BasePortalController
{
    public function index(): Response|RedirectResponse
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

        $timeline = ClientActivity::where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get(['id', 'event_type', 'title', 'description', 'created_at']);

        return Inertia::render('Portal/Dashboard', [
            'client'   => $client->only('id', 'company_name', 'primary_contact_name'),
            'projects' => $projects,
            'invoices' => $invoices,
            'quotes'   => $quotes,
            'timeline' => $timeline,
        ]);
    }
}
