<?php

namespace App\Filament\Pages;

use App\Enums\MaintenanceCommandType;
use App\Models\MaintenanceAction;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class MaintenanceTools extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::WrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $title = 'Maintenance Tools';

    protected string $view = 'filament.pages.maintenance-tools';

    public string $command = 'optimize';

    public ?string $output = null;

    public bool $isRunning = false;

    public ?string $lastRunAt = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole('super-admin');
    }

    public function getAllowedCommands(): array
    {
        $commands = [];
        foreach (MaintenanceCommandType::cases() as $commandType) {
            $commands[$commandType->value] = $commandType->label();
        }
        return $commands;
    }

    public function getCommandType(string $command): ?MaintenanceCommandType
    {
        return MaintenanceCommandType::tryFrom($command);
    }

    public function runCommand(?string $cmd = null): void
    {
        if (! static::canAccess()) {
            abort(403);
        }

        $command = $cmd ?: $this->command;
        $commandType = $this->getCommandType($command);

        if (! $commandType) {
            $this->output = "Command not allowed: {$command}";
            return;
        }

        $this->isRunning = true;

        try {
            // Use HTTP request for shared hosting compatibility
            $token = config('app.artisan_token');
            $url = route('artisan.execute', ['command' => $command]) . '?token=' . $token;

            // Make HTTP request to execute command
            $response = \Illuminate\Support\Facades\Http::timeout(120)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $this->output = $data['output'] ?? 'Command executed successfully.';
                $this->lastRunAt = $data['executed_at'] ?? now()->toDateTimeString();

                Notification::make()
                    ->title('Command executed')
                    ->body("{$command} finished successfully.")
                    ->success()
                    ->send();
            } else {
                $data = $response->json();
                $errorMessage = $data['error'] ?? $data['message'] ?? 'Command failed with status: ' . $response->status();
                $this->output = "Status: {$response->status()}\n\n{$errorMessage}\n\nResponse:\n" . json_encode($data, JSON_PRETTY_PRINT);
                $this->lastRunAt = now()->toDateTimeString();

                Notification::make()
                    ->title('Command failed')
                    ->body($errorMessage)
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            $this->output = 'Error: ' . $e->getMessage() . "\n\nTrace:\n" . $e->getTraceAsString();
            $this->lastRunAt = now()->toDateTimeString();

            Notification::make()
                ->title('Command failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isRunning = false;
        }
    }
}
