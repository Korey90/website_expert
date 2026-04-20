<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers\MessagesRelationManager;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectTemplate;
use App\Models\User;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class ProjectResource extends BaseResource
{
    protected static ?string $model = Project::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-briefcase';
    protected static \UnitEnum|string|null $navigationGroup = 'Projects';
    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->schema([

            // ── Stats bar ────────────────────────────────────────────────────
            Section::make()
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('_stats')
                        ->label('')
                        ->html()
                        ->state(function ($record) {
                            $totalTasks  = $record->tasks()->count();
                            $doneTasks   = $record->tasks()->where('status', 'done')->count();
                            $totalPhases = $record->phases()->count();
                            $donePhases  = $record->phases()->where('status', 'completed')->count();
                            $taskPct     = $totalTasks  > 0 ? round($doneTasks  / $totalTasks  * 100) : 0;
                            $phasePct    = $totalPhases > 0 ? round($donePhases / $totalPhases * 100) : 0;

                            $invoiced    = $record->invoices()->whereNotIn('status', ['draft', 'cancelled'])->sum('total');
                            $budget      = (float) ($record->budget ?? 0);
                            $currency    = match ($record->currency ?? 'GBP') { 'GBP' => '£', 'EUR' => '€', 'USD' => '$', 'PLN' => 'zł', default => $record->currency };
                            $budgetPct   = $budget > 0 ? min(round($invoiced / $budget * 100), 100) : 0;

                            $daysLeft  = $record->deadline ? (int) now()->diffInDays($record->deadline, false) : null;
                            $daysLabel = $daysLeft === null ? 'No deadline' : ($daysLeft > 0 ? $daysLeft . ' days left' : ($daysLeft === 0 ? 'Due today' : abs($daysLeft) . ' days overdue'));
                            $deadlineColor = $daysLeft === null ? '#64748b' : ($daysLeft < 0 ? '#f87171' : ($daysLeft <= 7 ? '#fbbf24' : '#4ade80'));

                            $activePhase = $record->phases()->where('status', 'in_progress')->orderBy('order')->first();
                            $phaseLabel  = $activePhase ? $activePhase->name : ($donePhases === $totalPhases && $totalPhases > 0 ? 'All done' : 'Not started');

                            $card = fn (string $label, string $value, string $sub, string $color, int $pct = -1) =>
                                '<div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-top:3px solid ' . $color . ';border-radius:10px;padding:16px 20px;flex:1;min-width:0;">'
                                . '<p style="margin:0 0 4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:' . $color . ';">' . $label . '</p>'
                                . '<p style="margin:0 0 2px;font-size:22px;font-weight:800;color:#f1f5f9;">' . $value . '</p>'
                                . '<p style="margin:0 0 ' . ($pct >= 0 ? '8' : '0') . 'px;font-size:12px;color:#64748b;">' . $sub . '</p>'
                                . ($pct >= 0
                                    ? '<div style="background:rgba(255,255,255,0.08);border-radius:4px;height:5px;"><div style="background:' . $color . ';border-radius:4px;height:5px;width:' . $pct . '%;transition:width .3s;"></div></div>'
                                    : '')
                                . '</div>';

                            return '<div style="display:flex;gap:14px;">'
                                . $card('Task Progress',  $taskPct . '%',                          $doneTasks . ' of ' . $totalTasks . ' tasks done',         '#818cf8', $taskPct)
                                . $card('Phase Progress', $phasePct . '%',                         $donePhases . ' of ' . $totalPhases . ' phases done',       '#38bdf8', $phasePct)
                                . $card('Active Phase',   $phaseLabel,                             $totalPhases . ' phases total',                             '#a78bfa')
                                . $card('Budget Spent',   $currency . number_format($invoiced, 0), 'of ' . $currency . number_format($budget, 0) . ' (' . $budgetPct . '%)', '#34d399', $budgetPct)
                                . $card('Deadline',       $daysLabel,                              $record->deadline?->format('d M Y') ?? '—',                $deadlineColor)
                                . '</div>';
                        }),
                ]),

            // ── Project Phases (left 2/3) ────────────────────────────────────
            Section::make('Project Phases')
                ->columnSpan(2)
                ->schema([
                    ViewEntry::make('_phases')
                        ->label('')
                        ->view('filament.infolists.project-phases'),
                ]),

            // ── Sidebar + Quick Actions (right 1/3) ─────────────────────────
            Group::make([
            Section::make('Project Details')
                ->schema([
                    TextEntry::make('_sidebar')
                        ->label('')
                        ->html()
                        ->state(function ($record) {
                            $currency = match ($record->currency ?? 'GBP') {
                                'GBP' => '£', 'EUR' => '€', 'USD' => '$', 'PLN' => 'zł', default => $record->currency,
                            };
                            $statusColors = [
                                'draft'     => ['bg' => 'rgba(255,255,255,0.05)', 'text' => '#94a3b8',  'border' => 'rgba(255,255,255,0.1)'],
                                'active'    => ['bg' => 'rgba(74,222,128,0.1)',   'text' => '#4ade80',  'border' => 'rgba(74,222,128,0.3)'],
                                'on_hold'   => ['bg' => 'rgba(251,191,36,0.1)',   'text' => '#fbbf24',  'border' => 'rgba(251,191,36,0.3)'],
                                'completed' => ['bg' => 'rgba(129,140,248,0.1)',  'text' => '#818cf8',  'border' => 'rgba(129,140,248,0.3)'],
                                'cancelled' => ['bg' => 'rgba(248,113,113,0.1)',  'text' => '#f87171',  'border' => 'rgba(248,113,113,0.3)'],
                            ];
                            $sc     = $statusColors[$record->status] ?? $statusColors['draft'];
                            $status = ucwords(str_replace('_', ' ', $record->status ?? 'draft'));

                            $row = fn (string $icon, string $label, string $value) =>
                                '<div style="display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.06);">'
                                . '<span style="color:#475569;font-size:16px;margin-top:1px;">' . $icon . '</span>'
                                . '<div style="min-width:0;">'
                                . '<p style="margin:0;font-size:11px;color:#475569;text-transform:uppercase;letter-spacing:.5px;font-weight:500;">' . $label . '</p>'
                                . '<p style="margin:2px 0 0;font-size:14px;font-weight:600;color:#e2e8f0;word-break:break-word;">' . $value . '</p>'
                                . '</div></div>';

                            $invoicePaid  = $record->invoices()->where('status', 'paid')->sum('total');
                            $invoiceTotal = $record->invoices()->whereNotIn('status', ['draft', 'cancelled'])->sum('total');
                            $serviceLabels = ['wizytowka' => 'Business Card', 'landing' => 'Landing Page', 'ecommerce' => 'E-Commerce', 'aplikacja' => 'Web Application', 'seo' => 'SEO'];

                            $html = '<div>'
                                . '<div style="background:' . $sc['bg'] . ';border:1px solid ' . $sc['border'] . ';border-radius:8px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;">'
                                . '<span style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#475569;">Status</span>'
                                . '<span style="color:' . $sc['text'] . ';font-weight:700;font-size:13px;">' . $status . '</span>'
                                . '</div>'

                                . $row('🏢', 'Client', e($record->client?->company_name ?? '—'))
                                . $row('👤', 'Lead Developer', e($record->assignedTo?->name ?? '—'))
                                . $row('⚙️', 'Service Type', $serviceLabels[$record->service_type] ?? ucfirst($record->service_type ?? '—'))
                                . $row('📅', 'Start Date', $record->start_date?->format('d M Y') ?? '—')
                                . $row('⏰', 'Deadline', $record->deadline?->format('d M Y') ?? '—')
                                . ($record->completed_at ? $row('✅', 'Completed', $record->completed_at->format('d M Y')) : '')

                                . '<div style="margin-top:16px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:8px;padding:12px 14px;">'
                                . '<p style="margin:0 0 10px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#475569;">Finances</p>'
                                . '<div style="display:flex;justify-content:space-between;margin-bottom:6px;">'
                                . '<span style="font-size:13px;color:#64748b;">Budget</span>'
                                . '<span style="font-size:13px;font-weight:700;color:#e2e8f0;">' . ($record->budget ? $currency . number_format((float) $record->budget, 0) : '—') . '</span>'
                                . '</div>'
                                . '<div style="display:flex;justify-content:space-between;margin-bottom:6px;">'
                                . '<span style="font-size:13px;color:#64748b;">Invoiced</span>'
                                . '<span style="font-size:13px;font-weight:700;color:#e2e8f0;">' . $currency . number_format($invoiceTotal, 0) . '</span>'
                                . '</div>'
                                . '<div style="display:flex;justify-content:space-between;">'
                                . '<span style="font-size:13px;color:#64748b;">Paid</span>'
                                . '<span style="font-size:13px;font-weight:700;color:#4ade80;">' . $currency . number_format($invoicePaid, 0) . '</span>'
                                . '</div>'
                                . '</div>'
                                . '</div>';

                            return $html;
                        }),
                ]),

            Section::make('Quick Actions')
                ->schema([
                    ViewEntry::make('_quick_actions')
                        ->label('')
                        ->view('filament.infolists.project-quick-actions'),
                ]),

            ])->columnSpan(1),

            // ── Description ──────────────────────────────────────────────────
            Section::make('Description')
                ->columnSpanFull()
                ->collapsed()
                ->schema([
                    TextEntry::make('description')
                        ->label('')
                        ->prose()
                        ->placeholder('No description provided.'),
                ]),

            // ── Invoices ──────────────────────────────────────────────────────
            Section::make('Invoices')
                ->columnSpanFull()
                ->collapsed()
                ->schema([
                    TextEntry::make('_invoices')
                        ->label('')
                        ->html()
                        ->state(function ($record) {
                            $invoices = $record->invoices()->orderBy('created_at', 'desc')->get();
                            if ($invoices->isEmpty()) {
                                return '<p style="color:#475569;font-size:14px;">No invoices linked to this project yet.</p>';
                            }

                            $currency = match ($record->currency ?? 'GBP') {
                                'GBP' => '£', 'EUR' => '€', 'USD' => '$', 'PLN' => 'zł', default => $record->currency,
                            };
                            $statusMeta = [
                                'draft'     => ['bg' => 'rgba(255,255,255,0.06)', 'text' => '#94a3b8'],
                                'sent'      => ['bg' => 'rgba(129,140,248,0.15)', 'text' => '#818cf8'],
                                'paid'      => ['bg' => 'rgba(74,222,128,0.15)',  'text' => '#4ade80'],
                                'overdue'   => ['bg' => 'rgba(248,113,113,0.15)', 'text' => '#f87171'],
                                'cancelled' => ['bg' => 'rgba(255,255,255,0.04)', 'text' => '#475569'],
                            ];

                            $html = '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
                                . '<thead><tr style="border-bottom:1px solid rgba(255,255,255,0.1);">'
                                . '<th style="text-align:left;padding:8px 12px;color:#475569;font-weight:600;">Number</th>'
                                . '<th style="text-align:left;padding:8px 12px;color:#475569;font-weight:600;">Issue Date</th>'
                                . '<th style="text-align:left;padding:8px 12px;color:#475569;font-weight:600;">Due Date</th>'
                                . '<th style="text-align:right;padding:8px 12px;color:#475569;font-weight:600;">Total</th>'
                                . '<th style="text-align:center;padding:8px 12px;color:#475569;font-weight:600;">Status</th>'
                                . '</tr></thead><tbody>';

                            foreach ($invoices as $inv) {
                                $sm = $statusMeta[$inv->status] ?? $statusMeta['draft'];
                                $html .= '<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">'
                                    . '<td style="padding:10px 12px;font-weight:600;color:#e2e8f0;">' . e($inv->number ?? "#{$inv->id}") . '</td>'
                                    . '<td style="padding:10px 12px;color:#64748b;">' . ($inv->issue_date?->format('d M Y') ?? '—') . '</td>'
                                    . '<td style="padding:10px 12px;color:#64748b;">' . ($inv->due_date?->format('d M Y') ?? '—') . '</td>'
                                    . '<td style="padding:10px 12px;text-align:right;font-weight:700;color:#e2e8f0;">' . $currency . number_format((float) $inv->total, 2) . '</td>'
                                    . '<td style="padding:10px 12px;text-align:center;"><span style="background:' . $sm['bg'] . ';color:' . $sm['text'] . ';border-radius:20px;padding:3px 10px;font-size:11px;font-weight:600;text-transform:uppercase;">' . $inv->status . '</span></td>'
                                    . '</tr>';
                            }

                            $html .= '</tbody></table>';
                            return $html;
                        }),
                ]),
        ]);
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Project Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->options(Client::withTrashed()->pluck('company_name', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('assigned_to')
                        ->label('Lead Developer')
                        ->options(User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager', 'developer']))->pluck('name', 'id'))
                        ->searchable(),
                    Forms\Components\Select::make('status')
                        ->options(['draft' => 'Draft', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled'])
                        ->default('draft')->required(),
                    Forms\Components\Select::make('service_type')
                        ->options(['wizytowka' => 'Business Card Site', 'landing' => 'Landing Page', 'ecommerce' => 'E-Commerce', 'aplikacja' => 'Web Application', 'seo' => 'SEO', 'other' => 'Other']),
                    Forms\Components\Select::make('template_id')
                        ->label('Project Template')
                        ->options(ProjectTemplate::where('is_active', true)->pluck('name', 'id'))
                        ->reactive()
                        ->helperText('Selecting a template will auto-create project phases on save.'),
                    Forms\Components\Select::make('currency')->options(['GBP' => '£ GBP', 'EUR' => '€ EUR', 'USD' => '$ USD'])->default('GBP'),
                    Forms\Components\TextInput::make('budget')->numeric()->prefix('£'),
                    Forms\Components\DatePicker::make('start_date'),
                    Forms\Components\DatePicker::make('deadline'),
                ]),

            Forms\Components\Textarea::make('description')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('service_type')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) { 'draft' => 'gray', 'active' => 'success', 'on_hold' => 'warning', 'completed' => 'primary', 'cancelled' => 'danger', default => 'gray' }),
                Tables\Columns\TextColumn::make('budget')->money('GBP')->sortable(),
                Tables\Columns\TextColumn::make('deadline')->date()->sortable(),
                Tables\Columns\TextColumn::make('assignedTo.name')->label('Developer'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled']),
                Tables\Filters\SelectFilter::make('service_type')
                    ->options(['wizytowka' => 'Business Card', 'landing' => 'Landing', 'ecommerce' => 'E-Commerce', 'aplikacja' => 'Application']),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view'   => Pages\ViewProject::route('/{record}'),
            'edit'   => Pages\EditProject::route('/{record}/edit'),
            'tasks'  => Pages\ManageProjectTasks::route('/{record}/tasks'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}

