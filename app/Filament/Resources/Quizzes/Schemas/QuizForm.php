<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use App\Models\ExamType;
use App\Models\Subject;
use App\Models\Topic;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                Select::make('type')
                                    ->options([
                                        'practice' => 'Practice Test',
                                        'timed' => 'Timed Exam',
                                        'mock' => 'Mock Exam (Past Questions)',
                                    ])
                                    ->required()
                                    ->default('practice')
                                    ->reactive(),

                                TextInput::make('question_count')
                                    ->label('Number of Questions')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(20),

                                TextInput::make('passing_score')
                                    ->label('Passing Score (%)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(50)
                                    ->suffix('%'),
                            ]),

                        TextInput::make('duration_minutes')
                            ->label('Duration (Minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty for untimed practice tests')
                            ->visible(fn($get) => $get('type') === 'timed'),
                    ])
                    ->columns(2),

                Section::make('Question Selection Criteria')
                    ->description('Define which questions should be included in this quiz')
                    ->schema([
                        Select::make('subject_ids')
                            ->label('Subjects')
                            ->multiple()
                            ->options(Subject::pluck('name', 'id'))
                            ->searchable()
                            ->preload(),

                        Select::make('topic_ids')
                            ->label('Topics')
                            ->multiple()
                            ->options(Topic::pluck('name', 'id'))
                            ->searchable()
                            ->preload(),

                        Select::make('exam_type_ids')
                            ->label('Exam Types')
                            ->multiple()
                            ->options(ExamType::pluck('name', 'id'))
                            ->searchable()
                            ->preload(),

                        Select::make('difficulty_levels')
                            ->label('Difficulty Levels')
                            ->multiple()
                            ->options([
                                'easy' => 'Easy',
                                'medium' => 'Medium',
                                'hard' => 'Hard',
                            ]),

                        Select::make('years')
                            ->label('Years (for Past Questions)')
                            ->multiple()
                            ->options(array_combine(
                                range(date('Y'), date('Y') - 20),
                                range(date('Y'), date('Y') - 20)
                            ))
                            ->visible(fn($get) => $get('type') === 'mock'),
                    ])
                    ->columns(2),

                Section::make('Quiz Settings')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('randomize_questions')
                                    ->label('Randomize Question Selection')
                                    ->helperText('Select random questions matching criteria')
                                    ->default(true),

                                Toggle::make('shuffle_questions')
                                    ->label('Shuffle Question Order')
                                    ->helperText('Show questions in random order')
                                    ->default(true),

                                Toggle::make('shuffle_options')
                                    ->label('Shuffle Answer Options')
                                    ->helperText('Randomize A, B, C, D order')
                                    ->default(true),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('show_answers_after_submit')
                                    ->label('Show Answers After Submit')
                                    ->default(true),

                                Toggle::make('allow_review')
                                    ->label('Allow Review After Completion')
                                    ->default(true),

                                Toggle::make('show_explanations')
                                    ->label('Show Explanations')
                                    ->default(true),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('max_attempts')
                                    ->label('Maximum Attempts')
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText('Leave empty for unlimited attempts'),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ]),
                    ]),

                Section::make('Availability Schedule')
                    ->schema([
                        DateTimePicker::make('available_from')
                            ->label('Available From')
                            ->helperText('Leave empty to make available immediately'),

                        DateTimePicker::make('available_until')
                            ->label('Available Until')
                            ->helperText('Leave empty for no expiration'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
