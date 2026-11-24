<?php

namespace App\Services\AI;

use App\Models\AiAssistant;
use App\Models\Business;
use App\Scopes\BusinessScope;
use Illuminate\Support\Facades\DB;

final class AiAssistantManager
{
    public function forBusiness(Business $business): AiAssistant
    {
        AiAccess::business('view_ai_knowledge', $business->id);

        return DB::transaction(function () use ($business) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();

            $existing = AiAssistant::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $business->id)->first();

            if ($existing) {
                return $existing;
            }

            $assistant = new AiAssistant;
            $assistant->forceFill([
                'business_id' => $business->id,
                'name' => $business->name.' AI',
                'locale' => in_array($business->locale, ['en', 'pl', 'pt'], true) ? $business->locale : 'en',
                'enabled' => false,
            ]);
            $assistant->save();

            return $assistant;
        });
    }

    public function setEnabled(Business $business, bool $enabled): AiAssistant
    {
        AiAccess::business('manage_ai_knowledge', $business->id);

        $assistant = $this->forBusiness($business);
        $assistant->forceFill(['enabled' => $enabled])->save();

        return $assistant;
    }
}
