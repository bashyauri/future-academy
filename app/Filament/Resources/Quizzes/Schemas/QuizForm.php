<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
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
                    ->columns(2)
                    ->collapsible(),

                Section::make('Manual Question Assignment (Optional)')
                    ->description('Manually select specific questions for this quiz. If questions are assigned here, the criteria above will be ignored.')
                    ->schema([
                        Repeater::make('questions')
                            ->relationship('questions')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('filter_subject_id')
                                            ->label('Filter by Subject')
                                            ->options(Subject::pluck('name', 'id'))
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(fn($state, $set) => $set('question_id', null))
                                            ->helperText('Optional: narrow down search'),

                                        Select::make('filter_difficulty')
                                            ->label('Filter by Difficulty')
                                            ->options([
                                                'easy' => 'Easy',
                                                'medium' => 'Medium',
                                                'hard' => 'Hard',
                                            ])
                                            ->live()
                                            ->afterStateUpdated(fn($state, $set) => $set('question_id', null))
                                            ->helperText('Optional: narrow down search'),

                                        Select::make('filter_exam_type_id')
                                            ->label('Filter by Exam Type')
                                            ->options(ExamType::pluck('name', 'id'))
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(fn($state, $set) => $set('question_id', null))
                                            ->helperText('Optional: narrow down search'),
                                    ]),

                                Select::make('question_id')
                                    ->label('Question')
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search, $get) {
                                        $query = Question::approved()
                                            ->active()
                                            ->where(function ($q) use ($search) {
                                                $q->where('id', 'like', "%{$search}%")
                                                    ->orWhere('question_text', 'like', "%{$search}%");
                                            });

                                        // Apply filters if set
                                        if ($subjectId = $get('filter_subject_id')) {
                                            $query->where('subject_id', $subjectId);
                                        }
                                        if ($difficulty = $get('filter_difficulty')) {
                                            $query->where('difficulty', $difficulty);
                                        }
                                        if ($examTypeId = $get('filter_exam_type_id')) {
                                            $query->where('exam_type_id', $examTypeId);
                                        }

                                        return $query
                                            ->with(['subject', 'examType'])
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn($question) => [
                                                $question->id => "#{$question->id} - " .
                                                    \Str::limit($question->question_text, 80) .
                                                    " ({$question->subject?->name} - {$question->difficulty})"
                                            ]);
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        $question = Question::with(['subject', 'examType'])->find($value);
                                        if (!$question) return "Question #{$value}";

                                        return "#{$question->id} - " .
                                            \Str::limit($question->question_text, 80) .
                                            " ({$question->subject?->name} - {$question->difficulty})";
                                    })
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->helperText('Type question ID or search by text')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->reorderable('order')
                            ->orderColumn('order')
                            ->defaultItems(0)
                            ->addActionLabel('Add Question')
                            ->helperText('Drag to reorder questions. Leave empty to use automatic selection based on criteria above.')
                            ->collapsible()
                            ->itemLabel(
                                fn(array $state): ?string =>
                                $state['question_id']
                                    ? Question::find($state['question_id'])?->question_text
                                    ? '#' . $state['question_id'] . ' - ' . \Str::limit(Question::find($state['question_id'])->question_text, 50)
                                    : "Question #{$state['question_id']}"
                                    : 'New Question'
                            ),
                    ])
                    ->collapsible()
                    ->collapsed(),

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
