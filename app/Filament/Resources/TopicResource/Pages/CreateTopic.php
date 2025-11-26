<?php

namespace App\Filament\Resources\TopicResource\Pages;

use App\Filament\Resources\TopicResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTopic extends CreateRecord
{
    protected static string $resource = TopicResource::class;

    public function getTitle(): string
    {
        return 'Create Topic';
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Topic created!')
            ->body("'{$this->record->name}' has been created successfully.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
