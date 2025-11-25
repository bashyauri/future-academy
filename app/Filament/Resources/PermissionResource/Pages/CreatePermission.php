<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;

    public function getTitle(): string
    {
        return 'Create New Permission';
    }

    public function getHeading(): string
    {
        return 'Add New Permission';
    }

    public function getSubheading(): ?string
    {
        return 'Create a custom permission and assign it to specific roles.';
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        Notification::make()
            ->success()
            ->title('Permission created successfully!')
            ->body("Permission '{$record->name}' has been created and assigned to " . $record->roles()->count() . " role(s).")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
