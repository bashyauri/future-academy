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
use Filament\Actions\Action;
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
                        ->live()
                        ->columnSpanFull(),

                    TextInput::make('video_url')
                        ->label('Video URL')
                        ->url()
                        ->maxLength(255)
                        ->visible(fn(Get $get) => in_array($get('video_type'), ['youtube', 'vimeo']))
                        ->helperText(fn(Get $get) => match ($get('video_type')) {
                            'youtube' => 'Paste YouTube video URL (e.g., https://www.youtube.com/watch?v=...)',
                            'vimeo' => 'Paste Vimeo video URL (e.g., https://vimeo.com/...)',
                            default => 'Provide video URL',
                        })
                        ->columnSpanFull(),

                    TextInput::make('video_url')
                        ->label('Video ID')
                        ->placeholder('Paste Bunny video URL or ID...')
                        ->helperText('Paste a Bunny video URL or ID from your dashboard')
                        ->visible(fn(Get $get) => $get('video_type') === 'bunny')
                        ->disabled(fn(Get $get): bool => !$get('subject_id'))
                        ->rules([
                            function ($attribute, $value, $fail) {
                                if (empty($value)) {
                                    return;
                                }

                                // Validate video ID exists on Bunny Stream
                                $bunnyService = app(BunnyStreamService::class);
                                $validatedId = $bunnyService->validateVideoId($value);

                                if (!$validatedId) {
                                    $fail('The video ID is invalid or does not exist on Bunny Stream.');
                                }
                            },
                        ])
                        ->suffixAction(
                            Action::make('clear_video')
                                ->label('Delete Video')
                                ->icon('heroicon-o-trash')
                                ->color('danger')
                                ->visible(fn(Get $get): bool => !empty($get('video_url')))
                                ->requiresConfirmation()
                                ->modalHeading('Delete Video')
                                ->modalDescription('This will permanently delete the video from Bunny Stream. This action cannot be undone.')
                                ->modalSubmitActionLabel('Delete')
                                ->action(function (Set $set, Get $get, $record) {
                                    $videoId = $get('video_url');

                                    // Delete from Bunny if exists
                                    if ($videoId && $record) {
                                        try {
                                            app(BunnyStreamService::class)->deleteVideo($videoId);
                                            Notification::make()
                                                ->success()
                                                ->title('Video Deleted')
                                                ->body('Video has been removed from Bunny Stream.')
                                                ->send();
                                        } catch (\Exception $e) {
                                            \Log::error('Failed to delete video from Bunny', [
                                                'video_id' => $videoId,
                                                'error' => $e->getMessage(),
                                            ]);
                                            Notification::make()
                                                ->warning()
                                                ->title('Partial Deletion')
                                                ->body('Video record cleared, but remote deletion may have failed. Check logs.')
                                                ->send();
                                        }
                                    }

                                    $set('video_url', null);
                                    $set('video_status', 'pending');
                                })
                        )
                        ->columnSpanFull(),

                    Placeholder::make('select_subject_first')
                        ->label('Video ID')
                        ->visible(fn(Get $get) => $get('video_type') === 'bunny' && !$get('subject_id'))
                        ->content(fn() => new \Illuminate\Support\HtmlString('
                            <div class="flex items-center gap-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <svg class="h-5 w-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Select a subject first</p>
                                    <p class="text-xs text-blue-700 mt-1">Please select a subject to enable video upload</p>
                                </div>
                            </div>
                        '))
                        ->columnSpanFull(),

                    Placeholder::make('bunny_video_uploader')
                        ->label('Upload Video')
                        ->visible(fn(Get $get) => $get('video_type') === 'bunny' && $get('subject_id'))
                        ->content(fn(Get $get) => new \Illuminate\Support\HtmlString('
                            <div x-data="videoChunkUploader()" class="space-y-3">
                                <!-- File Drop Zone -->
                                <div x-show="!uploading && !videoId"
                                     @drop.prevent="handleDrop($event)"
                                     @dragover.prevent="dragOver = true"
                                     @dragleave.prevent="dragOver = false"
                                     :class="dragOver ? \'border-blue-500 bg-blue-50\' : \'border-gray-300\'"
                                     class="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer hover:border-blue-400 transition"
                                     @click="$refs.fileInput.click()">
                                    <x-filament::icon icon="heroicon-o-video-camera" class="mx-auto h-12 w-12 text-gray-400" />
                                    <p class="mt-2 text-sm font-medium text-gray-900">Drop video file here or click to browse</p>
                                    <p class="mt-1 text-xs text-gray-500">MP4, MOV, AVI, WebM • Max 500MB</p>
                                    <input type="file" x-ref="fileInput" @change="handleFileSelect($event)" accept="video/*" class="hidden">
                                </div>

                                <!-- Upload Progress -->
                                <div x-show="uploading" class="space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-gray-700" x-text="fileName"></span>
                                        <span class="text-gray-500" x-text="Math.round(progress) + \'%\'"></span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-blue-600 h-2.5 rounded-full transition-all" :style="`width: ${progress}%`"></div>
                                    </div>
                                    <p class="text-xs text-gray-500" x-text="statusMessage"></p>
                                </div>

                                <!-- Upload Success -->
                                <div x-show="videoId && !uploading" class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-lg">
                                    <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-green-500" />
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-green-900">Video uploaded successfully</p>
                                        <p class="text-xs text-green-700 mt-1" x-text="\'Video ID: \' + videoId"></p>
                                    </div>
                                    <button type="button" @click="reset()" class="text-sm text-green-700 hover:text-green-900 font-medium">
                                        Upload Different Video
                                    </button>
                                </div>

                                <!-- Error Message -->
                                <div x-show="error" class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-lg">
                                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-red-500" />
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-red-900">Upload failed</p>
                                        <p class="text-xs text-red-700 mt-1" x-text="error"></p>
                                    </div>
                                    <button type="button" @click="reset()" class="text-sm text-red-700 hover:text-red-900 font-medium">
                                        Try Again
                                    </button>
                                </div>
                            </div>

                            <script>
                            function videoChunkUploader() {
                                return {
                                    file: null,
                                    fileName: \'\',
                                    uploading: false,
                                    progress: 0,
                                    videoId: \'\',
                                    error: null,
                                    dragOver: false,
                                    statusMessage: \'Preparing upload...\',
                                    CHUNK_SIZE: 5 * 1024 * 1024, // 5MB chunks

                                    handleDrop(e) {
                                        this.dragOver = false;
                                        const files = e.dataTransfer.files;
                                        if (files.length > 0) {
                                            this.processFile(files[0]);
                                        }
                                    },

                                    handleFileSelect(e) {
                                        const files = e.target.files;
                                        if (files.length > 0) {
                                            this.processFile(files[0]);
                                        }
                                    },

                                    processFile(file) {
                                        if (!file.type.startsWith(\'video/\')) {
                                            this.error = \'Please select a video file\';
                                            return;
                                        }

                                        const maxSize = 500 * 1024 * 1024; // 500MB
                                        if (file.size > maxSize) {
                                            this.error = \'File size exceeds 500MB limit\';
                                            return;
                                        }

                                        this.file = file;
                                        this.fileName = file.name;
                                        this.error = null;
                                        this.startUpload();
                                    },

                                    async startUpload() {
                                        this.uploading = true;
                                        this.progress = 0;
                                        this.statusMessage = \'Creating video on Bunny...\';

                                        try {
                                            // Get title from form
                                            const title = document.querySelector(\'[wire\\\\:model="data.title"]\')?.value || \'New Video\';

                                            // Create video on Bunny
                                            const createResponse = await fetch(\'/admin/video/create\', {
                                                method: \'POST\',
                                                headers: {
                                                    \'Content-Type\': \'application/json\',
                                                    \'X-CSRF-TOKEN\': document.querySelector(\'meta[name="csrf-token"]\').content,
                                                },
                                                body: JSON.stringify({ title })
                                            });

                                            if (!createResponse.ok) {
                                                const errorData = await createResponse.json();
                                                throw new Error(errorData.error || \'Failed to create video\');
                                            }

                                            const { video_id } = await createResponse.json();
                                            this.videoId = video_id;
                                            this.statusMessage = \'Uploading video...\';

                                            // Upload chunks
                                            await this.uploadInChunks(video_id);

                                            // Set video ID in form
                                            this.$wire.set(\'data.video_url\', video_id);
                                            this.$wire.set(\'data.video_status\', \'ready\');

                                            this.progress = 100;
                                            this.statusMessage = \'Upload complete!\';
                                        } catch (err) {
                                            this.error = err.message;
                                            this.videoId = \'\';
                                        } finally {
                                            this.uploading = false;
                                        }
                                    },

                                    async uploadInChunks(videoId) {
                                        const totalChunks = Math.ceil(this.file.size / this.CHUNK_SIZE);

                                        for (let i = 0; i < totalChunks; i++) {
                                            const start = i * this.CHUNK_SIZE;
                                            const end = Math.min(start + this.CHUNK_SIZE, this.file.size);
                                            const chunk = this.file.slice(start, end);

                                            const formData = new FormData();
                                            formData.append(\'chunk\', chunk);
                                            formData.append(\'video_id\', videoId);
                                            formData.append(\'chunk_index\', i);
                                            formData.append(\'total_chunks\', totalChunks);

                                            const response = await fetch(\'/admin/video/upload-chunk\', {
                                                method: \'POST\',
                                                headers: {
                                                    \'X-CSRF-TOKEN\': document.querySelector(\'meta[name="csrf-token"]\').content,
                                                },
                                                body: formData
                                            });

                                            if (!response.ok) {
                                                throw new Error(`Failed to upload chunk ${i + 1}`);
                                            }

                                            this.progress = ((i + 1) / totalChunks) * 100;
                                            this.statusMessage = `Uploading chunk ${i + 1} of ${totalChunks}...`;
                                        }
                                    },

                                    reset() {
                                        this.file = null;
                                        this.fileName = \'\';
                                        this.uploading = false;
                                        this.progress = 0;
                                        this.videoId = \'\';
                                        this.error = null;
                                        this.statusMessage = \'Preparing upload...\';
                                        this.$wire.set(\'data.video_url\', \'\');
                                    }
                                }
                            }
                            </script>
                        '))
                        ->columnSpanFull(),

                    Placeholder::make('video_preview')
                        ->label('Video Preview')
                        ->visible(fn(Get $get): bool => $get('video_type') === 'bunny' && !empty($get('video_url')) && is_string($get('video_url')) && ($get('video_status') ?? 'processing') === 'ready')
                        ->content(function (Get $get, $record) {
                            $videoId = $get('video_url');

                            // Safety check: ensure it's a string
                            if (!is_string($videoId) || empty($videoId)) {
                                return new \Illuminate\Support\HtmlString('');
                            }

                            try {
                                $service = app(BunnyStreamService::class);
                                // Pass 1440 minutes (24 hours) expiration for embed token
                                $embedUrl = $service->getEmbedUrl($videoId, now()->addMinutes(1440)->getTimestamp());

                                return new \Illuminate\Support\HtmlString('
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-2 p-2 bg-green-50 border border-green-200 rounded">
                                            <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4 text-green-600 flex-shrink-0" />
                                            <span class="text-sm font-medium text-green-800">Ready to watch</span>
                                        </div>
                                        <div class="rounded-lg overflow-hidden border border-gray-200 shadow-sm" style="aspect-ratio: 16/9;">
                                            <iframe
                                                src="' . htmlspecialchars($embedUrl) . '"
                                                loading="lazy"
                                                style="border: none; width: 100%; height: 100%;"
                                                allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                                                allowfullscreen>
                                            </iframe>
                                        </div>
                                        <p class="text-xs text-gray-500">This is how students will see the video. Use the clear button next to the Video ID field to remove this video.</p>
                                    </div>
                                ');
                            } catch (\Exception $e) {
                                Log::error('Video preview error: ' . $e->getMessage(), ['video_id' => $videoId]);
                                return new \Illuminate\Support\HtmlString('');
                            }
                        })
                        ->columnSpanFull(),

                    FileUpload::make('thumbnail')
                        ->label('Lesson Thumbnail')
                        ->image()

                        ->directory('lessons/thumbnails')
                        ->maxSize(2048)
                        ->imageEditor()
                        ->previewable(true)
                        ->downloadable(true)
                        ->helperText('Recommended size: 1280x720px (displayed on lesson cards)')
                        ->columnSpanFull(),
                ])->columns(1),

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
