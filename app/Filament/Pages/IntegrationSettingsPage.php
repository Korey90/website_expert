<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\SmsService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;

class IntegrationSettingsPage extends Page
{
    protected string $view = 'filament.pages.integration-settings';

    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-puzzle-piece';
    protected static \UnitEnum|string|null   $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Integrations';
    protected static ?int    $navigationSort  = 20;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            // SMTP
            'mail_mailer'    => Setting::get('mail_mailer',    config('mail.default', 'smtp')),
            'mail_host'      => Setting::get('mail_host',      config('mail.mailers.smtp.host', '')),
            'mail_port'      => Setting::get('mail_port',      config('mail.mailers.smtp.port', 587)),
            'mail_username'  => Setting::get('mail_username',  config('mail.mailers.smtp.username', '')),
            'mail_password'  => Setting::get('mail_password',  ''),
            'mail_from'      => Setting::get('mail_from',      config('mail.from.address', '')),
            'mail_from_name' => Setting::get('mail_from_name', config('mail.from.name', '')),
            'mail_encryption'=> Setting::get('mail_encryption',config('mail.mailers.smtp.encryption', 'tls')),

            // Twilio SMS
            'twilio_enabled'  => (bool) Setting::get('twilio_enabled', false),
            'twilio_sid'      => Setting::get('twilio_sid',        config('services.twilio.sid',  '')),
            'twilio_token'    => Setting::get('twilio_token',      ''),
            'twilio_from'     => Setting::get('twilio_from',       config('services.twilio.from', '')),
            'sms_test_number' => Setting::get('sms_test_number',   ''),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Email / SMTP')
                    ->description('Configure the outbound mail driver used for all system emails.')
                    ->schema([
                        Forms\Components\Select::make('mail_mailer')
                            ->label('Mail driver')
                            ->options([
                                'smtp'     => 'SMTP',
                                'postmark' => 'Postmark',
                                'resend'   => 'Resend',
                                'mailgun'  => 'Mailgun',
                                'ses'      => 'Amazon SES',
                                'log'      => 'Log (testing)',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('mail_host')
                            ->label('SMTP Host')
                            ->placeholder('smtp.example.com')
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        Forms\Components\TextInput::make('mail_port')
                            ->label('SMTP Port')
                            ->numeric()
                            ->placeholder('587')
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        Forms\Components\Select::make('mail_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls'  => 'TLS (STARTTLS)',
                                'ssl'  => 'SSL',
                                ''     => 'None',
                            ])
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        Forms\Components\TextInput::make('mail_username')
                            ->label('Username / API key')
                            ->placeholder('user@example.com'),

                        Forms\Components\TextInput::make('mail_password')
                            ->label('Password / Secret')
                            ->password()
                            ->revealable()
                            ->placeholder('Leave blank to keep existing'),

                        Forms\Components\TextInput::make('mail_from')
                            ->label('From address')
                            ->email()
                            ->required()
                            ->placeholder('hello@yourdomain.com'),

                        Forms\Components\TextInput::make('mail_from_name')
                            ->label('From name')
                            ->placeholder('WebsiteExpert'),
                    ]),

                Section::make('SMS / Twilio')
                    ->description('Send SMS notifications via Twilio. Get credentials at console.twilio.com.')
                    ->schema([
                        Forms\Components\Toggle::make('twilio_enabled')
                            ->label('Enable SMS sending'),

                        Forms\Components\TextInput::make('twilio_sid')
                            ->label('Account SID')
                            ->placeholder('ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'),

                        Forms\Components\TextInput::make('twilio_token')
                            ->label('Auth Token')
                            ->password()
                            ->revealable()
                            ->placeholder('Leave blank to keep existing'),

                        Forms\Components\TextInput::make('twilio_from')
                            ->label('From (phone number or sender name)')
                            ->placeholder('+44xxxxxxxxxx or WebsiteExpert')
                            ->helperText('Phone number in E.164 format (+44...) OR alphanumeric sender name (max 11 chars, e.g. "WebExpert"). Alphanumeric requires an activated Twilio account and UK registration.'),

                        Forms\Components\TextInput::make('sms_test_number')
                            ->label('Test phone number')
                            ->placeholder('+44xxxxxxxxxx')
                            ->helperText('Your personal mobile — used only by the "Send test SMS" button.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // SMTP settings
        Setting::set('mail_mailer',    $data['mail_mailer']    ?? 'smtp',  'integrations');
        Setting::set('mail_host',      $data['mail_host']      ?? '',       'integrations');
        Setting::set('mail_port',      $data['mail_port']      ?? '587',    'integrations');
        Setting::set('mail_encryption',$data['mail_encryption']?? 'tls',   'integrations');
        Setting::set('mail_username',  $data['mail_username']  ?? '',       'integrations');
        Setting::set('mail_from',      $data['mail_from']      ?? '',       'integrations');
        Setting::set('mail_from_name', $data['mail_from_name'] ?? '',       'integrations');

        // Only overwrite password if a new value was provided
        if (! empty($data['mail_password'])) {
            Setting::set('mail_password', $data['mail_password'], 'integrations');
        }

        // Twilio settings
        Setting::set('twilio_enabled', $data['twilio_enabled'] ? '1' : '0', 'integrations');
        Setting::set('twilio_sid',     $data['twilio_sid']     ?? '',        'integrations');
        Setting::set('twilio_from',    $data['twilio_from']    ?? '',        'integrations');

        if (! empty($data['twilio_token'])) {
            Setting::set('twilio_token', $data['twilio_token'], 'integrations');
        }

        Setting::set('sms_test_number', $data['sms_test_number'] ?? '', 'integrations');

        Notification::make()
            ->title('Integration settings saved')
            ->success()
            ->send();
    }

    public function sendTestEmail(): void
    {
        $to = Setting::get('mail_from', config('mail.from.address', ''));
        if (! $to) {
            Notification::make()->title('No from-address configured')->danger()->send();
            return;
        }

        try {
            Mail::raw('This is a test email from WebsiteExpert Integration Settings.', function ($msg) use ($to) {
                $msg->to($to)->subject('Test Email — WebsiteExpert');
            });
            Notification::make()->title('Test email sent to ' . $to)->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
        }
    }

    public function sendTestSms(): void
    {
        $to = Setting::get('sms_test_number', '');
        if (! $to) {
            Notification::make()
                ->title('No test phone number set')
                ->body('Enter your mobile number in the "Test phone number" field and save first.')
                ->danger()
                ->send();
            return;
        }

        $sent = app(SmsService::class)->send($to, 'Test SMS from WebsiteExpert. Integration is working! ✅');

        if ($sent) {
            Notification::make()->title('Test SMS sent to ' . $to)->success()->send();
        } else {
            Notification::make()->title('SMS failed — check credentials and logs')->danger()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->action('save')
                ->icon('heroicon-o-check')
                ->color('primary'),

            Action::make('testEmail')
                ->label('Send test email')
                ->action('sendTestEmail')
                ->icon('heroicon-o-envelope')
                ->color('gray'),

            Action::make('testSms')
                ->label('Send test SMS')
                ->action('sendTestSms')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('gray'),
        ];
    }
}
