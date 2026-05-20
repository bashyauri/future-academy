<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

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
        return 'Update user information, roles, and permissions.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('impersonate')
                ->label('Impersonate User')
                ->icon('heroicon-o-user')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Impersonate this user?')
                ->modalDescription('You will enter support mode as this user until you stop impersonation.')
                ->modalSubmitActionLabel('Start Impersonation')
                ->visible(function (): bool {
                    $admin = auth()->user();
                    $record = $this->record;

                    return (bool) ($admin
                        && $admin->hasAnyRole(['admin', 'super-admin'])
                        && ! session('impersonated_user_id')
                        && $record->id !== $admin->id);
                })
                ->action(function () {
                    $admin = auth()->user();
                    /** @var User $record */
                    $record = $this->record;

                    if (! $admin || ! $admin->hasAnyRole(['admin', 'super-admin']) || $record->id === $admin->id) {
                        Notification::make()
                            ->title('Unable to impersonate user')
                            ->danger()
                            ->send();

                        return;
                    }

                    session([
                        'impersonator_id' => $admin->id,
                        'impersonated_user_id' => $record->id,
                        'impersonated_user_email' => $record->email,
                        'impersonated_user_name' => $record->name,
                    ]);

                    $this->redirect(route('dashboard'));
                }),

            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(function (): bool {
                    $user = auth()->user();
                    $record = $this->record;
                    if (! $user?->hasAnyRole(['super-admin', 'admin'])) {
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
        if (! in_array($primary, $roles, true)) {
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
