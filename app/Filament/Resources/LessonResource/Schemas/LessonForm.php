<?php

namespace App\Filament\Resources\LessonResource\Schemas;

use App\Models\Lesson;
use App\Models\Topic;
use App\Services\BunnyStreamService;
use App\Filament\Forms\Components\BunnyUpload;
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

                    BunnyUpload::make('video_url')
                        ->visible(fn(Get $get) => $get('video_type') === 'bunny')
                        ->disabled(fn(Get $get): bool => !$get('subject_id'))
                        ->columnSpanFull(),

                    Placeholder::make('select_subject_first')
                        ->label('Video Upload')
                        ->visible(fn(Get $get) => $get('video_type') === 'bunny' && !$get('subject_id'))
                        ->content(fn() => new \Illuminate\Support\HtmlString('
                            <div class="flex items-center gap-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <svg class="h-5 w-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Select a subject first</p>
                                    <p class="text-xs text-blue-700 mt-1">Please select or create a subject to enable video upload</p>
                                </div>
                            </div>
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
                                $embedUrl = $service->getEmbedUrl($videoId);

                                return new \Illuminate\Support\HtmlString('
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-2 p-2 bg-green-50 border border-green-200 rounded">
                                            <svg class="h-4 w-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
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
                                        <p class="text-xs text-gray-500">This is how students will see the video</p>
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
