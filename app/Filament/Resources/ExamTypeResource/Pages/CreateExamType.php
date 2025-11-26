<?php

namespace App\Filament\Resources\ExamTypeResource\Pages;

use App\Filament\Resources\ExamTypeResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateExamType extends CreateRecord
{
    protected static string $resource = ExamTypeResource::class;

    public function getTitle(): string
    {
        return 'Create Exam Type';
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Exam type created!')
            ->body("'{$this->record->name}' has been created successfully.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
