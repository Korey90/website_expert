<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Project;
use App\Models\Quote;
use App\Services\ContractInterpolationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-check';
    protected static \UnitEnum|string|null $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Load from Template')
                ->schema([
                    Forms\Components\Select::make('_template_id')
                        ->label('Contract Template')
                        ->placeholder('— select a template to pre-fill the Terms field —')
                        ->options(
                            ContractTemplate::where('is_active', true)
                                ->orderByRaw("FIELD(language,'en','pl','pt')")
                                ->orderBy('type')
                                ->get()
                                ->mapWithKeys(fn ($t) => [$t->id => "[{$t->language}] {$t->name}"])
                        )
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, \Filament\Schemas\Components\Utilities\Get $get) {
                            if (!$state) return;
                            $template = ContractTemplate::find($state);
                            if (!$template) return;
                            $client  = Client::find($get('client_id'));
                            $project = Project::find($get('project_id'));
                            $content = app(ContractInterpolationService::class)
                                ->interpolate($template->content, $client, $project);
                            $set('terms', $content);
                            $set('contract_template_id', $state);
                            if (!$get('title')) {
                                $set('title', $template->name);
                            }
                        })
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),

            Section::make('Contract Details')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->label('Contract No.')
                        ->default(fn () => Contract::nextNumber())
                        ->required(),
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->options(Client::withTrashed()->orderBy('company_name')->pluck('company_name', 'id'))
                        ->searchable()
                        ->required()
                        ->default(fn () => request('client_id'))
                        ->live(),
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) =>
                            Project::withTrashed()->when($get('client_id'), fn ($q, $id) => $q->where('client_id', $id))
                                ->orderBy('title')
                                ->pluck('title', 'id')
                        )
                        ->searchable()
                        ->nullable()
                        ->default(fn () => request('project_id'))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) return;
                            $project = Project::withTrashed()->find($state);
                            if (!$project) return;
                            $set('value', $project->budget);
                            $set('currency', $project->currency);
                        }),
                    Forms\Components\Select::make('quote_id')
                        ->label('Quote')
                        ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) =>
                            Quote::withTrashed()->when($get('client_id'), fn ($q, $id) => $q->where('client_id', $id))
                                ->orderBy('number')
                                ->pluck('number', 'id')
                        )
                        ->searchable()
                        ->nullable()
                        ->default(fn () => request('quote_id')),
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft'     => 'Draft',
                            'sent'      => 'Sent',
                            'signed'    => 'Signed',
                            'expired'   => 'Expired',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('draft')
                        ->required(),
                    Forms\Components\Select::make('currency')
                        ->options(['GBP' => '£ GBP', 'EUR' => '€ EUR', 'USD' => '$ USD', 'PLN' => 'zł PLN'])
                        ->default(fn () => request('currency', 'GBP'))
                        ->required(),
                    Forms\Components\TextInput::make('value')
                        ->label('Contract Value')
                        ->numeric()
                        ->prefix('£')
                        ->default(fn () => request('value', 0)),
                ]),

            Section::make('Dates')
                ->columns(4)
                ->schema([
                    Forms\Components\DatePicker::make('starts_at')->label('Start date'),
                    Forms\Components\DatePicker::make('expires_at')->label('Expiry date'),
                    Forms\Components\DatePicker::make('sent_at')->label('Sent on'),
                    Forms\Components\DatePicker::make('signed_at')->label('Signed on'),
                ]),

            Section::make('Terms & Conditions')
                ->schema([
                    Forms\Components\RichEditor::make('terms')
                        ->label('')
                        ->columnSpanFull()
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('contracts/attachments'),
                ]),

            Section::make('Attachments & Notes')
                ->columns(2)
                ->schema([
                    Forms\Components\FileUpload::make('file_path')
                        ->label('Signed Contract PDF')
                        ->disk('public')
                        ->directory('contracts')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240),
                    Forms\Components\Textarea::make('notes')
                        ->label('Internal notes')
                        ->rows(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft'     => 'gray',
                        'sent'      => 'info',
                        'signed'    => 'success',
                        'expired'   => 'warning',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->money(fn ($record) => strtolower($record->currency))
                    ->sortable(),
                Tables\Columns\TextColumn::make('signed_at')
                    ->label('Signed')
                    ->date()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn ($record) => $record->expires_at && $record->expires_at->isPast() && $record->status !== 'signed' ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'sent'      => 'Sent',
                        'signed'    => 'Signed',
                        'expired'   => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->options(Client::withTrashed()->orderBy('company_name')->pluck('company_name', 'id'))
                    ->searchable(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('markSigned')
                    ->label('Mark Signed')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Contract $record) => $record->status === 'sent')
                    ->requiresConfirmation()
                    ->action(function (Contract $record) {
                        $record->update(['status' => 'signed', 'signed_at' => now()]);
                        Notification::make()->success()->title('Contract marked as signed')->send();
                    }),
                Action::make('markSent')
                    ->label('Mark Sent')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Contract $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function (Contract $record) {
                        $record->update(['status' => 'sent', 'sent_at' => now()]);
                        Notification::make()->success()->title('Contract marked as sent')->send();
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'view'   => Pages\ViewContract::route('/{record}'),
            'edit'   => Pages\EditContract::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}
