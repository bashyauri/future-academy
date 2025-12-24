<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use App\Enums\QuizType;
use App\Models\ExamType;
use App\Models\Lesson;
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
                    ->description('Core details about this quiz')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextInput::make('title')
                            ->label('Quiz Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., JAMB Mathematics Practice Test')
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Brief description of this quiz...')
                            ->columnSpanFull(),

                        Select::make('lesson_id')
                            ->label('Attach to Lesson')
                            ->options(fn() => Lesson::with('subject')
                                ->orderBy('title')
                                ->get()
                                ->mapWithKeys(fn($lesson) => [
                                    $lesson->id => $lesson->title . ($lesson->subject?->name ? ' · ' . $lesson->subject->name : ''),
                                ]))
                            ->searchable()
                            ->preload()
                            ->default(fn() => request()->integer('lesson_id'))
                            ->placeholder('None (standalone quiz)')
                            ->helperText('Optional: Link to a lesson. This quiz will appear on that lesson page.')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Select::make('type')
                                    ->label('Quiz Type')
                                    ->options(QuizType::options())
                                    ->required()
                                    ->default(QuizType::Practice->value)
                                    ->rules([
                                        'in:' . implode(',', QuizType::values()),
                                    ])
                                    ->helperText('Mock exams auto-configure: English=70Q, Others=50Q, 100min total')
                                    ->reactive()
                                    ->columnSpan(fn($get) => $get('type') === QuizType::Mock->value ? 2 : 1),

                                TextInput::make('question_count')
                                    ->label('Number of Questions')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(20)
                                    ->placeholder('20')
                                    ->visible(fn($get) => $get('type') !== QuizType::Mock->value),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('passing_score')
                                    ->label('Passing Score')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(50)
                                    ->suffix('%')
                                    ->helperText('Minimum percentage to pass')
                                    ->columnSpan(1),

                                TextInput::make('duration_minutes')
                                    ->label('Duration (Minutes)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('60')
                                    ->suffix('min')
                                    ->helperText('Time limit for this quiz')
                                    ->required(fn($get) => $get('type') === QuizType::Timed->value)
                                    ->visible(fn($get) => $get('type') === QuizType::Timed->value)
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Question Selection Criteria')
                    ->description('Choose which questions to include automatically')
                    ->icon('heroicon-o-funnel')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('subject_ids')
                                    ->label('Subjects')
                                    ->multiple()
                                    ->options(Subject::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('All subjects')
                                    ->helperText('Filter by specific subjects'),

                                Select::make('topic_ids')
                                    ->label('Topics')
                                    ->multiple()
                                    ->options(Topic::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('All topics')
                                    ->helperText('Filter by specific topics'),

                                Select::make('exam_type_ids')
                                    ->label('Exam Types')
                                    ->multiple()
                                    ->options(ExamType::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('All exam types')
                                    ->helperText('Filter by exam type (WAEC, JAMB, etc.)'),

                                Select::make('difficulty_levels')
                                    ->label('Difficulty Levels')
                                    ->multiple()
                                    ->options([
                                        'easy' => 'Easy',
                                        'medium' => 'Medium',
                                        'hard' => 'Hard',
                                    ])
                                    ->placeholder('All levels')
                                    ->helperText('Filter by difficulty'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Manual Question Assignment')
                    ->description('Override automatic selection by choosing specific questions')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Repeater::make('questions')
                            ->relationship('questions')
                            ->schema([
                                Select::make('filter_subject_id')
                                    ->label('Filter by Subject')
                                    ->options(Subject::pluck('name', 'id'))
                                    ->searchable()
                                    ->live()
                                    ->placeholder('All subjects')
                                    ->afterStateUpdated(fn($state, $set) => $set('question_id', null))
                                    ->helperText('Optional: narrow down search')
                                    ->columnSpanFull(),

                                Select::make('filter_difficulty')
                                    ->label('Filter by Difficulty')
                                    ->options([
                                        'easy' => 'Easy',
                                        'medium' => 'Medium',
                                        'hard' => 'Hard',
                                    ])
                                    ->live()
                                    ->placeholder('All difficulties')
                                    ->afterStateUpdated(fn($state, $set) => $set('question_id', null))
                                    ->helperText('Optional: narrow down search')
                                    ->columnSpanFull(),

                                Select::make('filter_exam_type_id')
                                    ->label('Filter by Exam Type')
                                    ->options(ExamType::pluck('name', 'id'))
                                    ->searchable()
                                    ->live()
                                    ->placeholder('All exam types')
                                    ->afterStateUpdated(fn($state, $set) => $set('question_id', null))
                                    ->helperText('Optional: narrow down search')
                                    ->columnSpanFull(),

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
                    ->description('Control quiz behavior and student experience')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('randomize_questions')
                                    ->label('Randomize Question Selection')
                                    ->helperText('Select random questions from criteria')
                                    ->default(true)
                                    ->inline(false)
                                    ->visible(fn($get) => $get('type') !== QuizType::Mock->value),

                                Toggle::make('shuffle_questions')
                                    ->label('Shuffle Question Order')
                                    ->helperText('Display questions in random order')
                                    ->default(true)
                                    ->inline(false)
                                    ->visible(fn($get) => $get('type') !== QuizType::Mock->value),

                                Toggle::make('shuffle_options')
                                    ->label('Shuffle Answer Options')
                                    ->helperText('Randomize A, B, C, D order')
                                    ->default(true)
                                    ->inline(false)
                                    ->visible(fn($get) => $get('type') !== QuizType::Mock->value),
                            ])
                            ->visible(fn($get) => $get('type') !== QuizType::Mock->value),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('show_answers_after_submit')
                                    ->label('Show Answers After Submit')
                                    ->helperText('Display correct answers immediately')
                                    ->default(true)
                                    ->inline(false)
                                    ->visible(fn($get) => $get('type') !== QuizType::Mock->value),

                                Toggle::make('allow_review')
                                    ->label('Allow Review After Completion')
                                    ->helperText('Let students review their answers')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('show_explanations')
                                    ->label('Show Explanations')
                                    ->helperText('Display answer explanations')
                                    ->default(true)
                                    ->inline(false)
                                    ->visible(fn($get) => $get('type') !== QuizType::Mock->value),
                            ])
                            ->visible(fn($get) => $get('type') !== QuizType::Mock->value),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('max_attempts')
                                    ->label('Maximum Attempts')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('Unlimited')
                                    ->helperText('Leave empty for unlimited attempts'),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->helperText('Make quiz available to students')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Availability Schedule')
                    ->description('Set when this quiz is available to students')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('available_from')
                                    ->label('Available From')
                                    ->placeholder('Select start date/time')
                                    ->helperText('Quiz becomes available at this date/time'),

                                DateTimePicker::make('available_until')
                                    ->label('Available Until')
                                    ->placeholder('Select end date/time')
                                    ->helperText('Quiz expires at this date/time'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
