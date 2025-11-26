<?php

namespace App\Filament\Resources\SubjectResource\Pages;

use App\Filament\Resources\SubjectResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSubject extends CreateRecord
{
    protected static string $resource = SubjectResource::class;

    public function getTitle(): string
    {
        return 'Create Subject';
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Subject created!')
            ->body("'{$this->record->name}' has been created successfully.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
