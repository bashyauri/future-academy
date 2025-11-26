<?php

namespace App\Filament\Resources\TopicResource\Pages;

use App\Filament\Resources\TopicResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTopic extends EditRecord
{
    protected static string $resource = TopicResource::class;

    public function getTitle(): string
    {
        return 'Edit Topic';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn() => auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false),
        ];
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title('Topic updated!')
            ->body("Changes to '{$this->record->name}' have been saved.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
