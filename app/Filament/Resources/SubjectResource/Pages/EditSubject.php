<?php

namespace App\Filament\Resources\SubjectResource\Pages;

use App\Filament\Resources\SubjectResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSubject extends EditRecord
{
    protected static string $resource = SubjectResource::class;

    public function getTitle(): string
    {
        return 'Edit Subject';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn() => auth()->user()?->hasRole('super-admin') ?? false),
        ];
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title('Subject updated!')
            ->body("Changes to '{$this->record->name}' have been saved.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
