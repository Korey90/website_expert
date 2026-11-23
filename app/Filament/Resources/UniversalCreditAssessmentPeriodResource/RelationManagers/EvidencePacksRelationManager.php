<?php

namespace App\Filament\Resources\UniversalCreditAssessmentPeriodResource\RelationManagers;

use App\Actions\Finance\RetryUniversalCreditEvidencePackAction;
use App\Models\UniversalCreditEvidencePack;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class EvidencePacksRelationManager extends RelationManager
{
    protected static string $relationship = 'evidencePacks';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('finance.universal_credit.ui.relations.evidence_packs');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                Tables\Columns\TextColumn::make('version')->label(__('finance.universal_credit.ui.fields.version'))->prefix('v')->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('finance.universal_credit.ui.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.ui.pack_statuses.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        UniversalCreditEvidencePack::STATUS_COMPLETED => 'success',
                        UniversalCreditEvidencePack::STATUS_FAILED => 'danger',
                        UniversalCreditEvidencePack::STATUS_EXPIRED => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('locale')->label(__('finance.universal_credit.ui.fields.language'))->badge(),
                Tables\Columns\TextColumn::make('included_count')->label(__('finance.universal_credit.ui.fields.included_files'))->numeric(),
                Tables\Columns\TextColumn::make('excluded_count')->label(__('finance.universal_credit.ui.fields.excluded_files'))->numeric(),
                Tables\Columns\TextColumn::make('attempt_count')->label(__('finance.universal_credit.ui.fields.attempts'))->numeric(),
                Tables\Columns\TextColumn::make('generated_at')->label(__('finance.universal_credit.ui.fields.generated_at'))->dateTime()->placeholder('-'),
                Tables\Columns\TextColumn::make('expires_at')->label(__('finance.universal_credit.ui.fields.expires_at'))->dateTime()->placeholder('-'),
                Tables\Columns\TextColumn::make('last_error_code')->label(__('finance.universal_credit.ui.fields.error_code'))->placeholder('-'),
            ])
            ->actions([
                Action::make('download')
                    ->label(__('finance.universal_credit.ui.actions.download_pack'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (UniversalCreditEvidencePack $record): bool => $record->status === UniversalCreditEvidencePack::STATUS_COMPLETED
                        && Gate::allows('download', $record))
                    ->url(fn (UniversalCreditEvidencePack $record): string => route(
                        'admin.finance.universal-credit.evidence-packs.download',
                        ['evidencePack' => $record],
                    )),
                Action::make('retry')
                    ->label(__('finance.universal_credit.ui.actions.retry_pack'))
                    ->icon('heroicon-o-arrow-path')->color('warning')
                    ->visible(fn (UniversalCreditEvidencePack $record): bool => $record->status === UniversalCreditEvidencePack::STATUS_FAILED
                        && Gate::allows('retry', $record))
                    ->action(function (UniversalCreditEvidencePack $record): void {
                        try {
                            app(RetryUniversalCreditEvidencePackAction::class)->execute($record, auth()->user());
                            Notification::make()->title(__('finance.universal_credit.ui.notifications.pack_retried'))->success()->send();
                        } catch (DomainException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('version', 'desc');
    }
}
