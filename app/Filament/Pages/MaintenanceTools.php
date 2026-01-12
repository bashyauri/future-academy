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
            $buffer = new BufferedOutput();

            // Block DB-affecting commands in production unless explicitly allowed
            if (app()->isProduction() && $commandType->requiresForce() && ! (bool) config('maintenance.allow_db_commands')) {
                $this->output = 'Blocked in production. Set ALLOW_DB_COMMANDS=true to enable.';
                $this->isRunning = false;
                return;
            }

            // Provide --force for DB commands to run non-interactively in prod
            $params = [];
            if ($commandType->requiresForce()) {
                $params['--force'] = true;
            }

            Artisan::call($command, $params, $buffer);
            $this->output = $buffer->fetch() ?: 'Command executed successfully.';
            $this->lastRunAt = now()->toDateTimeString();

            // Audit log
            MaintenanceAction::create([
                'user_id' => auth()->id(),
                'command' => $command,
                'status' => 'success',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'output' => $this->output,
            ]);

            Notification::make()
                ->title('Command executed')
                ->body("{$command} finished successfully.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->output = 'Error: ' . $e->getMessage();
            $this->lastRunAt = now()->toDateTimeString();

            // Audit log (error)
            MaintenanceAction::create([
                'user_id' => auth()->id(),
                'command' => $command,
                'status' => 'error',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'output' => $this->output,
            ]);

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
