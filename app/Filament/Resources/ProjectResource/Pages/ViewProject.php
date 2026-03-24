<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\ProjectResource;
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

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    public function updateProjectStatus(string $status): void
    {
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
            'active'    => ['title' => 'Project is now Active',     'color' => 'success'],
            'on_hold'   => ['title' => 'Project put on hold',       'color' => 'warning'],
            'completed' => ['title' => 'Project marked as Completed 🎉', 'color' => 'success'],
            'cancelled' => ['title' => 'Project cancelled',         'color' => 'danger'],
        ];

        $msg = $messages[$status];
        Notification::make()->title($msg['title'])->{$msg['color']}()->send();
    }

    public function updatePhaseStatus(int $phaseId, string $status): void
    {
        $allowed = ['pending', 'in_progress', 'completed', 'cancelled'];
        if (! in_array($status, $allowed, true)) {
            return;
        }

        ProjectPhase::where('id', $phaseId)
            ->where('project_id', $this->record->id)
            ->update(['status' => $status]);

        $this->record->refresh();

        Notification::make()
            ->title('Phase status updated')
            ->success()
            ->send();
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        $title = e($this->record->title ?? '—');

        $serviceLabels = [
            'wizytowka' => 'Business Card',
            'landing'   => 'Landing Page',
            'ecommerce' => 'E-Commerce',
            'aplikacja' => 'Web Application',
            'seo'       => 'SEO',
        ];
        $serviceColors = [
            'wizytowka' => ['bg' => 'rgba(56,189,248,0.15)',  'text' => '#38bdf8', 'border' => 'rgba(56,189,248,0.3)'],
            'landing'   => ['bg' => 'rgba(129,140,248,0.15)', 'text' => '#818cf8', 'border' => 'rgba(129,140,248,0.3)'],
            'ecommerce' => ['bg' => 'rgba(52,211,153,0.15)',  'text' => '#34d399', 'border' => 'rgba(52,211,153,0.3)'],
            'aplikacja' => ['bg' => 'rgba(251,191,36,0.15)',  'text' => '#fbbf24', 'border' => 'rgba(251,191,36,0.3)'],
            'seo'       => ['bg' => 'rgba(167,139,250,0.15)', 'text' => '#a78bfa', 'border' => 'rgba(167,139,250,0.3)'],
        ];

        $type   = $this->record->service_type;
        $label  = $serviceLabels[$type] ?? ($type ? ucfirst($type) : null);
        $colors = $serviceColors[$type] ?? ['bg' => 'rgba(255,255,255,0.08)', 'text' => '#94a3b8', 'border' => 'rgba(255,255,255,0.15)'];

        $badge = $label
            ? '<span style="display:inline-flex;align-items:center;vertical-align:middle;margin-left:10px;padding:3px 11px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:.4px;background:' . $colors['bg'] . ';color:' . $colors['text'] . ';border:1px solid ' . $colors['border'] . ';">' . e($label) . '</span>'
            : '';

        return new \Illuminate\Support\HtmlString(
            '<span style="vertical-align:middle;">' . $title . '</span>' . $badge
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
                    $project  = $this->record;
                    $currency = $project->currency ?? 'GBP';
                    $prefix   = match ($currency) { 'EUR' => '€', 'USD' => '$', 'PLN' => 'zł', default => '£' };
                    $nextNum  = 'INV-' . date('Y') . '-' . str_pad(Invoice::withTrashed()->count() + 1, 3, '0', STR_PAD_LEFT);

                    return [
                        Section::make('Invoice Details')->columns(3)->schema([
                            TextInput::make('number')->label('Invoice No.')->default($nextNum)->required()->columnSpan(1),
                            Select::make('status')->options(['draft' => 'Draft', 'sent' => 'Sent'])->default('draft')->required()->columnSpan(1),
                            Select::make('currency')->options(['GBP' => '£ GBP', 'EUR' => '€ EUR', 'USD' => '$ USD', 'PLN' => 'zł PLN'])->default($currency)->required()->columnSpan(1),
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
                                ->itemLabel(fn (array $state): ?string =>
                                    $state['description']
                                        ? $state['description'] . ' × ' . ($state['quantity'] ?? 1) . ' — ' . $prefix . number_format((float) ($state['unit_price'] ?? 0) * (float) ($state['quantity'] ?? 1), 2)
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
                        'client_id'   => $this->record->client_id,
                        'project_id'  => $this->record->id,
                        'created_by'  => auth()->id(),
                        'subtotal'    => 0, 'vat_amount' => 0, 'total' => 0, 'amount_paid' => 0, 'amount_due' => 0,
                    ]);

                    foreach ($items as $i => $item) {
                        InvoiceItem::create(['invoice_id' => $invoice->id, 'description' => $item['description'], 'quantity' => $item['quantity'] ?? 1, 'unit_price' => $item['unit_price'] ?? 0, 'order' => $i + 1]);
                    }

                    $invoice->recalculate();
                    Notification::make()->title('Invoice created')->body("Invoice {$invoice->number} created successfully.")->success()->send();
                    $this->redirect(InvoiceResource::getUrl('view', ['record' => $invoice]));
                }),

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
                    if (! $template) { Notification::make()->title('Template not found')->danger()->send(); return; }

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
