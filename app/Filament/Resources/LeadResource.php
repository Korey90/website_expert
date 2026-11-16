<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\BriefingTemplate;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\SalesOfferTemplate;
use App\Models\User;
use App\Scopes\BusinessScope;
use App\Services\BriefingService;
use App\Services\SalesOfferService;
use App\Support\PermissionHelper;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends BaseResource
{
    protected static ?string $model = Lead::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-funnel';

    protected static \UnitEnum|string|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 2;

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'client.company_name'];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Lead Details')
                ->columns(2)
                ->columnSpanFull()
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
                        ->options(fn (): array => User::query()
                            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'manager']))
                            ->whereHas('businesses', fn ($query) => $query
                                ->whereKey(currentBusiness()?->getKey())
                                ->where('business_users.is_active', true))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable(),
                    Forms\Components\Select::make('source')
                        ->options(['calculator' => 'Calculator', 'contact_form' => 'Contact Form', 'referral' => 'Referral', 'cold_outreach' => 'Cold Outreach', 'social_media' => 'Social Media', 'other' => 'Other'])
                        ->default('contact_form'),
                    Forms\Components\TextInput::make('value')->numeric()->prefix(fn () => FilamentCurrency::symbol()),
                    Forms\Components\Select::make('currency')->options(fn () => FilamentCurrency::options())->default(fn () => FilamentCurrency::default()),
                    Forms\Components\DatePicker::make('expected_close_date'),
                ]),

            Section::make('Calculator Data')
                ->collapsed()
                ->columnSpanFull()
                ->schema([
                    Forms\Components\KeyValue::make('calculator_data')
                        ->label('Calculator Configuration')
                        ->columnSpanFull(),
                ]),

            Section::make(__('admin.migration.form.sections.notes'))
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->hiddenLabel()
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('admin.pilot.leads.columns.lead'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('business.name')
                    ->label(__('admin.pilot.common.business'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->visibleFrom('xl')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label(__('admin.pilot.common.client'))
                    ->searchable()
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('stage.name')
                    ->label(__('admin.pilot.leads.columns.stage'))
                    ->badge(),
                Tables\Columns\TextColumn::make('value')
                    ->label(__('admin.pilot.leads.columns.value'))
                    ->money(fn (Lead $record) => FilamentCurrency::tableCurrency($record))
                    ->sortable()
                    ->visibleFrom('sm'),
                Tables\Columns\TextColumn::make('source')
                    ->label(__('admin.pilot.leads.columns.source'))
                    ->badge()
                    ->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('leadSource.type')
                    ->label('Source Type')
                    ->badge()
                    ->color(fn (string $state = ''): string => match ($state) {
                        'landing_page' => 'success',
                        'contact_form' => 'primary',
                        'calculator' => 'warning',
                        'api' => 'purple',
                        default => 'gray',
                    })
                    ->placeholder('—')
                    ->visibleFrom('lg')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('leadSource.landingPage.title')
                    ->label('Landing Page')
                    ->placeholder('—')
                    ->searchable()
                    ->visibleFrom('lg')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label(__('admin.pilot.common.assigned'))
                    ->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('expected_close_date')
                    ->label(__('admin.pilot.leads.columns.expected_close'))
                    ->date()
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.pilot.common.added'))
                    ->date()
                    ->sortable()
                    ->visibleFrom('md')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload(),
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
                                        $t->id => "[{$t->type}] {$t->title}".($t->service_slug ? " ({$t->service_slug})" : ''),
                                    ])
                                    ->toArray()
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Lead $record, array $data) {
                        $template = BriefingTemplate::forBusiness()->findOrFail($data['briefing_template_id']);
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
                Action::make('send_sales_offer')
                    ->label('Wyślij ofertę')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->modalHeading('Wyślij ofertę sprzedażową')
                    ->form([
                        Forms\Components\Select::make('template_id')
                            ->label('Szablon oferty')
                            ->options(
                                fn (Lead $record) => SalesOfferTemplate::forBusiness()
                                    ->active()
                                    ->when($record->service_slug ?? null, fn ($q, $slug) => $q->where(
                                        fn ($q) => $q->where('service_slug', $slug)->orWhereNull('service_slug')
                                    ))
                                    ->orderBy('language')
                                    ->orderBy('title')
                                    ->get()
                                    ->mapWithKeys(fn (SalesOfferTemplate $t) => [
                                        $t->id => "[{$t->language}] {$t->title}".($t->service_slug ? " ({$t->service_slug})" : ''),
                                    ])
                                    ->toArray()
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Lead $record, array $data) {
                        $template = SalesOfferTemplate::forBusiness()->findOrFail($data['template_id']);
                        $service = app(SalesOfferService::class);

                        $offer = $service->createFromTemplate($record, $template, auth()->user());

                        try {
                            $service->send($offer);
                            $email = $record->client?->primary_contact_email ?? $record->email ?? 'klienta';
                            Notification::make()
                                ->title("Oferta wysłana na: {$email}")
                                ->success()
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()
                                ->title('Oferta zapisana jako draft — '.$e->getMessage())
                                ->warning()
                                ->send();
                        }

                        return redirect()->route(
                            'filament.admin.resources.sales-offers.view',
                            $offer->id
                        );
                    }),
                Action::make('save_offer_draft')
                    ->label('Zapisz ofertę (draft)')
                    ->icon('heroicon-o-document-plus')
                    ->color('gray')
                    ->modalHeading('Utwórz draft oferty sprzedażowej')
                    ->form([
                        Forms\Components\Select::make('template_id')
                            ->label('Szablon oferty')
                            ->options(
                                fn (Lead $record) => SalesOfferTemplate::forBusiness()
                                    ->active()
                                    ->when($record->service_slug ?? null, fn ($q, $slug) => $q->where(
                                        fn ($q) => $q->where('service_slug', $slug)->orWhereNull('service_slug')
                                    ))
                                    ->orderBy('language')
                                    ->orderBy('title')
                                    ->get()
                                    ->mapWithKeys(fn (SalesOfferTemplate $t) => [
                                        $t->id => "[{$t->language}] {$t->title}".($t->service_slug ? " ({$t->service_slug})" : ''),
                                    ])
                                    ->toArray()
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Lead $record, array $data) {
                        $template = SalesOfferTemplate::forBusiness()->findOrFail($data['template_id']);
                        $offer = app(SalesOfferService::class)
                            ->createFromTemplate($record, $template, auth()->user());

                        Notification::make()
                            ->title('Draft oferty zapisany.')
                            ->success()
                            ->send();

                        return redirect()->route(
                            'filament.admin.resources.sales-offers.view',
                            $offer->id
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
            ->recordUrl(fn (Lead $record): string => static::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('admin.pilot.leads.empty.heading'))
            ->emptyStateDescription(__('admin.pilot.leads.empty.description'))
            ->emptyStateIcon('heroicon-o-funnel')
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (PermissionHelper::allows($user, 'view_businesses')) {
            return parent::getEloquentQuery()
                ->withoutGlobalScope(BusinessScope::class)
                ->withTrashed();
        }

        return parent::getEloquentQuery()->withTrashed();
    }
}
