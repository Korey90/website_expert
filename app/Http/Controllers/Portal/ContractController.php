<?php

namespace App\Http\Controllers\Portal;

use App\Models\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends BasePortalController
{
    public function index(): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            return redirect()->route('portal.dashboard');
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

    public function show(Contract $contract): Response|RedirectResponse
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

    public function sign(Request $request, Contract $contract): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $contract->client_id !== $client->id) {
            abort(403);
        }

        if ($contract->status !== 'sent') {
            return redirect()->route('portal.contracts.show', $contract)
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

        return redirect()->route('portal.contracts.show', $contract)
            ->with('success', 'Contract signed successfully. Thank you!');
    }
}
