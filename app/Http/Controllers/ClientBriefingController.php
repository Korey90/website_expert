<?php

namespace App\Http\Controllers;

use App\Models\Briefing;
use App\Services\BriefingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ClientBriefingController extends Controller
{
    public function __construct(
        private readonly BriefingService $service,
    ) {}

    /**
     * Display the client-facing briefing form.
     */
    public function show(string $token)
    {
        $briefing = Briefing::with(['template', 'lead', 'business'])
            ->where('client_token', $token)
            ->firstOrFail();

        // Already submitted — show read-only thank-you
        if ($briefing->client_submitted_at) {
            return Inertia::render('Briefing/ClientSubmitted', [
                'briefingTitle' => $briefing->title,
            ]);
        }

        if (!in_array($briefing->status, ['draft', 'in_progress'])) {
            abort(Response::HTTP_GONE, 'This briefing is no longer accepting responses.');
        }

        return Inertia::render('Briefing/ClientFill', [
            'token'    => $token,
            'briefing' => [
                'id'      => $briefing->id,
                'title'   => $briefing->title,
                'type'    => $briefing->type,
                'language'=> $briefing->language,
            ],
            'sections' => $briefing->template?->sections ?? [],
            'answers'  => $briefing->answers ?? (object)[],
            'business' => [
                'name' => $briefing->business?->name ?? 'Website Expert',
            ],
        ]);
    }

    /**
     * Autosave answers (called via PATCH periodically).
     * Returns JSON — not Inertia.
     */
    public function autosave(Request $request, string $token)
    {
        $briefing = Briefing::where('client_token', $token)
            ->whereIn('status', ['draft', 'in_progress'])
            ->whereNull('client_submitted_at')
            ->firstOrFail();

        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $this->service->saveAnswers($briefing, $validated['answers']);

        return response()->json(['saved_at' => now()->toISOString()]);
    }

    /**
     * Final submit from the client.
     */
    public function submit(Request $request, string $token)
    {
        $briefing = Briefing::where('client_token', $token)
            ->whereIn('status', ['draft', 'in_progress'])
            ->whereNull('client_submitted_at')
            ->firstOrFail();

        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $this->service->submitByClient($briefing, $validated['answers']);

        return Inertia::render('Briefing/ClientSubmitted', [
            'briefingTitle' => $briefing->title,
        ]);
    }
}
