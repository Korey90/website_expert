<?php

namespace App\Automation\Actions;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;

class ChangeStatusAction extends BaseAutomationAction
{
    public function execute(array $action, array $context, string $triggerEvent): void
    {
        $newStatus = $action['status'] ?? null;
        if (! $newStatus) {
            return;
        }

        foreach (['lead_id' => Lead::class, 'project_id' => Project::class, 'invoice_id' => Invoice::class] as $key => $model) {
            if (isset($context[$key])) {
                $model::find($context[$key])?->update(['status' => $newStatus]);
            }
        }
    }
}
