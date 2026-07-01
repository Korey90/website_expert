<?php

namespace App\Filament\Pages;

use App\Actions\Account\ChangePasswordAction;
use App\Actions\Account\DisableTwoFactorAction;
use App\Actions\Account\EnableTwoFactorAction;
use App\Actions\Account\UpdateAdminProfileAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountProfilePage extends Page
{
    protected string $view = 'filament.pages.account-profile';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'My Account';

    protected static ?int $navigationSort = -1;

    // ─── Profile form state ────────────────────────────────────────────
    /** @var array<string, mixed>|null */
    public ?array $profileData = [];

    // ─── Password form state ───────────────────────────────────────────
    /** @var array<string, mixed>|null */
    public ?array $passwordData = [];

    // ─── 2FA state ─────────────────────────────────────────────────────
    /** @var array<string, mixed>|null */
    public ?array $twoFactorData = [];

    public string $twoFactorQrSvg = '';

    public string $twoFactorSecret = '';

    public bool $showQrStep = false;

    public function mount(): void
    {
        $user = auth()->user();

        $this->profileForm->fill([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'locale' => $user->locale ?? 'en',
            'avatar' => $user->avatar_url,
        ]);

        $this->twoFactorForm->fill([
            'totp_code' => '',
            'disable_totp_code' => '',
        ]);
    }

    // ─── Profile schema ────────────────────────────────────────────────

    public function profileForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make(__('account.section_profile'))
                    ->description(__('account.section_profile_desc'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label(__('account.avatar'))
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('name')
                            ->label(__('account.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label(__('account.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique('users', 'email', ignorable: auth()->user()),

                        Forms\Components\TextInput::make('phone')
                            ->label(__('account.phone'))
                            ->tel()
                            ->maxLength(30),

                        Forms\Components\Select::make('locale')
                            ->label(__('account.locale'))
                            ->options([
                                'en' => 'English',
                                'pl' => 'Polski',
                                'pt' => 'Português',
                            ])
                            ->required(),
                    ]),
            ])
            ->statePath('profileData');
    }

    // ─── Password schema ───────────────────────────────────────────────

    public function passwordForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make(__('account.section_password'))
                    ->description(__('account.section_password_desc'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label(__('account.current_password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('password')
                            ->label(__('account.new_password'))
                            ->password()
                            ->revealable()
                            ->rule(Password::min(8)->mixedCase()->numbers())
                            ->required(),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label(__('account.confirm_password'))
                            ->password()
                            ->revealable()
                            ->same('password')
                            ->required(),
                    ]),
            ])
            ->statePath('passwordData');
    }

    // ─── 2FA schema ────────────────────────────────────────────────────

    public function twoFactorForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make(__('account.section_2fa'))
                    ->description(__('account.section_2fa_desc'))
                    ->schema([
                        Forms\Components\TextInput::make('totp_code')
                            ->label(__('account.totp_code'))
                            ->visible(fn () => $this->showQrStep)
                            ->maxLength(6),

                        Forms\Components\TextInput::make('disable_totp_code')
                            ->label(__('account.totp_code_disable'))
                            ->visible(fn () => auth()->user()->two_factor_enabled && ! $this->showQrStep)
                            ->maxLength(6),
                    ]),
            ])
            ->statePath('twoFactorData');
    }

    // ─── Actions ───────────────────────────────────────────────────────

    public function saveProfile(UpdateAdminProfileAction $action): void
    {
        $data = $this->profileForm->getState();

        $action->execute(auth()->user(), $data);

        Notification::make()
            ->title(__('account.profile_saved'))
            ->success()
            ->send();
    }

    public function changePassword(ChangePasswordAction $action): void
    {
        $data = $this->passwordForm->getState();

        try {
            $action->execute(auth()->user(), $data);
        } catch (ValidationException $e) {
            $this->addError('passwordData.current_password', $e->errors()['current_password'][0] ?? '');

            return;
        }

        $this->passwordForm->fill([
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        Notification::make()
            ->title(__('account.password_changed'))
            ->success()
            ->send();
    }

    public function initiate2fa(EnableTwoFactorAction $action): void
    {
        $result = $action->generateSecret(auth()->user());

        $this->twoFactorQrSvg = $result['qr_svg'];
        $this->twoFactorSecret = $result['secret'];
        $this->showQrStep = true;
    }

    public function confirm2fa(EnableTwoFactorAction $action): void
    {
        $data = $this->twoFactorForm->getState();
        $code = trim($data['totp_code'] ?? '');

        try {
            $action->confirm(auth()->user(), $code);
        } catch (ValidationException $e) {
            $this->addError('twoFactorData.totp_code', $e->errors()['totp_code'][0] ?? '');

            return;
        }

        $this->showQrStep = false;
        $this->twoFactorQrSvg = '';
        $this->twoFactorSecret = '';

        Notification::make()
            ->title(__('account.2fa_enabled'))
            ->success()
            ->send();
    }

    public function disable2fa(DisableTwoFactorAction $action): void
    {
        $data = $this->twoFactorForm->getState();
        $code = trim($data['disable_totp_code'] ?? '');

        try {
            $action->execute(auth()->user(), $code);
        } catch (ValidationException $e) {
            $this->addError('twoFactorData.disable_totp_code', $e->errors()['disable_totp_code'][0] ?? '');

            return;
        }

        Notification::make()
            ->title(__('account.2fa_disabled'))
            ->success()
            ->send();
    }

    protected function getForms(): array
    {
        return ['profileForm', 'passwordForm', 'twoFactorForm'];
    }
}
