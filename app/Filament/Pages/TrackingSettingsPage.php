<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrackingSettingsPage extends BasePage
{
    protected string $view = 'filament.pages.tracking-settings';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static \UnitEnum|string|null   $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Tracking & Analytics';
    protected static ?int    $navigationSort  = 11;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'gtm_enabled'            => (bool) Setting::get('gtm_enabled', false),
            'gtm_id'                 => Setting::get('gtm_id', ''),
            'ga4_enabled'            => (bool) Setting::get('ga4_enabled', false),
            'ga4_id'                 => Setting::get('ga4_id', ''),
            'pixel_enabled'          => (bool) Setting::get('pixel_enabled', false),
            'pixel_id'               => Setting::get('pixel_id', ''),
            'gads_enabled'           => (bool) Setting::get('gads_enabled', false),
            'gads_id'                => Setting::get('gads_id', ''),
            'cookie_consent_enabled' => (bool) Setting::get('cookie_consent_enabled', true),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Google Tag Manager')
                    ->description('GTM manages all other tracking tags — enable this first.')
                    ->schema([
                        Forms\Components\Toggle::make('gtm_enabled')
                            ->label('Enable GTM'),
                        Forms\Components\TextInput::make('gtm_id')
                            ->label('GTM Container ID')
                            ->placeholder('GTM-XXXXXXX')
                            ->helperText('Found in tagmanager.google.com → Container → Overview'),
                    ]),

                Section::make('Google Analytics 4')
                    ->description('Use only if NOT loading GA4 through GTM.')
                    ->schema([
                        Forms\Components\Toggle::make('ga4_enabled')
                            ->label('Enable GA4 direct snippet'),
                        Forms\Components\TextInput::make('ga4_id')
                            ->label('Measurement ID')
                            ->placeholder('G-XXXXXXXXXX'),
                    ]),

                Section::make('Meta Pixel')
                    ->schema([
                        Forms\Components\Toggle::make('pixel_enabled')
                            ->label('Enable Meta Pixel'),
                        Forms\Components\TextInput::make('pixel_id')
                            ->label('Pixel ID')
                            ->placeholder('XXXXXXXXXXXXXXXXX'),
                    ]),

                Section::make('Google Ads')
                    ->schema([
                        Forms\Components\Toggle::make('gads_enabled')
                            ->label('Enable Google Ads tags'),
                        Forms\Components\TextInput::make('gads_id')
                            ->label('Conversion ID')
                            ->placeholder('AW-XXXXXXXXX'),
                    ]),

                Section::make('Cookie Consent')
                    ->description('Controls the custom cookie consent banner.')
                    ->schema([
                        Forms\Components\Toggle::make('cookie_consent_enabled')
                            ->label('Show cookie consent banner'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('gtm_enabled',            $data['gtm_enabled']            ? '1' : '0', 'tracking');
        Setting::set('gtm_id',                 $data['gtm_id'] ?? '',                        'tracking');
        Setting::set('ga4_enabled',            $data['ga4_enabled']            ? '1' : '0', 'tracking');
        Setting::set('ga4_id',                 $data['ga4_id'] ?? '',                        'tracking');
        Setting::set('pixel_enabled',          $data['pixel_enabled']          ? '1' : '0', 'tracking');
        Setting::set('pixel_id',               $data['pixel_id'] ?? '',                      'tracking');
        Setting::set('gads_enabled',           $data['gads_enabled']           ? '1' : '0', 'tracking');
        Setting::set('gads_id',                $data['gads_id'] ?? '',                       'tracking');
        Setting::set('cookie_consent_enabled', $data['cookie_consent_enabled'] ? '1' : '0', 'tracking');

        Notification::make()
            ->title('Tracking settings saved')
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
