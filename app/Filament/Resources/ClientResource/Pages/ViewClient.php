<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Mail\PortalInviteMail;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->portalAccessAction(),
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    private function portalAccessAction(): Action
    {
        $client = fn () => $this->getRecord();

        return Action::make('portalAccess')
            ->label(fn () => $client()->portal_user_id ? 'Portal – aktywny' : 'Utwórz dostęp do portalu')
            ->icon(fn () => $client()->portal_user_id ? 'heroicon-o-check-circle' : 'heroicon-o-user-plus')
            ->color(fn () => $client()->portal_user_id ? 'success' : 'primary')

            // ── modal when account already exists ──────────────────────────
            ->modalHeading(fn () => $client()->portal_user_id
                ? 'Konto portalu – ' . ($client()->portalUser?->email ?? '')
                : 'Utwórz dostęp do portalu klienta'
            )
            ->modalDescription(fn () => $client()->portal_user_id
                ? 'Konto portalu jest aktywne. Możesz wysłać klientowi link do resetowania hasła.'
                : 'Zostanie utworzone konto użytkownika i wysłany e-mail z danymi logowania.'
            )
            ->modalWidth('lg')

            // ── form fields (shown only when no account yet) ────────────────
            ->form(fn () => $client()->portal_user_id ? [] : [
                TextInput::make('name')
                    ->label('Imię i nazwisko')
                    ->default($client()->primary_contact_name)
                    ->required()
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('Adres e-mail (login)')
                    ->email()
                    ->default($client()->primary_contact_email)
                    ->required()
                    ->maxLength(255),
            ])

            // ── submit button label ─────────────────────────────────────────
            ->modalSubmitActionLabel(fn () => $client()->portal_user_id
                ? 'Wyślij link do resetowania hasła'
                : 'Utwórz konto i wyślij hasło e-mailem'
            )

            ->action(function (array $data) use ($client): void {
                $record = $client();

                // ── Case A: account already exists → send password reset ───
                if ($record->portal_user_id) {
                    $portalUser = $record->portalUser;

                    if ($portalUser) {
                        Password::sendResetLink(['email' => $portalUser->email]);

                        Notification::make()
                            ->success()
                            ->title('Link do resetowania hasła wysłany')
                            ->body('Wiadomość e-mail wysłana na adres: ' . $portalUser->email)
                            ->send();
                    }

                    return;
                }

                // ── Case B: no account yet ─────────────────────────────────
                $email = $data['email'];
                $name  = $data['name'];

                // Re-use existing User if email already registered
                $existingUser = User::where('email', $email)->first();

                if ($existingUser) {
                    $record->update(['portal_user_id' => $existingUser->id]);

                    if (! $existingUser->hasRole('client')) {
                        $existingUser->assignRole('client');
                    }

                    Notification::make()
                        ->success()
                        ->title('Konto portalu połączone')
                        ->body("Istniejące konto {$email} zostało powiązane z tym klientem.")
                        ->send();

                    return;
                }

                // Create a brand-new portal user
                $plainPassword = Str::password(12, symbols: false);

                $user = User::create([
                    'name'     => $name,
                    'email'    => $email,
                    'password' => Hash::make($plainPassword),
                    'is_active' => true,
                    'locale'   => 'pl',
                ]);

                $user->assignRole('client');
                $record->update(['portal_user_id' => $user->id]);

                $companyName = config('mail.from.name', config('app.name'));

                Mail::to($email)->send(new PortalInviteMail(
                    clientName:    $name,
                    loginEmail:    $email,
                    plainPassword: $plainPassword,
                    loginUrl:      route('login'),
                    companyName:   $companyName,
                ));

                Notification::make()
                    ->success()
                    ->title('Konto portalu utworzone!')
                    ->body("Dane logowania wysłane na adres: {$email}")
                    ->send();
            });
    }
}

