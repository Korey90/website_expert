<?php

namespace App\Filament\Pages;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\UpdateAccountingProfileAction;
use App\Models\AccountingProfile;
use App\Models\Business;
use App\Models\User;
use App\Support\PermissionHelper;
use DomainException;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AccountingProfilePage extends BasePage
{
    protected string $view = 'filament.pages.accounting-profile';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-identification';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'accounting-profile';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public bool $hasCompanyUtr = false;

    public bool $hasPayeReference = false;

    public bool $hasAccountsOfficeReference = false;

    public static function getNavigationLabel(): string
    {
        return __('finance.accounting.navigation');
    }

    public function getTitle(): string
    {
        return __('finance.accounting.title');
    }

    public function mount(): void
    {
        $business = currentBusiness();
        $actor = auth()->user();
        abort_unless($business instanceof Business && $actor instanceof User, 404);

        $profile = app(InitializeAccountingSetupAction::class)->execute($business, $actor)['profile'];
        $address = $profile->registered_address ?? [];
        $this->hasCompanyUtr = filled($profile->company_utr);
        $this->hasPayeReference = filled($profile->paye_reference);
        $this->hasAccountsOfficeReference = filled($profile->accounts_office_reference);

        $this->form->fill([
            'entity_type' => $profile->entity_type,
            'legal_name' => $profile->legal_name ?: $business->name,
            'company_number' => $profile->company_number,
            'company_utr' => null,
            'registered_address' => [
                'line_1' => $address['line_1'] ?? null,
                'line_2' => $address['line_2'] ?? null,
                'city' => $address['city'] ?? null,
                'county' => $address['county'] ?? null,
                'postcode' => $address['postcode'] ?? null,
                'country' => $address['country'] ?? 'GB',
            ],
            'functional_currency' => $profile->functional_currency ?: 'GBP',
            'accounting_basis' => $profile->accounting_basis ?: 'accrual',
            'financial_year_start' => $profile->financial_year_start?->toDateString(),
            'financial_year_end' => $profile->financial_year_end?->toDateString(),
            'vat_registered' => (bool) $profile->vat_registered,
            'vat_number' => $profile->vat_number,
            'vat_scheme' => $profile->vat_scheme,
            'vat_effective_from' => $profile->vat_effective_from?->toDateString(),
            'vat_effective_to' => $profile->vat_effective_to?->toDateString(),
            'paye_reference' => null,
            'accounts_office_reference' => null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make(__('finance.accounting.sections.entity'))
                    ->description(__('finance.accounting.sections.entity_description'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('entity_type')
                            ->label(__('finance.accounting.fields.entity_type'))
                            ->options([
                                'limited_company' => __('finance.accounting.options.limited_company'),
                                'sole_trader' => __('finance.accounting.options.sole_trader'),
                                'partnership' => __('finance.accounting.options.partnership'),
                            ])
                            ->placeholder(__('finance.accounting.hints.select_entity_type'))
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('legal_name')
                            ->label(__('finance.accounting.fields.legal_name'))
                            ->required()
                            ->minLength(2)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('company_number')
                            ->label(__('finance.accounting.fields.company_number'))
                            ->maxLength(20)
                            ->visible(fn (Get $get): bool => $get('entity_type') === 'limited_company')
                            ->required(fn (Get $get): bool => $get('entity_type') === 'limited_company'),
                        Forms\Components\TextInput::make('company_utr')
                            ->label(__('finance.accounting.fields.company_utr'))
                            ->password()
                            ->autocomplete('new-password')
                            ->maxLength(14)
                            ->rule('regex:/^(?:\s*\d){10}\s*$/')
                            ->helperText($this->secretHint($this->hasCompanyUtr, 'utr')),
                    ]),
                Section::make(__('finance.accounting.sections.address'))
                    ->description(__('finance.accounting.sections.address_description'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('registered_address.line_1')->label(__('finance.accounting.fields.line_1'))->required()->maxLength(255),
                        Forms\Components\TextInput::make('registered_address.line_2')->label(__('finance.accounting.fields.line_2'))->maxLength(255),
                        Forms\Components\TextInput::make('registered_address.city')->label(__('finance.accounting.fields.city'))->required()->maxLength(100),
                        Forms\Components\TextInput::make('registered_address.county')->label(__('finance.accounting.fields.county'))->maxLength(100),
                        Forms\Components\TextInput::make('registered_address.postcode')->label(__('finance.accounting.fields.postcode'))->required()->maxLength(12),
                        Forms\Components\TextInput::make('registered_address.country')->label(__('finance.accounting.fields.country'))->default('GB')->required()->length(2),
                    ]),
                Section::make(__('finance.accounting.sections.accounting'))
                    ->description(__('finance.accounting.sections.accounting_description'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('functional_currency')
                            ->label(__('finance.accounting.fields.functional_currency'))
                            ->default('GBP')
                            ->readOnly()
                            ->required()
                            ->helperText(__('finance.accounting.hints.gbp_only')),
                        Forms\Components\Select::make('accounting_basis')
                            ->label(__('finance.accounting.fields.accounting_basis'))
                            ->options([
                                'accrual' => __('finance.accounting.options.accrual'),
                                'cash' => __('finance.accounting.options.cash'),
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('financial_year_start')->label(__('finance.accounting.fields.financial_year_start'))->required(),
                        Forms\Components\DatePicker::make('financial_year_end')->label(__('finance.accounting.fields.financial_year_end'))->after('financial_year_start')->required(),
                    ]),
                Section::make(__('finance.accounting.sections.vat'))
                    ->description(__('finance.accounting.sections.vat_description'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('vat_registered')
                            ->label(__('finance.accounting.fields.vat_registered'))
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('vat_number')
                            ->label(__('finance.accounting.fields.vat_number'))
                            ->maxLength(15)
                            ->rule('regex:/^(?:GB)?(?:\d{9}(?:\d{3})?|GD\d{3}|HA\d{3})$/i')
                            ->visible(fn (Get $get): bool => (bool) $get('vat_registered'))
                            ->required(fn (Get $get): bool => (bool) $get('vat_registered')),
                        Forms\Components\Select::make('vat_scheme')
                            ->label(__('finance.accounting.fields.vat_scheme'))
                            ->options([
                                'standard' => __('finance.accounting.options.standard'),
                                'cash' => __('finance.accounting.options.cash_vat'),
                                'annual' => __('finance.accounting.options.annual'),
                                'flat_rate' => __('finance.accounting.options.flat_rate'),
                            ])
                            ->visible(fn (Get $get): bool => (bool) $get('vat_registered'))
                            ->required(fn (Get $get): bool => (bool) $get('vat_registered')),
                        Forms\Components\DatePicker::make('vat_effective_from')
                            ->label(__('finance.accounting.fields.vat_effective_from'))
                            ->visible(fn (Get $get): bool => (bool) $get('vat_registered'))
                            ->required(fn (Get $get): bool => (bool) $get('vat_registered')),
                        Forms\Components\DatePicker::make('vat_effective_to')
                            ->label(__('finance.accounting.fields.vat_effective_to'))
                            ->afterOrEqual('vat_effective_from')
                            ->visible(fn (Get $get): bool => (bool) $get('vat_registered')),
                    ]),
                Section::make(__('finance.accounting.sections.payroll'))
                    ->description(__('finance.accounting.sections.payroll_description'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('paye_reference')
                            ->label(__('finance.accounting.fields.paye_reference'))
                            ->password()
                            ->autocomplete('new-password')
                            ->maxLength(32)
                            ->helperText($this->secretHint($this->hasPayeReference)),
                        Forms\Components\TextInput::make('accounts_office_reference')
                            ->label(__('finance.accounting.fields.accounts_office_reference'))
                            ->password()
                            ->autocomplete('new-password')
                            ->maxLength(32)
                            ->helperText($this->secretHint($this->hasAccountsOfficeReference)),
                    ]),
            ])
            ->disabled(fn (): bool => ! $this->canManageProfile())
            ->statePath('data');
    }

    public function save(): void
    {
        if (! $this->canManageProfile()) {
            abort(403);
        }

        $business = currentBusiness();
        $actor = auth()->user();
        abort_unless($business instanceof Business && $actor instanceof User, 404);
        $profile = AccountingProfile::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->firstOrFail();
        $data = $this->form->getState();
        $data['company_utr'] = filled($data['company_utr'] ?? null)
            ? preg_replace('/\s+/', '', (string) $data['company_utr'])
            : null;
        $data['vat_number'] = filled($data['vat_number'] ?? null)
            ? strtoupper(preg_replace('/\s+/', '', (string) $data['vat_number']))
            : null;

        try {
            app(UpdateAccountingProfileAction::class)->execute($profile, $actor, $data);
            Notification::make()->title(__('finance.accounting.saved'))->success()->send();
            $this->mount();
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    public function canManageProfile(): bool
    {
        $business = currentBusiness();
        $actor = auth()->user();

        return $business instanceof Business
            && $actor instanceof User
            && PermissionHelper::allows($actor, 'manage_finance')
            && $actor->businessMemberships()
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->whereIn('role', ['owner', 'admin'])
                ->exists();
    }

    private function secretHint(bool $isSaved, ?string $extra = null): string
    {
        return collect([
            $isSaved ? __('finance.accounting.hints.secret_saved') : null,
            __('finance.accounting.hints.secret_blank'),
            $extra ? __('finance.accounting.hints.'.$extra) : null,
        ])->filter()->implode(' ');
    }
}
