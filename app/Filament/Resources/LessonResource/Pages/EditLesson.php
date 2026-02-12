<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use App\Services\BunnyStreamService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        Log::info('EditLesson: mutateFormDataBeforeSave called', [
            'video_type' => $data['video_type'] ?? 'not set',
            'video_url' => $data['video_url'] ?? 'not set',
        ]);

        // Video URL is now set by the chunked uploader, no need to process bunny_video_file

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        // Set video status to ready if video_url is filled
        if ($record->video_type === 'bunny' && $record->video_url) {
            $record->update([
                'video_status' => 'ready',
                'video_processed_at' => now(),
            ]);

            Notification::make()
                ->success()
                ->title('Video Ready')
                ->icon('heroicon-o-check-circle')
                ->body('Your video has been uploaded successfully and is ready to use.')
                ->send();
        }
    }
}

