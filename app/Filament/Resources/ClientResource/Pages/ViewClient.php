<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ContractResource;
use App\Filament\Resources\LeadResource;
use App\Services\Account\PortalAccessService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Password;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected string $view = 'filament.resources.client-resource.pages.view-client';

    protected function getHeaderActions(): array
    {
        return [
            $this->portalAccessAction(),
            Action::make('createLead')
                ->label('Create Lead')
                ->icon('heroicon-o-funnel')
                ->color('warning')
                ->url(fn () => LeadResource::getUrl('create', ['client_id' => $this->record->id])),
            Action::make('newContract')
                ->label('New Contract')
                ->icon('heroicon-o-document-check')
                ->color('gray')
                ->url(fn () => ContractResource::getUrl('create') . '?client_id=' . $this->record->id),
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
                ? 'Konto portalu jest aktywne. Możesz wysłać link do resetowania hasła i opcjonalnie nadać dostęp do workspace.'
                : 'Zostanie utworzone konto użytkownika i wysłany e-mail z danymi logowania. Dostęp do workspace SaaS pozostaje opcjonalny.'
            )
            ->modalWidth('lg')

            // ── form fields (shown only when no account yet) ────────────────
            ->form(fn () => [
                TextInput::make('name')
                    ->label('Imię i nazwisko')
                    ->default($client()->primary_contact_name)
                    ->visible(fn () => ! $client()->portal_user_id)
                    ->required()
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('Adres e-mail (login)')
                    ->email()
                    ->default($client()->primary_contact_email)
                    ->visible(fn () => ! $client()->portal_user_id)
                    ->required()
                    ->maxLength(255),
                Checkbox::make('grant_workspace_access')
                    ->label('Nadaj także dostęp do workspace SaaS')
                    ->visible(fn () => (bool) $client()->business_id)
                    ->helperText('Tworzy membership w business_users tylko jawnie. Dla klienta agencyjnego domyślny tryb to portal-only.'),
                Checkbox::make('send_password_reset')
                    ->label('Wyślij link do resetowania hasła')
                    ->default(true)
                    ->visible(fn () => (bool) $client()->portal_user_id),
            ])

            // ── submit button label ─────────────────────────────────────────
            ->modalSubmitActionLabel(fn () => $client()->portal_user_id
                ? 'Zastosuj zmiany'
                : 'Utwórz konto i wyślij hasło e-mailem'
            )

            ->action(function (array $data) use ($client): void {
                $record = $client();
                $portalAccessService = app(PortalAccessService::class);
                $grantWorkspaceAccess = (bool) ($data['grant_workspace_access'] ?? false);

                // ── Case A: account already exists → send password reset ───
                if ($record->portal_user_id) {
                    $portalUser = $record->portalUser;

                    $messages = [];

                    if ($grantWorkspaceAccess) {
                        try {
                            $result = $portalAccessService->ensurePortalAccess($record, [
                                'grant_workspace_access' => true,
                                'send_invite' => false,
                                'invited_by' => auth()->id(),
                            ]);
                        } catch (DomainException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Nie udało się nadać workspace access')
                                ->body($e->getMessage())
                                ->send();

                            return;
                        }

                        $messages[] = $result['workspace_membership_created']
                            ? 'Workspace access został nadany.'
                            : 'Workspace access był już aktywny.';
                    }

                    if (($data['send_password_reset'] ?? false) && $portalUser) {
                        Password::sendResetLink(['email' => $portalUser->email]);

                        $messages[] = 'Link do resetowania hasła wysłany na adres: ' . $portalUser->email;
                    }

                    if ($messages === []) {
                        Notification::make()
                            ->warning()
                            ->title('Brak zmian')
                            ->body('Nie wybrano żadnej akcji do wykonania.')
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Dostęp zaktualizowany')
                        ->body(implode(' ', $messages))
                        ->send();

                    return;
                }

                // ── Case B: no account yet ─────────────────────────────────
                try {
                    $result = $portalAccessService->ensurePortalAccess($record, [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'grant_workspace_access' => $grantWorkspaceAccess,
                        'send_invite' => true,
                        'queue_invite' => false,
                        'invited_by' => auth()->id(),
                    ]);
                } catch (DomainException $e) {
                    Notification::make()
                        ->danger()
                        ->title('Nie udało się utworzyć dostępu do portalu')
                        ->body($e->getMessage())
                        ->send();

                    return;
                }

                $title = $result['user_was_created']
                    ? 'Konto portalu utworzone!'
                    : 'Konto portalu połączone';

                $body = $result['user_was_created']
                    ? "Dane logowania wysłane na adres: {$result['user']->email}"
                    : "Istniejące konto {$result['user']->email} zostało powiązane z tym klientem.";

                if ($result['workspace_membership_created']) {
                    $body .= ' Nadano też dostęp do workspace.';
                }

                Notification::make()
                    ->success()
                    ->title($title)
                    ->body($body)
                    ->send();
            });
    }
}

