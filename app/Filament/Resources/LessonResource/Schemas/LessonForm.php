<?php

namespace App\Filament\Resources\LessonResource\Schemas;

use App\Models\Lesson;
use App\Models\Topic;
use App\Services\BunnyStreamService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\View as ViewComponent;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
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

                    Placeholder::make('current_bunny_video')
                        ->label('Current Video')
                        ->visible(fn(Get $get, $record) => $get('video_type') === 'bunny' && !empty($get('video_url')) && is_string($get('video_url')))
                        ->content(function (Get $get) {
                            $videoId = $get('video_url');

                                                        // Safety check: ensure it's a string
                                                        if (!is_string($videoId)) {
                                                            return null;
                                                        }

                            $status = $get('video_status') ?? 'processing';

                            $statusBadge = match($status) {
                                'ready' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Ready</span>',
                                'processing' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Processing</span>',
                                'failed' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Failed</span>',
                                default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Unknown</span>',
                            };

                            return new \Illuminate\Support\HtmlString('
                                <div class="flex items-center gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-900">Video ID: ' . substr($videoId, 0, 16) . '...</span>
                                            ' . $statusBadge . '
                                        </div>
                                        <p class="text-xs text-gray-600 mt-1">
                                            To replace this video, upload a new one below. To delete it, use the delete button.
                                        </p>
                                    </div>
                                </div>
                            ');
                        }),

                    FileUpload::make('video_url')
                        ->label(fn(Get $get) => !empty($get('video_url')) ? 'Replace Video' : 'Upload Video')
                        ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'])
                        ->maxSize(512000)
                        ->visible(fn(Get $get) => $get('video_type') === 'bunny')
                        ->disabled(fn(Get $get): bool => !$get('subject_id'))
                        ->previewable(false)
                        ->downloadable(false)
                        ->deletable(true)
                        ->openable(false)
                        ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, Set $set, Get $get, ?Lesson $record): string {
                            try {
                                Log::info('Bunny upload starting');

                                $service = app(BunnyStreamService::class);
                                $title = $get('title') ?: $file->getClientOriginalName();

                                // Get previous video ID from the database record, not form state
                                $previousVideoId = $record && is_string($record->video_url) ? $record->video_url : null;

                                $video = $service->createVideo($title);

                                $videoId = $video['guid'] ?? $video['videoId'] ?? $video['id'] ?? null;

                                if (!$videoId) {
                                    throw new \RuntimeException('Bunny did not return video ID');
                                }

                                $service->uploadVideo($videoId, $file);

                                $set('video_status', 'processing');

                                if ($previousVideoId && $previousVideoId !== $videoId) {
                                    try {
                                        $service->deleteVideo($previousVideoId);
                                        Log::info('Previous Bunny video deleted after replacement', ['videoId' => $previousVideoId]);
                                    } catch (\Exception $deleteException) {
                                        Log::warning('Failed to delete previous Bunny video', [
                                            'videoId' => $previousVideoId,
                                            'error' => $deleteException->getMessage(),
                                        ]);
                                    }
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Upload Started')
                                    ->body('Video uploaded to Bunny. Processing: 5-30 min')
                                    ->send();

                                return (string) $videoId;
                            } catch (\Exception $e) {
                                Log::error('Bunny upload failed: ' . $e->getMessage());

                                Notification::make()
                                    ->danger()
                                    ->title('Upload Failed')
                                    ->body('Error: ' . $e->getMessage())
                                    ->send();

                                throw $e;
                            }
                        })
                        ->deleteUploadedFileUsing(function (string $file, Set $set, Get $get): void {
                            try {
                                // Get the actual Bunny video ID from form state, not the file parameter
                                $videoId = $get('video_url');

                                if (!$videoId || !is_string($videoId)) {
                                    throw new \Exception('No video ID found to delete');
                                }

                                $service = app(BunnyStreamService::class);
                                $service->deleteVideo($videoId);

                                // Only clear the video_url, keep status fields as they are
                                $set('video_url', null);

                                Log::info('Video deleted from Bunny', ['videoId' => $videoId]);

                                Notification::make()
                                    ->success()
                                    ->title('Video Deleted')
                                    ->body('Video has been removed from Bunny Stream')
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Bunny delete failed', [
                                    'error' => $e->getMessage(),
                                    'videoId' => $videoId ?? 'unknown'
                                ]);

                                Notification::make()
                                    ->danger()
                                    ->title('Delete Error')
                                    ->body('Video could not be removed. Please try again.')
                                    ->send();
                            }
                        })
                        ->helperText(function (Get $get): string {
                            if (! $get('subject_id')) {
                                return 'Please select a subject first to organize videos properly.';
                            }

                            $hasVideo = is_string($get('video_url')) && !empty($get('video_url'));

                            return $hasVideo
                                ? 'Upload to replace this video. Click the X button to delete it permanently from Bunny Stream.'
                                : 'Uploads to Bunny Stream. Max size: 500MB.';
                        }),

                    Placeholder::make('video_preview')
                        ->label('Video Preview')
                        ->visible(fn(Get $get): bool => $get('video_type') === 'bunny' && !empty($get('video_url')) && is_string($get('video_url')))
                        ->content(function (Get $get, $record) {
                            $videoId = $get('video_url');

                            // Safety check: ensure it's a string
                            if (!is_string($videoId) || empty($videoId)) {
                                return new \Illuminate\Support\HtmlString('
                                    <div class="rounded-lg border border-gray-300 p-4 bg-gray-50">
                                        <p class="text-sm text-gray-600">No video uploaded yet. Upload a video above to see preview.</p>
                                    </div>
                                ');
                            }

                            try {
                                $service = app(BunnyStreamService::class);
                                $embedUrl = $service->getEmbedUrl($videoId);
                                $status = $get('video_status') ?? 'processing';

                                $statusBadge = match($status) {
                                    'ready' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        Ready
                                    </span>',
                                    'processing' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <svg class="animate-spin mr-1.5 h-2 w-2 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processing
                                    </span>',
                                    'failed' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="mr-1.5 h-2 w-2 text-red-400" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        Failed
                                    </span>',
                                    default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Unknown</span>',
                                };

                                return new \Illuminate\Support\HtmlString('
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm font-medium text-gray-700">Status:</span>
                                            ' . $statusBadge . '
                                            <span class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded">
                                                ID: ' . substr($videoId, 0, 12) . '...
                                            </span>
                                        </div>
                                        ' . ($status === 'ready' ? '
                                        <div class="rounded-lg overflow-hidden border-2 border-gray-200 shadow-sm" style="aspect-ratio: 16/9; max-width: 700px;">
                                            <iframe
                                                src="' . htmlspecialchars($embedUrl) . '"
                                                loading="lazy"
                                                style="border: none; width: 100%; height: 100%;"
                                                allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                                                allowfullscreen>
                                            </iframe>
                                        </div>
                                        <p class="text-xs text-gray-500 italic">This is how students will see the video</p>
                                        ' : '
                                        <div class="rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center" style="max-width: 700px;">
                                            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <p class="mt-4 text-sm font-medium text-gray-900">Video is being processed</p>
                                            <p class="mt-1 text-xs text-gray-500">This usually takes 5-30 minutes depending on video length</p>
                                            <p class="mt-2 text-xs text-gray-400">Refresh the page to check status</p>
                                        </div>') . '
                                    </div>
                                ');
                            } catch (\Exception $e) {
                                Log::error('Video preview error: ' . $e->getMessage(), ['video_id' => $videoId]);
                                return new \Illuminate\Support\HtmlString('
                                    <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                                        <p class="text-sm text-red-600">Unable to load video preview. Video ID: ' . htmlspecialchars($videoId) . '</p>
                                    </div>
                                ');
                            }
                        })
                        ->helperText('Preview updates automatically when video processing completes'),

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
                        ->default(fn() => Auth::id()),
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
