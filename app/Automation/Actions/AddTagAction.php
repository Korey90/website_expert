<?php

namespace App\Automation\Actions;

use App\Models\Lead;

class AddTagAction extends BaseAutomationAction
{
    public function execute(array $action, array $context, string $triggerEvent): void
    {
        $tag = $action['tag'] ?? null;
        if (! $tag) {
            return;
        }

        if (isset($context['lead_id'])) {
            $lead = Lead::find($context['lead_id']);
            if ($lead) {
                $tags   = $lead->tags ?? [];
                $tags[] = $tag;
                $lead->update(['tags' => array_unique($tags)]);
            }
        }
    }
}
