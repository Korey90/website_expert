<?php

namespace App\Http\Controllers\Portal;

use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends BasePortalController
{
    public function index(): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            return redirect()->route('portal.dashboard');
        }

        $quotes = Quote::where('client_id', $client->id)
            ->latest()
            ->get(['id', 'number', 'status', 'total', 'valid_until', 'currency', 'sent_at', 'accepted_at']);

        return Inertia::render('Portal/Quotes', [
            'client' => $client->only('id', 'company_name'),
            'quotes' => $quotes,
        ]);
    }

    public function show(Quote $quote): Response|RedirectResponse
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

    public function accept(Quote $quote): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $quote->client_id !== $client->id) {
            abort(403);
        }

        if ($quote->status !== 'sent') {
            return redirect()->route('portal.quotes.show', $quote)
                ->with('error', 'This quote can no longer be accepted.');
        }

        $quote->update([
            'status'      => 'accepted',
            'accepted_at' => now(),
        ]);

        return redirect()->route('portal.quotes.show', $quote)
            ->with('success', 'Quote accepted! We will be in touch shortly.');
    }

    public function reject(Quote $quote): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $quote->client_id !== $client->id) {
            abort(403);
        }

        if ($quote->status !== 'sent') {
            return redirect()->route('portal.quotes.show', $quote)
                ->with('error', 'This quote can no longer be rejected.');
        }

        $quote->update([
            'status'      => 'rejected',
            'rejected_at' => now(),
        ]);

        return redirect()->route('portal.quotes.show', $quote)
            ->with('success', 'Quote rejected.');
    }
}
