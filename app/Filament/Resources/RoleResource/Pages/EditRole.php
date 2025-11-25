<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string
    {
        return 'Edit Role';
    }

    public function getHeading(): string
    {
        return "Edit {$this->record->name}";
    }

    public function getSubheading(): ?string
    {
        return "Update role permissions and settings.";
    }

    protected function getHeaderActions(): array
    {
        $protectedRoles = ['super-admin', 'admin', 'teacher', 'uploader', 'guardian', 'student'];

        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(function (): bool {
                    $protectedRoles = ['super-admin', 'admin', 'teacher', 'uploader', 'guardian', 'student'];
                    return auth()->user()?->hasRole('super-admin')
                        && !in_array($this->record->name, $protectedRoles);
                })
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to delete this role? Users with this role will lose their permissions.'),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        Notification::make()
            ->success()
            ->title('Role updated successfully!')
            ->body("Changes to '{$record->name}' have been saved. It now has " . $record->permissions()->count() . " permissions.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
