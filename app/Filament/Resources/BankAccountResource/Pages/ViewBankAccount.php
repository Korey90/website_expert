<?php

namespace App\Filament\Resources\BankAccountResource\Pages;

use App\Actions\Finance\ImportBankStatementCsvAction;
use App\Filament\Resources\BankAccountResource;
use App\Models\BankAccount;
use App\Models\User;
use App\Services\Finance\BankStatementCsvParser;
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

    public function getSubheading(): ?string
    {
        return __('admin.migration.banking.accounts.view_subheading');
    }

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

                    Notification::make()
                        ->title(__($result->dryRun
                            ? 'finance.bank_imports.notifications.dry_run_complete'
                            : 'finance.bank_imports.notifications.import_complete'))
                        ->body($body)
                        ->color($result->parseResult->failedRows() > 0 ? 'warning' : 'success')
                        ->send();
                } catch (DomainException) {
                    Notification::make()
                        ->title(__('finance.bank_imports.notifications.failed'))
                        ->body(__('finance.bank_imports.notifications.check_file'))
                        ->danger()
                        ->send();
                } finally {
                    Storage::disk('local')->delete($storedPath);
                }
            });
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
