<?php

namespace App\Filament\Resources\LessonResource\Schemas;

use App\Models\Topic;
use App\Services\BunnyStreamService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Lesson Details')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Select::make('subject_id')
                        ->label('Subject')
                        ->relationship('subject', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn(Set $set) => $set('topic_id', null)),

                    Select::make('topic_id')
                        ->label('Topic')
                        ->options(
                            fn(Get $get): array =>
                            Topic::where('subject_id', $get('subject_id'))
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->disabled(fn(Get $get): bool => !$get('subject_id')),

                    TextInput::make('order')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->helperText('Order in which lessons appear'),

                    TextInput::make('duration_minutes')
                        ->label('Duration (minutes)')
                        ->numeric()
                        ->suffix('min'),
                ])->columns(2),

            Section::make('Video Content')
                ->schema([
                    Select::make('video_type')
                        ->label('Video Type')
                        ->options([
                            'youtube' => 'YouTube',
                            'vimeo' => 'Vimeo',
                            'bunny' => 'Bunny Stream',
                            'local' => 'Legacy (Cloudinary)',
                        ])
                        ->default('bunny')
                        ->required()
                        ->live(),

                    TextInput::make('video_url')
                        ->label('Video URL')
                        ->url()
                        ->maxLength(255)
                        ->visible(fn(Get $get) => in_array($get('video_type'), ['youtube', 'vimeo']))
                        ->helperText(fn(Get $get) => match ($get('video_type')) {
                            'youtube' => 'Paste YouTube video URL (e.g., https://www.youtube.com/watch?v=...)',
                            'vimeo' => 'Paste Vimeo video URL (e.g., https://vimeo.com/...)',
                            default => 'Provide video URL',
                        }),

                    FileUpload::make('video_url')
                        ->label('Upload Video')
                        ->acceptedFileTypes(['video/mp4', 'video/quicktime'])
                        ->maxSize(512000)
                        ->visible(fn(Get $get) => $get('video_type') === 'bunny')
                        ->disabled(fn(Get $get): bool => !$get('subject_id'))
                        ->previewable(true)
                        ->downloadable(false)
                        ->deletable(true)
                        ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, Set $set, Get $get): string {
                            try {
                                \Log::info('Bunny upload starting');

                                $service = app(BunnyStreamService::class);
                                $title = $get('title') ?: $file->getClientOriginalName();

                                $video = $service->createVideo($title);

                                $videoId = $video['guid'] ?? $video['videoId'] ?? $video['id'] ?? null;

                                if (!$videoId) {
                                    throw new \RuntimeException('Bunny did not return video ID');
                                }

                                $service->uploadVideo($videoId, $file);

                                $set('video_status', 'processing');

                                Notification::make()
                                    ->success()
                                    ->title('Upload Started')
                                    ->body('Video uploaded to Bunny. Processing: 5-30 min')
                                    ->send();

                                return (string) $videoId;
                            } catch (\Exception $e) {
                                \Log::error('Bunny upload failed: ' . $e->getMessage());

                                Notification::make()
                                    ->danger()
                                    ->title('Upload Failed')
                                    ->body('Error: ' . $e->getMessage())
                                    ->send();

                                throw $e;
                            }
                        })
                        ->deleteUploadedFileUsing(function (string $file): void {
                            try {
                                $service = app(BunnyStreamService::class);
                                $service->deleteVideo($file);

                                \Log::info('Video deleted from Bunny', ['videoId' => $file]);

                                Notification::make()
                                    ->success()
                                    ->title('Video Deleted')
                                    ->body('Video removed from Bunny Stream')
                                    ->send();
                            } catch (\Exception $e) {
                                \Log::error('Bunny delete failed: ' . $e->getMessage());

                                Notification::make()
                                    ->warning()
                                    ->title('Delete Warning')
                                    ->body('Video might not be fully deleted from Bunny')
                                    ->send();
                            }
                        })
                        ->helperText(fn(Get $get): string =>
                            !$get('subject_id')
                                ? 'Please select a subject first to organize videos properly.'
                                : 'Uploads to Bunny Stream. Max size: 500MB.'
                        ),

                    FileUpload::make('thumbnail')
                        ->image()
                        ->directory('lessons/thumbnails')
                        ->maxSize(2048)
                        ->imageEditor()
                        ->helperText('Recommended size: 1280x720px'),
                ])->columns(2),

            Section::make('Lesson Content')
                ->schema([
                    RichEditor::make('content')
                        ->label('Lesson Notes & Materials')
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'link',
                            'h2',
                            'h3',
                            'bulletList',
                            'orderedList',
                            'blockquote',
                            'codeBlock',
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make('Settings')
                ->schema([
                    Toggle::make('is_free')
                        ->label('Free Lesson')
                        ->helperText('Available to all users without subscription')
                        ->default(false),

                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'published' => 'Published',
                            'archived' => 'Archived',
                        ])
                        ->default('draft')
                        ->required()
                        ->live(),

                    DateTimePicker::make('published_at')
                        ->label('Publish Date')
                        ->visible(fn(Get $get) => $get('status') === 'published')
                        ->default(now()),

                    Hidden::make('video_status')
                        ->default('processing'),

                    Hidden::make('created_by')
                        ->default(auth()->id()),
                ])->columns(2),
        ]);
    }

    /**
     * Generate the video directory path based on subject and topic.
     * Cloudinary will auto-create folders when a file is uploaded with "/" in the path.
     */
    private static function getVideoDirectory(Get $get): string
    {
        $subjectId = $get('subject_id');
        $topicId = $get('topic_id');

        if (!$subjectId) {
            return 'future-academy/lessons';
        }

        $subject = \App\Models\Subject::find($subjectId);
        $topic = $topicId ? \App\Models\Topic::find($topicId) : null;

        $path = 'future-academy/lessons/' . ($subject ? \Illuminate\Support\Str::slug($subject->name) : 'uncategorized');

        if ($topic) {
            $path .= '/' . \Illuminate\Support\Str::slug($topic->name);
        }

        return $path;
    }
}
