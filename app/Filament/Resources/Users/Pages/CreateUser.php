<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Create New User';
    }

    public function getHeading(): string
    {
        return 'Add New User';
    }

    public function getSubheading(): ?string
    {
        return 'Create a new user account with specific roles and permissions.';
    }

    protected function afterCreate(): void
    {
        /** @var User $record */
        $record = $this->record;

        // Ensure primary role (account_type) is included in assigned roles
        $primary = $record->account_type ?: 'student';
        $roles = $record->roles()->pluck('name')->all();
        if (!in_array($primary, $roles, true)) {
            $roles[] = $primary;
        }
        $record->syncRoles(array_values(array_unique($roles)));

        Notification::make()
            ->success()
            ->title('User created successfully!')
            ->body("User '{$record->name}' has been created with the role: {$primary}.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
