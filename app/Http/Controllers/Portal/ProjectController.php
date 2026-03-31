<?php

namespace App\Http\Controllers\Portal;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends BasePortalController
{
    public function index(): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            return redirect()->route('portal.dashboard');
        }

        $projects = Project::where('client_id', $client->id)
            ->latest()
            ->get(['id', 'title', 'status', 'service_type', 'deadline', 'start_date', 'budget', 'currency']);

        return Inertia::render('Portal/Projects', [
            'client'   => $client->only('id', 'company_name'),
            'projects' => $projects,
        ]);
    }

    public function show(Project $project): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $project->client_id !== $client->id) {
            abort(403);
        }

        $project->load([
            'phases.tasks',
            'messages' => fn ($q) => $q->orderBy('created_at'),
        ]);

        // Mark unread agency messages as read (client has now seen them)
        $project->messages()
            ->whereNull('read_at')
            ->where('sender_type', User::class)
            ->update(['read_at' => now()]);

        return Inertia::render('Portal/Project', [
            'client'  => $client->only('id', 'company_name'),
            'project' => $project,
        ]);
    }

    public function storeMessage(Request $request, Project $project): RedirectResponse
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
