<?php

namespace App\Filament\Resources\BriefingResource\Pages;

use App\Filament\Resources\BriefingResource;
use App\Models\Briefing;
use App\Services\BriefingService;
use App\Services\CalculatorBriefingMapper;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewBriefing extends ViewRecord
{
    protected static string $resource = BriefingResource::class;
    protected string $view = 'filament.pages.view-briefing';

    /** @var array<string, array<string, mixed>> */
    public array $answers = [];

    /** @var string|null */
    public ?string $notes = null;

    /**
     * Flat list of 'sectionKey.questionKey' strings whose current value
     * matches what the lead's calculator/form data provided.
     * Recomputed live — badge disappears as soon as user edits the value.
     *
     * @var array<string>
     */
    public array $calculatorPrefilled = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var Briefing $briefing */
        $briefing = $this->getRecord();

        $this->answers = $briefing->answers ?? [];
        $this->notes   = $briefing->notes;

        $this->prefillFromLead($briefing);
    }

    public function updatedAnswers(): void
    {
        $this->recomputeCalculatorPrefilled();
        $this->autosave();
    }

    /**
     * Prefill empty answers from lead's calculator_data / form_data,
     * then compute which current values still match the source data.
     */
    private function prefillFromLead(Briefing $briefing): void
    {
        $lead = $briefing->lead;

        if (!$lead) {
            return;
        }

        $sourceData = array_merge(
            $lead->calculator_data ?? [],
            $lead->form_data       ?? [],
        );

        if (empty($sourceData)) {
            return;
        }

        $sections = $briefing->template?->sections ?? [];
        $mapper   = app(CalculatorBriefingMapper::class);
        $result   = $mapper->map($sourceData, $sections);

        // Fill only empty / null answer slots
        foreach ($result['answers'] as $sectionKey => $questions) {
            foreach ($questions as $qKey => $value) {
                $existing = $this->answers[$sectionKey][$qKey] ?? null;
                if ($existing === null || $existing === '') {
                    $this->answers[$sectionKey][$qKey] = $value;
                }
            }
        }

        $this->recomputeCalculatorPrefilled($result['answers']);
    }

    /**
     * Rebuild $calculatorPrefilled: keys whose current answer still matches
     * what the lead's source data provides (so badge disappears on user edit).
     *
     * @param  array<string, array<string, string>>|null $calcAnswers
     */
    private function recomputeCalculatorPrefilled(?array $calcAnswers = null): void
    {
        if ($calcAnswers === null) {
            // Re-derive from lead
            $briefing = $this->getRecord();
            $lead     = $briefing->lead;

            if (!$lead) {
                return;
            }

            $sourceData = array_merge(
                $lead->calculator_data ?? [],
                $lead->form_data       ?? [],
            );

            if (empty($sourceData)) {
                return;
            }

            $sections   = $briefing->template?->sections ?? [];
            $mapper     = app(CalculatorBriefingMapper::class);
            $result     = $mapper->map($sourceData, $sections);
            $calcAnswers = $result['answers'];
        }

        $prefilled = [];

        foreach ($calcAnswers as $sectionKey => $questions) {
            foreach ($questions as $qKey => $calcValue) {
                $current = $this->answers[$sectionKey][$qKey] ?? null;
                // Show badge when current value still matches what calculator gave
                if ((string) $current === (string) $calcValue && $calcValue !== '') {
                    $prefilled[] = "{$sectionKey}.{$qKey}";
                }
            }
        }

        $this->calculatorPrefilled = $prefilled;
    }

    public function autosave(): void
    {
        /** @var Briefing $briefing */
        $briefing = $this->getRecord();

        if (!$briefing->isEditable()) {
            return;
        }

        app(BriefingService::class)->saveAnswers($briefing, $this->answers);

        $this->record = $briefing->fresh();
    }

    public function saveProgress(): void
    {
        /** @var Briefing $briefing */
        $briefing = $this->getRecord();

        if (!$briefing->isEditable()) {
            Notification::make()
                ->title('Cannot save: briefing is not editable.')
                ->warning()
                ->send();
            return;
        }

        app(BriefingService::class)->saveAnswers($briefing, $this->answers);

        if ($this->notes !== null) {
            $briefing->update(['notes' => $this->notes]);
        }

        $this->record = $briefing->fresh();

        Notification::make()
            ->title('Progress saved.')
            ->success()
            ->send();
    }

    public function completeBriefing(): void
    {
        /** @var Briefing $briefing */
        $briefing = $this->getRecord();

        try {
            app(BriefingService::class)->complete($briefing, $this->answers, $this->notes);
            $this->record = $briefing->fresh();

            Notification::make()
                ->title('Briefing completed.')
                ->success()
                ->send();
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Cannot complete: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelBriefing(): void
    {
        /** @var Briefing $briefing */
        $briefing = $this->getRecord();

        app(BriefingService::class)->cancel($briefing);
        $this->record = $briefing->fresh();

        Notification::make()
            ->title('Briefing cancelled.')
            ->warning()
            ->send();
    }

    public function shareWithClient(): void
    {
        /** @var Briefing $briefing */
        $briefing = $this->getRecord();

        $url = app(BriefingService::class)->shareWithClient($briefing);
        $this->record = $briefing->fresh();

        Notification::make()
            ->title('Client link generated.')
            ->body($url)
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        /** @var Briefing $briefing */
        $briefing = $this->getRecord();

        $actions = [];

        if ($briefing->isEditable()) {
            $actions[] = Action::make('save_progress')
                ->label('Save progress')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('gray')
                ->action('saveProgress');

            $actions[] = Action::make('share_with_client')
                ->label('Share with client')
                ->icon('heroicon-o-share')
                ->color('info')
                ->requiresConfirmation()
                ->action('shareWithClient');

            $actions[] = Action::make('complete_briefing')
                ->label('Complete')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Complete briefing?')
                ->modalDescription('This will mark the briefing as completed. Make sure all required fields are filled.')
                ->action('completeBriefing');

            $actions[] = Action::make('cancel_briefing')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action('cancelBriefing');
        }

        $actions[] = Action::make('export_pdf')
            ->label('Export PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function () {
                $briefing = $this->getRecord()->load(['lead', 'conductedBy', 'template']);
                $pdf = Pdf::loadView('pdf.briefing', compact('briefing'));
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'briefing-' . $briefing->id . '.pdf'
                );
            });

        return $actions;
    }

    public function getProgress(): int
    {
        return $this->getRecord()->getProgressPercentage();
    }

    public function getSections(): array
    {
        $template = $this->getRecord()->template;

        return $template ? ($template->sections ?? []) : [];
    }

    public function getAnswer(string $sectionKey, string $questionKey): mixed
    {
        return $this->answers[$sectionKey][$questionKey] ?? null;
    }
}
