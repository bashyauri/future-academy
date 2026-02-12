<?php

namespace App\Filament\Resources\LessonResource\Schemas;

use App\Models\Topic;
use App\Services\BunnyStreamService;
use Filament\Forms\Components\{
    DateTimePicker,  FileUpload,
    Hidden,
    RichEditor,
    Select,
    Textarea,
    TextInput,
    Toggle,

};
use Filament\Actions\Action;

use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            /*
            |--------------------------------------------------------------------------
            | Lesson Details
            |--------------------------------------------------------------------------
            */

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
                        ->live(debounce: '100ms')
                        ->afterStateUpdated(fn (Set $set) => $set('topic_id', null)),

                    Select::make('topic_id')
                        ->label('Topic')
                        ->options(fn (Get $get) =>
                            Topic::where('subject_id', $get('subject_id'))
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->disabled(fn (Get $get) => ! $get('subject_id')),

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

            /*
            |--------------------------------------------------------------------------
            | Video Content
            |--------------------------------------------------------------------------
            */

            Section::make('Video Content')
                ->schema([

                    Select::make('video_type')
                        ->label('Video Type')
                        ->options([
                            'youtube' => 'YouTube',
                            'vimeo'   => 'Vimeo',
                            'bunny'   => 'Bunny Stream',
                            'local'   => 'Legacy (Cloudinary)',
                        ])
                        ->default('bunny')
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    /*
                    |----------------------------------
                    | Video URL / ID Field
                    |----------------------------------
                    */
                    TextInput::make('video_url')
                        ->label(fn (Get $get) =>
                            in_array($get('video_type'), ['youtube', 'vimeo'])
                                ? 'Video URL'
                                : 'Video ID'
                        )
                        ->visible(fn (Get $get) =>
                            in_array($get('video_type'), ['youtube', 'vimeo', 'bunny'])
                        )
                        ->disabled(fn (Get $get) =>
                            $get('video_type') === 'bunny' && ! $get('subject_id')
                        )
                        ->required(fn (Get $get) =>
                            in_array($get('video_type'), ['youtube', 'vimeo', 'bunny'])
                        )
                        ->helperText(fn (Get $get) => match ($get('video_type')) {
                            'youtube' => 'Paste YouTube URL',
                            'vimeo'   => 'Paste Vimeo URL',
                            'bunny'   => 'Auto-filled after upload or paste Bunny ID',
                            default   => null,
                        })
                        ->suffixAction(
                            Action::make('delete_video')
                                ->icon('heroicon-o-trash')
                                ->color('danger')
                                ->visible(fn (Get $get) => filled($get('video_url')))
                                ->requiresConfirmation()
                                ->modalHeading('Delete Video')
                                ->modalDescription('This will permanently delete the video from Bunny Stream.')
                                ->action(function (Set $set, Get $get, $record) {

                                    $videoId = $get('video_url');

                                    if ($videoId && $record) {
                                        try {
                                            app(BunnyStreamService::class)->deleteVideo($videoId);

                                            Notification::make()
                                                ->success()
                                                ->title('Video Deleted')
                                                ->send();
                                        } catch (\Exception $e) {

                                            Log::error('Bunny delete failed', [
                                                'video_id' => $videoId,
                                                'error' => $e->getMessage(),
                                            ]);

                                            Notification::make()
                                                ->warning()
                                                ->title('Remote deletion may have failed.')
                                                ->send();
                                        }
                                    }

                                    $set('video_url', null);
                                    $set('video_status', 'processing');
                                })
                        )
                        ->columnSpanFull(),

                    /*
                    |----------------------------------
                    | Select Subject Info
                    |----------------------------------
                    */
                    View::make('filament.components.select-subject-first')
                        ->visible(fn (Get $get) =>
                            $get('video_type') === 'bunny'
                            && !filled($get('subject_id'))
                        )
                        ->columnSpanFull(),

                    /*
                    |----------------------------------
                    | Bunny Upload Component
                    |----------------------------------
                    */
                    View::make('filament.components.video-chunk-uploader')
                        ->visible(fn (Get $get) =>
                            $get('video_type') === 'bunny'
                            && filled($get('subject_id'))
                        )
                        ->viewData([
                            'statePath' => 'data.video_url',
                        ])
                        ->columnSpanFull(),

                    /*
                    |----------------------------------
                    | Bunny Preview
                    |----------------------------------
                    */
                    View::make('filament.components.video-preview')
                        ->visible(fn (Get $get) =>
                            $get('video_type') === 'bunny'
                            && filled($get('video_url'))
                            && ($get('video_status') ?? 'processing') === 'ready'
                        )
                        ->viewData(fn (Get $get) => [
                            'videoId' => $get('video_url'),
                        ])
                        ->columnSpanFull(),

                    /*
                    |----------------------------------
                    | Thumbnail Upload
                    |----------------------------------
                    */
                    FileUpload::make('thumbnail')
                        ->label('Lesson Thumbnail')
                        ->image()
                        ->directory('lessons/thumbnails')
                        ->maxSize(2048)
                        ->imageEditor()
                        ->previewable()
                        ->downloadable()
                        ->helperText('Recommended size: 1280x720px')
                        ->columnSpanFull(),

                ]),

            /*
            |--------------------------------------------------------------------------
            | Lesson Content
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Settings
            |--------------------------------------------------------------------------
            */

            Section::make('Settings')
                ->schema([

                    Toggle::make('is_free')
                        ->label('Free Lesson')
                        ->default(false),

                    Select::make('status')
                        ->options([
                            'draft'     => 'Draft',
                            'published' => 'Published',
                            'archived'  => 'Archived',
                        ])
                        ->default('draft')
                        ->required()
                        ->live(),

                    DateTimePicker::make('published_at')
                        ->visible(fn (Get $get) => $get('status') === 'published')
                        ->default(now()),

                    Hidden::make('video_status')
                        ->default('processing'),

                    Hidden::make('created_by')
                        ->default(fn () => Auth::id()),

                ])->columns(2),

        ]);
    }
}
