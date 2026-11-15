<?php

namespace App\Filament\Pages;

use App\Actions\Admin\ParseLogFileAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogViewerPage extends BasePage
{
    protected string $view = 'filament.pages.log-viewer';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Log Viewer';

    protected static ?int $navigationSort = 20;

    public string $selectedFile = '';

    public string $levelFilter = 'ALL';

    public string $search = '';

    public int $perPage = 50;

    public int $page = 1;

    public function mount(): void
    {
        $files = $this->logFiles();
        $this->selectedFile = $files->first() ?? '';
    }

    /** @return Collection<int, string> filenames (not full paths) */
    public function logFiles(): Collection
    {
        $logPath = storage_path('logs');

        return collect(glob("{$logPath}/*.log") ?: [])
            ->map(fn (string $path) => basename($path))
            ->sort()
            ->reverse()
            ->values();
    }

    public function levels(): array
    {
        return ['ALL', 'ERROR', 'WARNING', 'INFO', 'DEBUG', 'NOTICE', 'CRITICAL', 'ALERT', 'EMERGENCY'];
    }

    public function entries(): Collection
    {
        if ($this->selectedFile === '') {
            return collect();
        }

        $filePath = storage_path('logs/'.basename($this->selectedFile));

        return app(ParseLogFileAction::class)
            ->execute($filePath, $this->levelFilter, $this->search);
    }

    public function paginatedEntries(): array
    {
        $all = $this->entries();
        $total = $all->count();
        $offset = ($this->page - 1) * $this->perPage;

        return [
            'items' => $all->slice($offset, $this->perPage)->values()->all(),
            'total' => $total,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'totalPages' => (int) ceil($total / $this->perPage),
        ];
    }

    public function updatedSelectedFile(): void
    {
        $this->page = 1;
    }

    public function updatedLevelFilter(): void
    {
        $this->page = 1;
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function nextPage(): void
    {
        $data = $this->paginatedEntries();
        if ($this->page < $data['totalPages']) {
            $this->page++;
        }
    }

    public function downloadLog(): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);

        $filename = basename($this->selectedFile);
        $filePath = storage_path('logs/'.$filename);

        abort_unless(file_exists($filePath), 404);

        return response()->streamDownload(function () use ($filePath): void {
            readfile($filePath);
        }, $filename, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function getTitle(): string
    {
        return __('admin.migration.administration.log_viewer.title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.migration.administration.log_viewer.subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('admin.migration.administration.log_viewer.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->page = 1;
                    Notification::make()
                        ->title(__('admin.migration.administration.log_viewer.refreshed'))
                        ->success()
                        ->send();
                }),

            Action::make('download')
                ->label(__('admin.migration.administration.log_viewer.download'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (): bool => $this->selectedFile !== '')
                ->action(function () {
                    return $this->downloadLog();
                }),

            Action::make('clearLog')
                ->label(__('admin.migration.administration.log_viewer.clear'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn (): bool => $this->selectedFile !== '')
                ->requiresConfirmation()
                ->modalHeading(__('admin.migration.administration.log_viewer.clear_heading'))
                ->modalDescription(__('admin.migration.administration.log_viewer.clear_description'))
                ->modalSubmitActionLabel(__('admin.migration.administration.log_viewer.clear_submit'))
                ->action(function () {
                    abort_unless(static::canAccess(), 403);

                    if ($this->selectedFile === '') {
                        return;
                    }

                    $filePath = storage_path('logs/'.basename($this->selectedFile));

                    abort_unless(file_exists($filePath), 404);

                    file_put_contents($filePath, '');

                    $this->page = 1;

                    Notification::make()
                        ->title(__('admin.migration.administration.log_viewer.cleared'))
                        ->body(__('admin.migration.administration.log_viewer.cleared_description', ['file' => $this->selectedFile]))
                        ->success()
                        ->send();
                }),
        ];
    }
}
