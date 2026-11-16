<?php

namespace App\Filament\Resources\AutomationRuleResource\Pages;

use App\Filament\Resources\AutomationRuleResource;
use App\Jobs\ProcessAutomationJob;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;

class ViewAutomationRule extends ViewRecord
{
    protected static string $resource = AutomationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_trigger')
                ->label('Test Rule')
                ->icon('heroicon-o-play')
                ->color('warning')
                ->modalHeading('Test Automation Rule')
                ->modalDescription('Run this rule with real or mock data. Enable dry-run to skip actual SMS/email sending.')
                ->modalWidth('lg')
                ->form([
                    Radio::make('mode')
                        ->label('Data source')
                        ->options([
                            'real' => 'Real Lead — pick from database',
                            'mock' => 'Mock Data — use example values',
                        ])
                        ->default('mock')
                        ->live()
                        ->inline(),

                    // ── Real lead picker ─────────────────────────────────
                    Select::make('lead_id')
                        ->label('Select Lead')
                        ->options(
                            Lead::with('client')
                                ->latest()
                                ->limit(200)
                                ->get()
                                ->mapWithKeys(fn ($l) => [
                                    $l->id => "#{$l->id} — {$l->title} ({$l->source})",
                                ])
                        )
                        ->searchable()
                        ->visible(fn (Get $get) => $get('mode') === 'real')
                        ->required(fn (Get $get) => $get('mode') === 'real'),

                    // ── Mock data fields ──────────────────────────────────
                    TextInput::make('mock_lead_title')
                        ->label('Lead Title')
                        ->default('Test Lead')
                        ->visible(fn (Get $get) => $get('mode') === 'mock'),
                    TextInput::make('mock_client_name')
                        ->label('Client Name')
                        ->default('Jan Kowalski')
                        ->visible(fn (Get $get) => $get('mode') === 'mock'),
                    TextInput::make('mock_company_name')
                        ->label('Company')
                        ->default('Test Sp. z o.o.')
                        ->visible(fn (Get $get) => $get('mode') === 'mock'),
                    TextInput::make('mock_source')
                        ->label('Lead Source')
                        ->default('service_cta')
                        ->visible(fn (Get $get) => $get('mode') === 'mock'),

                    // ── Dry run ───────────────────────────────────────────
                    Checkbox::make('dry_run')
                        ->label('Dry Run (do not send real SMS/email)')
                        ->default(true)
                        ->helperText('Uncheck only if you want to send a real message during testing.'),
                ])
                ->action(function (array $data): void {
                    /** @var \App\Models\AutomationRule $rule */
                    $rule    = $this->getRecord();
                    $dryRun  = (bool) ($data['dry_run'] ?? true);

                    if ($data['mode'] === 'real' && ! empty($data['lead_id'])) {
                        $lead    = Lead::find($data['lead_id']);
                        $context = [
                            'lead_id'     => $lead?->id,
                            'client_id'   => $lead?->client_id,
                            'business_id' => $lead?->business_id,
                            'source'      => $lead?->source,
                        ];
                    } else {
                        $context = [
                            'lead_id'      => null,
                            'client_id'    => null,
                            'business_id'  => null,
                            'source'       => $data['mock_source'] ?? 'service_cta',
                            '_mock'        => true,
                            'lead_title'   => $data['mock_lead_title'] ?? 'Test Lead',
                            'client_name'  => $data['mock_client_name'] ?? 'Jan Kowalski',
                            'company_name' => $data['mock_company_name'] ?? 'Test Sp. z o.o.',
                        ];
                    }

                    ProcessAutomationJob::dispatchSync(
                        triggerEvent: $rule->trigger_event,
                        context:      $context,
                        singleRuleId: $rule->id,
                        dryRun:       $dryRun,
                        source:       'test',
                    );

                    Notification::make()
                        ->title($dryRun ? 'Test run complete (dry run)' : 'Test run complete — real actions executed!')
                        ->body('Check Automation Logs for details.')
                        ->success()
                        ->send();
                }),

            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
