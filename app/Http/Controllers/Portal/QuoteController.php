<?php

namespace App\Http\Controllers\Portal;

use App\Mail\ContractSentMail;
use App\Mail\QuoteAcceptedAdminMail;
use App\Models\Contract;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\QuoteAcceptedNotification;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
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

        $quote->loadMissing(['lead.assignedTo', 'client']);

        // Auto-create contract linked to accepted quote
        $assignedUserId = $quote->lead?->assignedTo?->id ?? $quote->created_by;
        $clientName     = $quote->client?->company_name ?? $quote->client?->primary_contact_name ?? 'Client';

        $contract = Contract::create([
            'number'     => Contract::nextNumber(),
            'title'      => "Contract — {$clientName}",
            'client_id'  => $quote->client_id,
            'quote_id'   => $quote->id,
            'project_id' => $quote->lead?->project?->id,
            'created_by' => $assignedUserId,
            'status'     => 'sent',
            'sent_at'    => now(),
            'value'      => $quote->total,
            'currency'   => $quote->currency,
            'terms'      => $quote->terms,
        ]);

        // Email to client
        $clientEmail = $quote->client?->primary_contact_email;
        if ($clientEmail) {
            Mail::to($clientEmail)->send(new ContractSentMail($contract));
        }

        // Notify assigned user + all admins/managers
        $admins        = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager', 'super_admin']))->get();
        $assignedUser  = $quote->lead?->assignedTo;
        $recipients    = $admins;

        if ($assignedUser && ! $admins->contains('id', $assignedUser->id)) {
            $recipients = $recipients->push($assignedUser);
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new QuoteAcceptedAdminMail($quote, $recipient));
            $recipient->notify(new QuoteAcceptedNotification($quote));
            DatabaseNotificationsSent::dispatch($recipient);
        }

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
