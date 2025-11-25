<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string
    {
        return 'Create New Role';
    }

    public function getHeading(): string
    {
        return 'Add New Role';
    }

    public function getSubheading(): ?string
    {
        return 'Create a custom role with specific permissions for your users.';
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        Notification::make()
            ->success()
            ->title('Role created successfully!')
            ->body("Role '{$record->name}' has been created with " . $record->permissions()->count() . " permissions.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
