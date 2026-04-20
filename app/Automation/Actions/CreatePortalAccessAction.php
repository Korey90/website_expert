<?php

namespace App\Automation\Actions;

use App\Services\Account\PortalAccessService;
use DomainException;
use Illuminate\Support\Facades\Log;

class CreatePortalAccessAction extends BaseAutomationAction
{
    public function __construct(
        private readonly PortalAccessService $portalAccessService,
    ) {}

    public function execute(array $action, array $context, string $triggerEvent): void
    {
        $client = $this->resolveClient($context);

        if (! $client) {
            Log::warning('CreatePortalAccessAction: no client found.', ['context' => $context]);
            return;
        }

        try {
            $result = $this->portalAccessService->ensurePortalAccess($client, [
                'grant_workspace_access' => (bool) ($action['grant_workspace_access'] ?? false),
                'send_invite' => true,
                'queue_invite' => true,
            ]);
        } catch (DomainException $e) {
            Log::warning("CreatePortalAccessAction: {$e->getMessage()}", [
                'client_id' => $client->id,
                'context' => $context,
                'action' => $action,
            ]);
            return;
        }

        Log::info('CreatePortalAccessAction: portal access ensured.', [
            'client_id' => $client->id,
            'user_id' => $result['user']->id,
            'user_was_created' => $result['user_was_created'],
            'workspace_membership_created' => $result['workspace_membership_created'],
        ]);
    }
}
