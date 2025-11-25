<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Edit User';
    }

    public function getHeading(): string
    {
        return "Edit {$this->record->name}";
    }

    public function getSubheading(): ?string
    {
        return "Update user information, roles, and permissions.";
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(function (): bool {
                    $user = auth()->user();
                    $record = $this->record;
                    if (!$user?->hasAnyRole(['super-admin', 'admin'])) {
                        return false;
                    }
                    if ($record->id === $user->id) {
                        return false;
                    }
                    if ($record->hasAnyRole(['super-admin', 'admin'])) {
                        return $user->hasRole('super-admin');
                    }
                    return true;
                }),
        ];
    }

    protected function afterSave(): void
    {
        /** @var User $record */
        $record = $this->record;

        $primary = $record->account_type ?: 'student';
        $roles = $record->roles()->pluck('name')->all();
        if (!in_array($primary, $roles, true)) {
            $roles[] = $primary;
        }
        $record->syncRoles(array_values(array_unique($roles)));

        Notification::make()
            ->success()
            ->title('User updated successfully!')
            ->body("Changes to '{$record->name}' have been saved.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
