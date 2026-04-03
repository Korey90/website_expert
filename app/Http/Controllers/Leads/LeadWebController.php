<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\Leads\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadWebController extends Controller
{
    public function __construct(private readonly LeadService $leadService) {}

    public function show(Lead $lead): Response
    {
        $this->authorize('view', $lead);

        $lead->load([
            'client',
            'stage',
            'assignedTo',
            'leadSource.landingPage',
            'consent',
            'activities.user',
        ]);

        return Inertia::render('Leads/Show', [
            'lead'   => $lead,
            'stages' => PipelineStage::orderBy('order')->get(['id', 'name']),
            'users'  => User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))->get(['id', 'name']),
        ]);
    }

    public function assign(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $request->validate(['assigned_to' => ['required', 'integer', 'exists:users,id']]);

        $assignee = User::findOrFail($request->assigned_to);
        $this->leadService->assign($lead, $assignee, auth()->user());

        return back()->with('success', __('leads.assigned', ['user' => $assignee->name]));
    }

    public function stage(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $request->validate(['pipeline_stage_id' => ['required', 'integer', 'exists:pipeline_stages,id']]);

        $stage = PipelineStage::findOrFail($request->pipeline_stage_id);
        $this->leadService->updateStage($lead, $stage, auth()->user());

        return back()->with('success', __('leads.stage_moved', ['stage' => $stage->name]));
    }

    public function won(Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $this->leadService->markWon($lead, auth()->user());

        return back()->with('success', __('leads.marked_won'));
    }

    public function lost(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $this->leadService->markLost($lead, $request->reason, auth()->user());

        return back()->with('success', __('leads.marked_lost', ['reason' => $request->reason]));
    }
}
