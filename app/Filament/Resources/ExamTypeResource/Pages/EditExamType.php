<?php

namespace App\Filament\Resources\ExamTypeResource\Pages;

use App\Filament\Resources\ExamTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExamType extends EditRecord
{
    protected static string $resource = ExamTypeResource::class;

    public function getTitle(): string
    {
        return 'Edit Exam Type';
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
            ->title('Exam type updated!')
            ->body("Changes to '{$this->record->name}' have been saved.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
