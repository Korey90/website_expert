<?php

use App\Models\PipelineStage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PipelineStage::where('name', 'Contacted')->each(function (PipelineStage $stage): void {
            $checklist = $stage->checklist ?? [];
            $changed   = false;

            foreach ($checklist as &$item) {
                if (($item['label'] ?? '') === 'Schedule discovery call' && ($item['condition'] ?? null) === null) {
                    $item['condition'] = 'call_scheduled';
                    $changed = true;
                }
            }
            unset($item);

            if ($changed) {
                $stage->checklist = $checklist;
                $stage->save();
            }
        });
    }

    public function down(): void
    {
        PipelineStage::where('name', 'Contacted')->each(function (PipelineStage $stage): void {
            $checklist = $stage->checklist ?? [];
            $changed   = false;

            foreach ($checklist as &$item) {
                if (($item['label'] ?? '') === 'Schedule discovery call' && ($item['condition'] ?? null) === 'call_scheduled') {
                    $item['condition'] = null;
                    $changed = true;
                }
            }
            unset($item);

            if ($changed) {
                $stage->checklist = $checklist;
                $stage->save();
            }
        });
    }
};
