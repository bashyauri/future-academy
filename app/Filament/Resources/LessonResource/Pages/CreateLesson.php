<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use App\Services\BunnyStreamService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;

    public function mount(): void
    {
        Log::info('CreateLesson: Page mounted - form loaded');
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function create(bool $another = false): void
    {
        Log::info('CreateLesson: create() method called', ['another' => $another]);
        parent::create($another);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        Log::info('CreateLesson: mutateFormDataBeforeSave called', [
            'video_type' => $data['video_type'] ?? 'not set',
            'video_url' => $data['video_url'] ?? 'not set',
            'subject_id' => $data['subject_id'] ?? 'not set',
            'title' => $data['title'] ?? 'not set',
        ]);

        // Video URL is now set by the chunked uploader, no need to process bunny_video_file

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::info('CreateLesson: afterCreate() called', [
            'record_id' => $this->record->id ?? 'no id',
            'video_type' => $this->record->video_type ?? 'not set',
            'video_url' => $this->record->video_url ?? 'not set',
        ]);

        $record = $this->record;

        // Set video status to ready if video_url is filled
        if ($record->video_type === 'bunny' && $record->video_url) {
            Log::info('CreateLesson: afterCreate - updating video status to ready');
            $record->update([
                'video_status' => 'ready',
                'video_processed_at' => now(),
            ]);

            Notification::make()
                ->success()
                ->title('Video Ready')
                ->body('Your video has been uploaded successfully and is ready to use.')
                ->send();
        } else {
            Log::info('CreateLesson: afterCreate - skipping status update', [
                'video_type' => $record->video_type,
                'has_video_url' => !empty($record->video_url),
            ]);
        }
    }
}
