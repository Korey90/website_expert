<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\PipelineStage;
use Filament\Pages\Page;

class PipelinePage extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-funnel';
    protected static \UnitEnum|string|null $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Sales Pipeline';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.pipeline';

    public function getTitle(): string
    {
        return 'Sales Pipeline';
    }

    public function getViewData(): array
    {
        $stages = PipelineStage::orderBy('order')->get();

        $leads = Lead::withoutTrashed()
            ->with(['client', 'stage', 'assignedTo'])
            ->whereNotIn('pipeline_stage_id', function ($q) {
                $q->select('id')
                  ->from('pipeline_stages')
                  ->where('name', 'Lost');
            })
            ->get()
            ->groupBy('pipeline_stage_id');

        $totals = Lead::withoutTrashed()
            ->selectRaw('pipeline_stage_id, COUNT(*) as count, SUM(value) as total_value')
            ->groupBy('pipeline_stage_id')
            ->pluck(null, 'pipeline_stage_id')
            ->map(fn ($r) => ['count' => $r->count, 'total' => $r->total_value]);

        return [
            'stages' => $stages,
            'leads'  => $leads,
            'totals' => $totals,
        ];
    }
}
