<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles inbound webhook notifications from domain registrar providers.
 *
 * Route: POST /webhooks/domain/{provider}
 *
 * Each provider sends async status updates (registration ACK, transfer
 * completion, renewal confirmation, etc.).  We log the raw payload, then
 * apply domain status changes where possible.
 *
 * Adding a new provider: implement a private handle{Provider}() method.
 */
class DomainProviderWebhookController extends Controller
{
    /** Allowed provider identifiers in the URL segment. */
    private const ALLOWED_PROVIDERS = ['openprovider', 'manual'];

    public function __invoke(Request $request, string $provider): JsonResponse
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return response()->json(['error' => 'Unknown provider.'], 404);
        }

        Log::channel('stack')->info("Domain provider webhook received [{$provider}]", [
            'payload' => $request->all(),
            'ip'      => $request->ip(),
        ]);

        return match ($provider) {
            'openprovider' => $this->handleOpenprovider($request),
            default        => response()->json(['received' => true]),
        };
    }

    // ── Provider-specific handlers ────────────────────────────────────────────

    /**
     * Openprovider sends a POST with JSON payload when a domain order changes status.
     *
     * Relevant status codes:
     *   ACT → active (registered / renewed successfully)
     *   REQ → pending / in progress
     *   FAI → failed
     *   DEL → deleted / expired
     *
     * @see https://docs.openprovider.com/docs/registrant/domain/domain-notifications
     */
    private function handleOpenprovider(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        // Openprovider wraps event data in different keys depending on the event type.
        // We look for the domain ID in common locations.
        $providerId = (string) (
            $payload['domain_id']
            ?? $payload['id']
            ?? ($payload['object']['id'] ?? null)
            ?? ''
        );

        $status = strtoupper(
            $payload['status']
            ?? ($payload['object']['status'] ?? '')
        );

        if (! $providerId || ! $status) {
            // Unrecognised payload structure — acknowledge and move on
            return response()->json(['received' => true]);
        }

        $domain = Domain::where('provider_domain_id', $providerId)->first();

        if (! $domain) {
            Log::warning("Domain webhook [{$providerId}]: no matching domain record found.");
            return response()->json(['received' => true]);
        }

        $newStatus = match ($status) {
            'ACT'  => 'active',
            'REQ'  => 'registering',
            'FAI'  => 'failed',
            'DEL'  => 'expired',
            default => null,
        };

        if ($newStatus && $domain->status !== $newStatus) {
            $domain->update(['status' => $newStatus]);

            Log::info("Domain [{$domain->id}] status updated to [{$newStatus}] via Openprovider webhook.");
        }

        return response()->json(['received' => true]);
    }
}
