<?php

namespace App\Filament\Resources\BankAccountResource\Pages;

use App\Actions\Finance\ImportBankStatementCsvAction;
use App\Filament\Resources\BankAccountResource;
use App\Models\BankAccount;
use App\Models\User;
use App\Services\Finance\BankStatementCsvParser;
use App\Services\Finance\Exceptions\BankStatementCsvImportException;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ViewBankAccount extends ViewRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->importCsvAction(),
            EditAction::make(),
        ];
    }

    private function importCsvAction(): Action
    {
        return Action::make('importCsv')
            ->label(__('finance.bank_imports.actions.import_csv'))
            ->icon('heroicon-o-arrow-up-tray')
            ->modalHeading(__('finance.bank_imports.modal.heading'))
            ->modalDescription(__('finance.bank_imports.modal.description'))
            ->modalWidth('4xl')
            ->modalSubmitActionLabel(__('finance.bank_imports.actions.run'))
            ->visible(fn (): bool => $this->getRecord()->status === BankAccount::STATUS_ACTIVE
                && BankAccountResource::canEdit($this->getRecord()))
            ->form([
                FileUpload::make('csv_file')
                    ->label(__('finance.bank_imports.fields.file'))
                    ->disk('local')
                    ->directory('bank-statement-imports')
                    ->visibility('private')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                    ->maxSize(5120)
                    ->storeFileNamesIn('csv_file_name')
                    ->live()
                    ->required(),
                Hidden::make('csv_file_name'),
                Section::make(__('finance.bank_imports.sections.format'))
                    ->columns(3)
                    ->schema([
                        Select::make('delimiter')
                            ->label(__('finance.bank_imports.fields.delimiter'))
                            ->options([
                                'auto' => __('finance.bank_imports.options.auto'),
                                'comma' => __('finance.bank_imports.options.comma'),
                                'semicolon' => __('finance.bank_imports.options.semicolon'),
                                'tab' => __('finance.bank_imports.options.tab'),
                            ])
                            ->default('auto')
                            ->live()
                            ->required(),
                        Select::make('date_format')
                            ->label(__('finance.bank_imports.fields.date_format'))
                            ->options([
                                'auto' => __('finance.bank_imports.options.auto_uk_date'),
                                'd/m/Y' => 'DD/MM/YYYY',
                                'Y-m-d' => 'YYYY-MM-DD',
                                'Y-m-d H:i:s' => 'YYYY-MM-DD HH:MM:SS',
                                'd-m-Y' => 'DD-MM-YYYY',
                                'd.m.Y' => 'DD.MM.YYYY',
                                'm/d/Y' => 'MM/DD/YYYY',
                            ])
                            ->default('auto')
                            ->required(),
                        Select::make('decimal_separator')
                            ->label(__('finance.bank_imports.fields.decimal_separator'))
                            ->options([
                                'auto' => __('finance.bank_imports.options.auto'),
                                'dot' => __('finance.bank_imports.options.dot'),
                                'comma' => __('finance.bank_imports.options.decimal_comma'),
                            ])
                            ->default('auto')
                            ->required(),
                    ]),
                Section::make(__('finance.bank_imports.sections.mapping'))
                    ->description(__('finance.bank_imports.hints.mapping'))
                    ->columns(3)
                    ->schema([
                        $this->mappingSelect('booking_date'),
                        $this->mappingSelect('value_date'),
                        $this->mappingSelect('amount'),
                        $this->mappingSelect('debit'),
                        $this->mappingSelect('credit'),
                        $this->mappingSelect('currency'),
                        $this->mappingSelect('reference'),
                        $this->mappingSelect('counterparty'),
                        $this->mappingSelect('provider_id'),
                    ]),
                Toggle::make('dry_run')
                    ->label(__('finance.bank_imports.fields.dry_run'))
                    ->helperText(__('finance.bank_imports.hints.dry_run'))
                    ->default(true),
            ])
            ->action(function (array $data): void {
                $storedPath = $this->singleFileState($data['csv_file'] ?? null);

                if ($storedPath === null) {
                    Notification::make()->title(__('finance.bank_imports.notifications.failed'))->danger()->send();

                    return;
                }

                try {
                    $path = Storage::disk('local')->path($storedPath);
                    $mapping = [];

                    foreach (['booking_date', 'value_date', 'amount', 'debit', 'credit', 'currency', 'reference', 'counterparty', 'provider_id'] as $field) {
                        $mapping[$field] = $data[$field] ?? null;
                    }

                    $user = auth()->user();

                    if (! $user instanceof User) {
                        throw new DomainException('An authenticated user is required.');
                    }

                    $result = app(ImportBankStatementCsvAction::class)->execute(
                        $this->getRecord(),
                        $user,
                        $path,
                        $mapping,
                        [
                            'delimiter' => $data['delimiter'] ?? 'auto',
                            'date_format' => $data['date_format'] ?? 'auto',
                            'decimal_separator' => $data['decimal_separator'] ?? 'auto',
                            'dry_run' => (bool) ($data['dry_run'] ?? true),
                            'original_filename' => $this->singleFileName($data['csv_file_name'] ?? null) ?? basename($storedPath),
                        ],
                    );

                    $invalidRows = collect($result->parseResult->errors)
                        ->take(10)
                        ->pluck('rowNumber')
                        ->implode(', ');
                    $body = __('finance.bank_imports.notifications.summary', [
                        'total' => $result->parseResult->totalRows,
                        'valid' => $result->parseResult->validRows(),
                        'imported' => $result->importedRows,
                        'duplicates' => $result->duplicateRows,
                        'failed' => $result->parseResult->failedRows(),
                    ]);

                    if ($invalidRows !== '') {
                        $body .= ' '.__('finance.bank_imports.notifications.invalid_rows', ['rows' => $invalidRows]);
                    }

                    if ($result->parseResult->profile !== null) {
                        $body .= ' '.__('finance.bank_imports.notifications.profile_detected', [
                            'profile' => $this->profileLabel($result->parseResult->profile),
                        ]);
                    }

                    $detectedMapping = collect($result->parseResult->resolvedMapping)
                        ->filter()
                        ->map(fn (string $source, string $target): string => __("finance.bank_imports.fields.{$target}").': '.$source)
                        ->implode(', ');

                    if ($detectedMapping !== '') {
                        $body .= ' '.__('finance.bank_imports.notifications.mapping_detected', ['mapping' => $detectedMapping]);
                    }

                    $errorReasons = collect($result->parseResult->errors)
                        ->countBy('code')
                        ->map(fn (int $count, string $code): string => __('finance.bank_imports.notifications.error_count', [
                            'reason' => $this->importErrorMessage($code),
                            'count' => $count,
                        ]))
                        ->implode('; ');

                    if ($errorReasons !== '') {
                        $body .= ' '.__('finance.bank_imports.notifications.error_summary', ['reasons' => $errorReasons]);
                    }

                    Notification::make()
                        ->title(__($result->dryRun
                            ? 'finance.bank_imports.notifications.dry_run_complete'
                            : 'finance.bank_imports.notifications.import_complete'))
                        ->body($body)
                        ->color($result->parseResult->failedRows() > 0 ? 'warning' : 'success')
                        ->send();
                } catch (DomainException $exception) {
                    $errorCode = $exception instanceof BankStatementCsvImportException
                        ? $exception->errorCode
                        : 'generic';

                    Notification::make()
                        ->title(__('finance.bank_imports.notifications.failed'))
                        ->body($this->importErrorMessage($errorCode))
                        ->danger()
                        ->send();
                } finally {
                    Storage::disk('local')->delete($storedPath);
                }
            });
    }

    private function importErrorMessage(string $code): string
    {
        $supported = [
            'missing_booking_date',
            'missing_amount',
            'invalid_date',
            'invalid_date_format',
            'booking_date_required',
            'invalid_amount',
            'amount_required',
            'amount_out_of_range',
            'conflicting_amount_columns',
            'invalid_decimal_separator',
            'unsupported_currency',
            'currency_mismatch',
            'unsupported_status',
            'unsupported_fee',
            'column_count_mismatch',
            'no_valid_rows',
        ];

        return in_array($code, $supported, true)
            ? __("finance.bank_imports.errors.{$code}")
            : __('finance.bank_imports.notifications.check_file');
    }

    private function profileLabel(string $profile): string
    {
        return match ($profile) {
            'revolut_pl' => __('finance.bank_imports.profiles.revolut_pl'),
            default => $profile,
        };
    }

    private function mappingSelect(string $field): Select
    {
        return Select::make($field)
            ->label(__("finance.bank_imports.fields.{$field}"))
            ->placeholder(__('finance.bank_imports.options.auto_detect'))
            ->options(fn (Get $get): array => $this->csvHeaderOptions($get('csv_file'), (string) ($get('delimiter') ?? 'auto')))
            ->searchable();
    }

    /** @return array<string, string> */
    private function csvHeaderOptions(mixed $state, string $delimiter): array
    {
        $path = $this->uploadedFilePath($state);

        if ($path === null) {
            return [];
        }

        try {
            $headers = app(BankStatementCsvParser::class)->headers($path, $delimiter);

            return array_combine($headers, $headers) ?: [];
        } catch (DomainException) {
            return [];
        }
    }

    private function uploadedFilePath(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = reset($state);
        }

        if ($state instanceof TemporaryUploadedFile) {
            return $state->getRealPath();
        }

        $storedPath = $this->singleFileState($state);

        return $storedPath === null ? null : Storage::disk('local')->path($storedPath);
    }

    private function singleFileState(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = reset($state);
        }

        return is_string($state) && $state !== '' ? $state : null;
    }

    private function singleFileName(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = reset($state);
        }

        return is_string($state) && $state !== '' ? $state : null;
    }
}
