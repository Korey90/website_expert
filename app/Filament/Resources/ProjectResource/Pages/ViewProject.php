<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Actions\Finance\GenerateDocumentNumberAction;
use App\Filament\Resources\ContractResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\ProjectResource;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ProjectPhase;
use App\Models\ProjectTask;
use App\Models\ProjectTemplate;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.pilot.projects.view_subheading');
    }

    public function updateProjectStatus(string $status): void
    {
        abort_unless(ProjectResource::canEdit($this->record), 403);

        $allowed = ['active', 'on_hold', 'completed', 'cancelled'];
        if (! in_array($status, $allowed, true)) {
            return;
        }

        $updates = ['status' => $status];
        if ($status === 'completed') {
            $updates['completed_at'] = now();
        }

        $this->record->update($updates);
        $this->record->refresh();

        $messages = [
            'active' => ['title' => __('admin.pilot.projects.notifications.active'), 'color' => 'success'],
            'on_hold' => ['title' => __('admin.pilot.projects.notifications.on_hold'), 'color' => 'warning'],
            'completed' => ['title' => __('admin.pilot.projects.notifications.completed'), 'color' => 'success'],
            'cancelled' => ['title' => __('admin.pilot.projects.notifications.cancelled'), 'color' => 'danger'],
        ];

        $msg = $messages[$status];
        Notification::make()->title($msg['title'])->{$msg['color']}()->send();
    }

    public function updatePhaseStatus(int $phaseId, string $status): void
    {
        abort_unless(ProjectResource::canEdit($this->record), 403);

        $allowed = ['pending', 'in_progress', 'completed', 'cancelled'];
        if (! in_array($status, $allowed, true)) {
            return;
        }

        ProjectPhase::where('id', $phaseId)
            ->where('project_id', $this->record->id)
            ->update(['status' => $status]);

        $this->record->refresh();

        Notification::make()
            ->title(__('admin.pilot.projects.notifications.phase_updated'))
            ->success()
            ->send();
    }

    public function getHeading(): string|Htmlable
    {
        $title = e($this->record->title ?? '—');

        $serviceLabels = [
            'wizytowka' => __('admin.pilot.projects.services.business_card'),
            'landing' => __('admin.pilot.projects.services.landing_page'),
            'ecommerce' => __('admin.pilot.projects.services.ecommerce'),
            'aplikacja' => __('admin.pilot.projects.services.web_application'),
            'seo' => __('admin.pilot.projects.services.seo'),
        ];

        $type = $this->record->service_type;
        $label = $serviceLabels[$type] ?? ($type ? ucfirst($type) : null);
        $badgeModifier = in_array($type, array_keys($serviceLabels), true) ? $type : 'default';

        $badge = $label
            ? '<span class="admin-project-service-badge admin-project-service-badge--'.e($badgeModifier).'">'.e($label).'</span>'
            : '';

        return new HtmlString(
            '<span class="admin-record-heading"><span>'.$title.'</span>'.$badge.'</span>'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('newInvoice')
                ->label('Create Invoice')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->modalHeading('New Invoice')
                ->modalWidth('4xl')
                ->modalSubmitActionLabel('Create Invoice')
                ->form(function () {
                    $project = $this->record;
                    $currency = $project->currency ?? FilamentCurrency::default();
                    $prefix = FilamentCurrency::symbol($currency);

                    return [
                        Section::make('Invoice Details')->columns(3)->schema([
                            TextInput::make('number')
                                ->label('Invoice No.')
                                ->placeholder('Assigned when the invoice is saved')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(1),
                            Select::make('status')->options(['draft' => 'Draft', 'sent' => 'Sent'])->default('draft')->required()->columnSpan(1),
                            Select::make('currency')->options(fn () => FilamentCurrency::options())->default($currency)->required()->columnSpan(1),
                            DatePicker::make('issue_date')->label('Issue Date')->default(today())->required()->columnSpan(1),
                            DatePicker::make('due_date')->label('Due Date')->default(today()->addDays(30))->required()->columnSpan(1),
                            TextInput::make('vat_rate')->label('VAT %')->numeric()->default(20)->suffix('%')->columnSpan(1),
                        ]),
                        Section::make('Line Items')->schema([
                            Repeater::make('items')
                                ->label('')
                                ->schema([
                                    Select::make('description')->required()->searchable()
                                        ->options(function () {
                                            $static = [
                                                'Website design' => 'Website design', 'Website development' => 'Website development',
                                                'E-commerce development' => 'E-commerce development', 'Landing page design' => 'Landing page design',
                                                'UI/UX design' => 'UI/UX design', 'Logo design' => 'Logo design',
                                                'Branding & identity' => 'Branding & identity', 'SEO optimisation' => 'SEO optimisation',
                                                'Content creation' => 'Content creation', 'Maintenance & support' => 'Maintenance & support',
                                                'Domain registration' => 'Domain registration', 'Hosting setup' => 'Hosting setup',
                                                'WordPress development' => 'WordPress development', 'Custom plugin development' => 'Custom plugin development',
                                                'API integration' => 'API integration', 'Mobile app development' => 'Mobile app development',
                                                'Graphic design' => 'Graphic design', 'Email campaign design' => 'Email campaign design',
                                                'Consultation' => 'Consultation',
                                            ];
                                            $fromDb = InvoiceItem::whereNotNull('description')->distinct()->orderBy('description')->pluck('description', 'description')->toArray();

                                            return array_merge($static, $fromDb);
                                        })->columnSpan(4),
                                    TextInput::make('quantity')->numeric()->default(1)->minValue(0)->columnSpan(1),
                                    TextInput::make('unit_price')->label('Unit Price')->numeric()->default($project->budget ?? 0)->prefix($prefix)->minValue(0)->columnSpan(2),
                                ])
                                ->columns(7)->defaultItems(1)->addActionLabel('Add line item')
                                ->itemLabel(fn (array $state): ?string => $state['description']
                                        ? $state['description'].' × '.($state['quantity'] ?? 1).' — '.FilamentCurrency::format((float) ($state['unit_price'] ?? 0) * (float) ($state['quantity'] ?? 1), $currency)
                                        : null
                                )->collapsible(),
                        ]),
                        Textarea::make('notes')->rows(2)->placeholder('Optional notes for the client...')->columnSpanFull(),
                        Textarea::make('terms')->rows(2)->default('Payment due within 30 days of invoice date.')->columnSpanFull(),
                    ];
                })
                ->action(function (array $data) {
                    $items = $data['items'] ?? [];
                    unset($data['items']);

                    $invoice = Invoice::create([
                        ...$data,
                        'business_id' => $this->record->business_id,
                        'number' => app(GenerateDocumentNumberAction::class)
                            ->execute($this->record->business_id, 'invoice'),
                        'client_id' => $this->record->client_id,
                        'project_id' => $this->record->id,
                        'created_by' => auth()->id(),
                        'subtotal' => 0, 'vat_amount' => 0, 'total' => 0, 'amount_paid' => 0, 'amount_due' => 0,
                    ]);

                    foreach ($items as $i => $item) {
                        InvoiceItem::create(['business_id' => $invoice->business_id, 'invoice_id' => $invoice->id, 'description' => $item['description'], 'quantity' => $item['quantity'] ?? 1, 'unit_price' => $item['unit_price'] ?? 0, 'order' => $i + 1]);
                    }

                    $invoice->recalculate();
                    Notification::make()->title('Invoice created')->body("Invoice {$invoice->number} created successfully.")->success()->send();
                    $this->redirect(InvoiceResource::getUrl('view', ['record' => $invoice]));
                }),

            Action::make('newContract')
                ->label('Create Contract')
                ->icon('heroicon-o-document-check')
                ->color('gray')
                ->url(fn () => ContractResource::getUrl('create').'?client_id='.$this->record->client_id.'&project_id='.$this->record->id),

            Action::make('applyTemplate')
                ->label('Apply Template')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->visible(fn () => $this->record->status === 'draft' && ProjectTemplate::where('is_active', true)->exists())
                ->form([
                    Select::make('template_id')->label('Project Template')
                        ->options(fn () => ProjectTemplate::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => $this->record->template_id)->required()->searchable(),
                    Toggle::make('clear_existing')->label('Remove existing phases & tasks first')
                        ->helperText('If OFF, phases from the template will be added on top of existing ones.')
                        ->default(fn () => $this->record->phases()->count() > 0),
                ])
                ->modalHeading('Apply Project Template')
                ->modalDescription('Phases and tasks from the selected template will be created in this project.')
                ->modalSubmitActionLabel('Apply')
                ->action(function (array $data) {
                    $template = ProjectTemplate::find($data['template_id']);
                    if (! $template) {
                        Notification::make()->title('Template not found')->danger()->send();

                        return;
                    }

                    if ($data['clear_existing']) {
                        ProjectTask::where('project_id', $this->record->id)->whereIn('phase_id', $this->record->phases()->pluck('id'))->delete();
                        $this->record->phases()->delete();
                    }

                    $phaseCount = $taskCount = 0;
                    foreach ($template->phases as $phase) {
                        $createdPhase = ProjectPhase::create(['project_id' => $this->record->id, 'name' => $phase['name'], 'description' => $phase['description'] ?? null, 'order' => $phase['order'], 'status' => 'pending']);
                        $phaseCount++;
                        foreach ($phase['tasks'] ?? [] as $i => $task) {
                            ProjectTask::create(['project_id' => $this->record->id, 'phase_id' => $createdPhase->id, 'title' => $task['title'], 'description' => $task['description'] ?? null, 'priority' => $task['priority'] ?? 'medium', 'status' => 'todo', 'order' => $i + 1]);
                            $taskCount++;
                        }
                    }

                    $this->record->update(['template_id' => $data['template_id']]);
                    $this->record->refresh();
                    Notification::make()->title('Template applied')->body("{$phaseCount} phases and {$taskCount} tasks created.")->success()->send();
                }),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
