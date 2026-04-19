<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Briefing;
use App\Models\BriefingTemplate;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\BriefingService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-funnel';
    protected static \UnitEnum|string|null $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Lead Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->options(Client::withTrashed()->pluck('company_name', 'id'))
                        ->searchable()
                        ->default(fn () => request('client_id') ? (int) request('client_id') : null)
                        ->createOptionForm([
                            Forms\Components\TextInput::make('company_name')->required(),
                            Forms\Components\TextInput::make('primary_contact_email')->email(),
                        ]),
                    Forms\Components\Select::make('pipeline_stage_id')
                        ->label('Stage')
                        ->options(PipelineStage::orderBy('order')->pluck('name', 'id'))
                        ->required(),
                    Forms\Components\Select::make('assigned_to')
                        ->label('Assigned To')
                        ->options(User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))->pluck('name', 'id'))
                        ->searchable(),
                    Forms\Components\Select::make('source')
                        ->options(['calculator' => 'Calculator', 'contact_form' => 'Contact Form', 'referral' => 'Referral', 'cold_outreach' => 'Cold Outreach', 'social_media' => 'Social Media', 'other' => 'Other'])
                        ->default('contact_form'),
                    Forms\Components\TextInput::make('value')->numeric()->prefix('£'),
                    Forms\Components\Select::make('currency')->options(['GBP' => '£ GBP', 'EUR' => '€ EUR', 'USD' => '$ USD'])->default('GBP'),
                    Forms\Components\DatePicker::make('expected_close_date'),
                ]),

            Section::make('Calculator Data')
                ->collapsed()
                ->schema([
                    Forms\Components\KeyValue::make('calculator_data')
                        ->label('Calculator Configuration')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Textarea::make('notes')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('business.name')->label('Business')->searchable()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stage.name')->label('Stage')->badge(),
                Tables\Columns\TextColumn::make('value')->money('GBP')->sortable(),
                Tables\Columns\TextColumn::make('source')->badge(),
                Tables\Columns\TextColumn::make('leadSource.type')
                    ->label('Source Type')
                    ->badge()
                    ->color(fn (string $state = ''): string => match ($state) {
                        'landing_page' => 'success',
                        'contact_form' => 'primary',
                        'calculator'   => 'warning',
                        'api'          => 'purple',
                        default        => 'gray',
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('leadSource.landingPage.title')
                    ->label('Landing Page')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assignedTo.name')->label('Assigned'),
                Tables\Columns\TextColumn::make('expected_close_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pipeline_stage_id')
                    ->label('Stage')
                    ->options(PipelineStage::orderBy('order')->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('source')
                    ->options(['calculator' => 'Calculator', 'contact_form' => 'Contact Form', 'referral' => 'Referral', 'landing_page' => 'Landing Page']),
                Tables\Filters\SelectFilter::make('lead_source_type')
                    ->label('Source Type')
                    ->options(array_combine(LeadSource::TYPES, array_map(
                        fn ($t) => ucfirst(str_replace('_', ' ', $t)),
                        LeadSource::TYPES,
                    )))
                    ->query(fn ($query, $data) => $data['value']
                        ? $query->whereHas('leadSource', fn ($q) => $q->where('type', $data['value']))
                        : $query
                    ),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Action::make('conduct_briefing')
                    ->label('Conduct Briefing')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->modalHeading('Start a new briefing')
                    ->form([
                        Forms\Components\Select::make('briefing_template_id')
                            ->label('Select template')
                            ->options(
                                fn (Lead $record) => BriefingTemplate::forBusiness()
                                    ->active()
                                    ->when($record->service_slug ?? null, fn ($q, $slug) => $q->where(
                                        fn ($q) => $q->where('service_slug', $slug)->orWhereNull('service_slug')
                                    ))
                                    ->orderBy('type')
                                    ->orderBy('title')
                                    ->get()
                                    ->mapWithKeys(fn (BriefingTemplate $t) => [
                                        $t->id => "[{$t->type}] {$t->title}" . ($t->service_slug ? " ({$t->service_slug})" : ''),
                                    ])
                                    ->toArray()
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Lead $record, array $data) {
                        $template = BriefingTemplate::findOrFail($data['briefing_template_id']);
                        $briefing = app(BriefingService::class)
                            ->createFromTemplate($record, $template, auth()->user());

                        Notification::make()
                            ->title('Briefing created.')
                            ->success()
                            ->send();

                        return redirect()->route(
                            'filament.admin.resources.briefings.view',
                            $briefing->id
                        );
                    }),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view'   => Pages\ViewLead::route('/{record}'),
            'edit'   => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withTrashed()
            ->when(currentBusiness(), fn ($q, $b) => $q->where('business_id', $b->id));
    }
}

