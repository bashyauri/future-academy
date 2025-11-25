<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPermission extends EditRecord
{
    protected static string $resource = PermissionResource::class;

    public function getTitle(): string
    {
        return 'Edit Permission';
    }

    public function getHeading(): string
    {
        return "Edit {$this->record->name}";
    }

    public function getSubheading(): ?string
    {
        return "Update permission settings and role assignments.";
    }

    protected function getHeaderActions(): array
    {
        $protectedPermissions = [
            'manage users',
            'upload questions',
            'view stats',
            'manage subjects',
            'manage topics',
            'manage quizzes',
            'view reports',
            'manage subscriptions',
        ];

        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(function (): bool {
                    $protectedPermissions = [
                        'manage users',
                        'upload questions',
                        'view stats',
                        'manage subjects',
                        'manage topics',
                        'manage quizzes',
                        'view reports',
                        'manage subscriptions',
                    ];
                    return auth()->user()?->hasRole('super-admin')
                        && !in_array($this->record->name, $protectedPermissions);
                })
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to delete this permission? Roles and users will lose this permission.'),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        Notification::make()
            ->success()
            ->title('Permission updated successfully!')
            ->body("Changes to '{$record->name}' have been saved. It's now assigned to " . $record->roles()->count() . " role(s).")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
