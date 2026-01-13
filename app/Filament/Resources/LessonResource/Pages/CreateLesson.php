<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Automatically set video status to ready for local videos
        $record = $this->record;

        if ($record->video_type === 'local' && $record->video_url) {
            // Bypass webhook - set to ready immediately
            $record->update([
                'video_status' => 'ready',
                'video_processed_at' => now(),
            ]);

            Notification::make()
                ->success()
                ->title('Video Ready')
                ->body('Your video has been uploaded and is ready to use.')
                ->send();
        }
    }
}
