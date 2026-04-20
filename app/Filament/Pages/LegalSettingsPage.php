<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LegalSettingsPage extends BasePage
{
    protected string $view = 'filament.pages.legal-settings';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-scale';
    protected static \UnitEnum|string|null   $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Legal & Company';
    protected static ?int    $navigationSort  = 9;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            // Company Details
            'company_name'    => Setting::get('legal.company_name',    'WebsiteExpert Ltd'),
            'company_number'  => Setting::get('legal.company_number',  ''),
            'company_address' => Setting::get('legal.company_address', ''),
            'vat_number'      => Setting::get('legal.vat_number',      ''),
            'company_email'   => Setting::get('legal.company_email',   'hello@websiteexpert.co.uk'),
            'company_phone'   => Setting::get('legal.company_phone',   ''),
            // Data Protection
            'ico_number'             => Setting::get('legal.ico_number',             ''),
            'ico_registration_url'   => Setting::get('legal.ico_registration_url',   'https://ico.org.uk/ESDWebPages/Entry/'),
            'privacy_email'          => Setting::get('legal.privacy_email',          'privacy@websiteexpert.co.uk'),
            'dpo_name'               => Setting::get('legal.dpo_name',               ''),
            'data_retention_years'   => Setting::get('legal.data_retention_years',   '7'),
            // Customer Service
            'complaints_email'    => Setting::get('legal.complaints_email',    'support@websiteexpert.co.uk'),
            'complaints_phone'    => Setting::get('legal.complaints_phone',    ''),
            'response_days'       => Setting::get('legal.response_days',       '14'),
            'deposit_percent'     => Setting::get('legal.deposit_percent',     '50'),
            'payment_terms_days'  => Setting::get('legal.payment_terms_days',  '30'),
            // Cookie queries
            'cookie_policy_email' => Setting::get('legal.cookie_policy_email', 'privacy@websiteexpert.co.uk'),
            // Document Dates & Versions
            'privacy_effective_date'       => Setting::get('legal.privacy_effective_date',       ''),
            'privacy_version'              => Setting::get('legal.privacy_version',              '1.0'),
            'terms_effective_date'         => Setting::get('legal.terms_effective_date',         ''),
            'terms_version'                => Setting::get('legal.terms_version',                '1.0'),
            'cookies_effective_date'       => Setting::get('legal.cookies_effective_date',       ''),
            'cookies_version'              => Setting::get('legal.cookies_version',              '1.0'),
            'accessibility_effective_date' => Setting::get('legal.accessibility_effective_date', ''),
            'accessibility_version'        => Setting::get('legal.accessibility_version',        '1.0'),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Company Details')
                    ->description('Used in all legal documents via {{legal.company_name}}, {{legal.company_address}}, etc.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Legal company name')
                            ->placeholder('WebsiteExpert Ltd')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('company_number')
                            ->label('Companies House number')
                            ->placeholder('e.g. 12345678')
                            ->helperText('UK company registration number'),
                        Forms\Components\TextInput::make('vat_number')
                            ->label('VAT number')
                            ->placeholder('GB 123 456 789'),
                        Forms\Components\Textarea::make('company_address')
                            ->label('Registered address')
                            ->placeholder("123 High Street\nLondon\nW1A 1AA")
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('company_email')
                            ->label('General contact email')
                            ->email()
                            ->placeholder('hello@websiteexpert.co.uk'),
                        Forms\Components\TextInput::make('company_phone')
                            ->label('Contact phone')
                            ->tel()
                            ->placeholder('+44 20 1234 5678'),
                    ]),

                Section::make('Data Protection (Privacy Policy)')
                    ->description('Used in the Privacy Policy via {{legal.ico_number}}, {{legal.privacy_email}}, etc.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('ico_number')
                            ->label('ICO Registration number')
                            ->placeholder('ZB1234567')
                            ->helperText('Register at ico.org.uk/registration — required for UK companies processing personal data'),
                        Forms\Components\TextInput::make('data_retention_years')
                            ->label('Data retention period (years)')
                            ->numeric()
                            ->placeholder('7')
                            ->helperText('HMRC requires 7 years for financial records'),
                        Forms\Components\TextInput::make('ico_registration_url')
                            ->label('ICO register entry URL')
                            ->url()
                            ->placeholder('https://ico.org.uk/ESDWebPages/Entry/ZB…')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('privacy_email')
                            ->label('Privacy / GDPR contact email')
                            ->email()
                            ->placeholder('privacy@websiteexpert.co.uk'),
                        Forms\Components\TextInput::make('dpo_name')
                            ->label('DPO name (optional)')
                            ->placeholder('Leave blank if no DPO appointed'),
                    ]),

                Section::make('Customer Service & Complaints (Terms & Conditions)')
                    ->description('Used in T&C via {{legal.complaints_email}}, {{legal.deposit_percent}}, etc.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('complaints_email')
                            ->label('Complaints email')
                            ->email()
                            ->placeholder('support@websiteexpert.co.uk'),
                        Forms\Components\TextInput::make('complaints_phone')
                            ->label('Complaints phone')
                            ->tel()
                            ->placeholder('+44 20 1234 5678'),
                        Forms\Components\TextInput::make('response_days')
                            ->label('Response time (days)')
                            ->numeric()
                            ->placeholder('14'),
                        Forms\Components\TextInput::make('deposit_percent')
                            ->label('Initial deposit required (%)')
                            ->numeric()
                            ->suffix('%')
                            ->placeholder('50'),
                        Forms\Components\TextInput::make('payment_terms_days')
                            ->label('Payment terms (days)')
                            ->numeric()
                            ->placeholder('30'),
                        Forms\Components\TextInput::make('cookie_policy_email')
                            ->label('Cookie / privacy queries email')
                            ->email()
                            ->placeholder('privacy@websiteexpert.co.uk'),
                    ]),

                Section::make('Document Dates & Versions')
                    ->description('Used as {{legal.privacy_effective_date}}, {{legal.privacy_version}}, etc. — fill in before publishing documents.')
                    ->columns(4)
                    ->schema([
                        Forms\Components\DatePicker::make('privacy_effective_date')
                            ->label('Privacy Policy — effective from')
                            ->displayFormat('d M Y'),
                        Forms\Components\TextInput::make('privacy_version')
                            ->label('Privacy Policy — version')
                            ->placeholder('1.0'),
                        Forms\Components\DatePicker::make('terms_effective_date')
                            ->label('T&C — effective from')
                            ->displayFormat('d M Y'),
                        Forms\Components\TextInput::make('terms_version')
                            ->label('T&C — version')
                            ->placeholder('1.0'),
                        Forms\Components\DatePicker::make('cookies_effective_date')
                            ->label('Cookie Policy — effective from')
                            ->displayFormat('d M Y'),
                        Forms\Components\TextInput::make('cookies_version')
                            ->label('Cookie Policy — version')
                            ->placeholder('1.0'),
                        Forms\Components\DatePicker::make('accessibility_effective_date')
                            ->label('Accessibility Statement — effective from')
                            ->displayFormat('d M Y'),
                        Forms\Components\TextInput::make('accessibility_version')
                            ->label('Accessibility Statement — version')
                            ->placeholder('1.0'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $map = [
            'company_name'                 => 'legal.company_name',
            'company_number'               => 'legal.company_number',
            'company_address'              => 'legal.company_address',
            'vat_number'                   => 'legal.vat_number',
            'company_email'                => 'legal.company_email',
            'company_phone'                => 'legal.company_phone',
            'ico_number'                   => 'legal.ico_number',
            'ico_registration_url'         => 'legal.ico_registration_url',
            'privacy_email'                => 'legal.privacy_email',
            'dpo_name'                     => 'legal.dpo_name',
            'data_retention_years'         => 'legal.data_retention_years',
            'complaints_email'             => 'legal.complaints_email',
            'complaints_phone'             => 'legal.complaints_phone',
            'response_days'                => 'legal.response_days',
            'deposit_percent'              => 'legal.deposit_percent',
            'payment_terms_days'           => 'legal.payment_terms_days',
            'cookie_policy_email'          => 'legal.cookie_policy_email',
            'privacy_effective_date'       => 'legal.privacy_effective_date',
            'privacy_version'              => 'legal.privacy_version',
            'terms_effective_date'         => 'legal.terms_effective_date',
            'terms_version'                => 'legal.terms_version',
            'cookies_effective_date'       => 'legal.cookies_effective_date',
            'cookies_version'              => 'legal.cookies_version',
            'accessibility_effective_date' => 'legal.accessibility_effective_date',
            'accessibility_version'        => 'legal.accessibility_version',
        ];

        foreach ($map as $field => $key) {
            Setting::set($key, $data[$field] ?? '', 'legal');
        }

        Notification::make()
            ->title('Legal settings saved')
            ->body('All legal variables will be updated in documents immediately.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->action('save')
                ->icon('heroicon-o-check')
                ->color('primary'),
        ];
    }
}
