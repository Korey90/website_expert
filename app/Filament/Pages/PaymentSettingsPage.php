<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentSettingsPage extends BasePage
{
    protected string $view = 'filament.pages.payment-settings';

    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-credit-card';
    protected static \UnitEnum|string|null   $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Payment Settings';
    protected static ?int    $navigationSort  = 10;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'payment_currency'       => Setting::get('payment_currency', 'GBP'),

            // Stripe
            'stripe_enabled'         => (bool) Setting::get('stripe_enabled', false),
            'stripe_pk'              => Setting::get('stripe_pk', ''),
            'stripe_sk'              => '',
            'stripe_webhook_secret'  => '',

            // PayU
            'payu_enabled'           => (bool) Setting::get('payu_enabled', false),
            'payu_sandbox'           => (bool) Setting::get('payu_sandbox', true),
            'payu_pos_id'            => Setting::get('payu_pos_id', ''),
            'payu_client_id'         => Setting::get('payu_client_id', ''),
            'payu_md5_key'           => '',
            'payu_client_secret'     => '',
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('General')
                    ->schema([
                        Forms\Components\Select::make('payment_currency')
                            ->label('Default currency')
                            ->options([
                                'GBP' => 'GBP — British Pound',
                                'EUR' => 'EUR — Euro',
                                'USD' => 'USD — US Dollar',
                                'PLN' => 'PLN — Polish Złoty',
                            ])
                            ->required(),
                    ]),

                Section::make('Stripe')
                    ->description('Accept card payments via Stripe Checkout. Get your keys at dashboard.stripe.com.')
                    ->schema([
                        Forms\Components\Toggle::make('stripe_enabled')
                            ->label('Enable Stripe payments'),

                        Forms\Components\TextInput::make('stripe_pk')
                            ->label('Publishable Key')
                            ->placeholder('pk_live_... or pk_test_...')
                            ->helperText('Visible in your Stripe dashboard → Developers → API keys'),

                        Forms\Components\TextInput::make('stripe_sk')
                            ->label('Secret Key')
                            ->password()
                            ->revealable()
                            ->placeholder('Leave blank to keep existing')
                            ->helperText('Starts with sk_live_ or sk_test_'),

                        Forms\Components\TextInput::make('stripe_webhook_secret')
                            ->label('Webhook Signing Secret')
                            ->password()
                            ->revealable()
                            ->placeholder('Leave blank to keep existing')
                            ->helperText('Stripe → Developers → Webhooks → your endpoint → Signing secret (whsec_...)'),
                    ]),

                Section::make('PayU')
                    ->description('Accept PayU payments (BLIK, bank transfers, cards). Register at secure.payu.com.')
                    ->schema([
                        Forms\Components\Toggle::make('payu_enabled')
                            ->label('Enable PayU payments'),

                        Forms\Components\Toggle::make('payu_sandbox')
                            ->label('Sandbox / test mode')
                            ->helperText('Use PayU sandbox environment (secure.snd.payu.com) for testing'),

                        Forms\Components\TextInput::make('payu_pos_id')
                            ->label('POS ID')
                            ->placeholder('123456')
                            ->helperText('From PayU panel → Point of Sales → POS ID'),

                        Forms\Components\TextInput::make('payu_client_id')
                            ->label('OAuth2 Client ID')
                            ->placeholder('123456'),

                        Forms\Components\TextInput::make('payu_md5_key')
                            ->label('Second key (MD5)')
                            ->password()
                            ->revealable()
                            ->placeholder('Leave blank to keep existing')
                            ->helperText('Used to verify IPN notification signatures'),

                        Forms\Components\TextInput::make('payu_client_secret')
                            ->label('OAuth2 Client Secret')
                            ->password()
                            ->revealable()
                            ->placeholder('Leave blank to keep existing'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('payment_currency', $data['payment_currency'] ?? 'GBP', 'payments');

        // Stripe
        Setting::set('stripe_enabled', $data['stripe_enabled'] ? '1' : '0', 'payments');
        Setting::set('stripe_pk',       $data['stripe_pk'] ?? '',             'payments');

        if (! empty($data['stripe_sk'])) {
            Setting::set('stripe_sk', $data['stripe_sk'], 'payments');
        }
        if (! empty($data['stripe_webhook_secret'])) {
            Setting::set('stripe_webhook_secret', $data['stripe_webhook_secret'], 'payments');
        }

        // PayU
        Setting::set('payu_enabled',   $data['payu_enabled'] ? '1' : '0', 'payments');
        Setting::set('payu_sandbox',   $data['payu_sandbox'] ? '1' : '0', 'payments');
        Setting::set('payu_pos_id',    $data['payu_pos_id'] ?? '',         'payments');
        Setting::set('payu_client_id', $data['payu_client_id'] ?? '',      'payments');

        if (! empty($data['payu_md5_key'])) {
            Setting::set('payu_md5_key', $data['payu_md5_key'], 'payments');
        }
        if (! empty($data['payu_client_secret'])) {
            Setting::set('payu_client_secret', $data['payu_client_secret'], 'payments');
        }

        Notification::make()
            ->title('Payment settings saved')
            ->success()
            ->send();
    }

    public function testStripe(): void
    {
        $sk = Setting::get('stripe_sk', '');
        if (! $sk) {
            Notification::make()
                ->title('No Stripe Secret Key configured')
                ->body('Enter and save the Secret Key first.')
                ->danger()
                ->send();
            return;
        }

        try {
            \Stripe\Stripe::setApiKey($sk);
            \Stripe\Balance::retrieve();
            Notification::make()
                ->title('Stripe connection successful')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Stripe error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_stripe')
                ->label('Test Stripe')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('testStripe'),
        ];
    }
}
