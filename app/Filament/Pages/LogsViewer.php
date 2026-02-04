<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use Symfony\Component\Finder\Finder;

class LogsViewer extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $title = 'Application Logs';

    protected string $view = 'filament.pages.logs-viewer';

    public ?string $selectedFile = null;
    public ?string $logContent = null;
    public array $logFiles = [];
    public ?string $searchQuery = null;
    public ?string $filterLevel = null;
    public int $lineCount = 100;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->hasRole('super-admin');
    }

    public function mount(): void
    {
        $this->loadLogFiles();
    }

    public function loadLogFiles(): void
    {
        $logsPath = storage_path('logs');

        if (!is_dir($logsPath)) {
            return;
        }

        $finder = new Finder();
        $finder->files()->in($logsPath)->name('*.log')->sortByModifiedTime()->reverseSorting();

        $this->logFiles = [];
        foreach ($finder as $file) {
            $this->logFiles[] = [
                'name' => $file->getFilename(),
                'path' => $file->getRelativePathname(),
                'size' => $file->getSize(),
                'date' => $file->getMTime(),
            ];
        }

        if (empty($this->logFiles)) {
            $this->selectedFile = null;
            $this->logContent = 'No log files found.';
            return;
        }

        if (!$this->selectedFile) {
            $this->selectedFile = $this->logFiles[0]['path'] ?? null;
        }

        if ($this->selectedFile) {
            $this->loadLogContent();
        }
    }

    public function loadLogContent(): void
    {
        if (!$this->selectedFile) {
            $this->logContent = 'No log file selected.';
            return;
        }

        $logsPath = storage_path('logs');
        $filePath = $logsPath . '/' . $this->selectedFile;

        if (!file_exists($filePath)) {
            $this->logContent = 'Log file not found.';
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            $this->logContent = 'Unable to read log file.';
            return;
        }

        // Reverse to show newest logs first
        $lines = array_reverse($lines);

        // Apply line limit
        $lines = array_slice($lines, 0, $this->lineCount);

        // Apply search filter
        if ($this->searchQuery) {
            $lines = array_filter($lines, fn ($line) => stripos($line, $this->searchQuery) !== false);
        }

        // Apply log level filter
        if ($this->filterLevel) {
            $lines = array_filter($lines, fn ($line) => stripos($line, $this->filterLevel) !== false);
        }

        $this->logContent = implode("\n", $lines) ?: 'No matching log entries found.';
    }

    public function updatedSelectedFile(): void
    {
        $this->loadLogContent();
    }

    public function updatedSearchQuery(): void
    {
        $this->loadLogContent();
    }

    public function updatedFilterLevel(): void
    {
        $this->loadLogContent();
    }

    public function updatedLineCount(): void
    {
        $this->loadLogContent();
    }

    public function clearSelectedLog(): void
    {
        if (!$this->selectedFile) {
            Notification::make()
                ->title('Error')
                ->body('No log file selected.')
                ->danger()
                ->send();
            return;
        }

        if (!static::canAccess()) {
            abort(403);
        }

        $logsPath = storage_path('logs');
        $filePath = $logsPath . '/' . $this->selectedFile;

        if (file_exists($filePath)) {
            file_put_contents($filePath, '');
            $this->loadLogContent();

            Notification::make()
                ->title('Success')
                ->body('Log file cleared.')
                ->success()
                ->send();
        }
    }

    public function clearAllLogs(): void
    {
        if (!static::canAccess()) {
            abort(403);
        }

        $logsPath = storage_path('logs');

        if (is_dir($logsPath)) {
            $finder = new Finder();
            $finder->files()->in($logsPath)->name('*.log');

            foreach ($finder as $file) {
                file_put_contents($file->getPathname(), '');
            }
        }

        $this->loadLogFiles();
        $this->logContent = 'All logs cleared.';

        Notification::make()
            ->title('Success')
            ->body('All log files cleared.')
            ->success()
            ->send();
    }

    public function downloadLog(): void
    {
        if (!$this->selectedFile) {
            Notification::make()
                ->title('Error')
                ->body('No log file selected.')
                ->danger()
                ->send();
            return;
        }

        $logsPath = storage_path('logs');
        $filePath = $logsPath . '/' . $this->selectedFile;

        if (!file_exists($filePath)) {
            Notification::make()
                ->title('Error')
                ->body('Log file not found.')
                ->danger()
                ->send();
            return;
        }

        Notification::make()
            ->title('Success')
            ->body('Log file downloaded.')
            ->success()
            ->send();
    }
}
