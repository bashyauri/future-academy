<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\ExamType;
use App\Models\Subject;
use App\Models\Topic;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Question Content')
                    ->description('Enter the question text and upload images if needed')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Textarea::make('question_text')
                            ->label('Question')
                            ->required()
                            ->rows(4)
                            ->placeholder('Enter the question here...')
                            ->columnSpanFull(),

                        FileUpload::make('question_image')
                            ->label('Question Image (Optional)')
                            ->image()
                            ->imageEditor()
                            ->directory('questions/images')
                            ->maxSize(2048)
                            ->helperText('Upload diagrams, charts, or images related to the question')
                            ->columnSpanFull(),

                        Textarea::make('explanation')
                            ->label('Answer Explanation (Optional)')
                            ->rows(3)
                            ->placeholder('Explain why the answer is correct and why other options are wrong...')
                            ->helperText('Provide detailed explanation to help students learn')
                            ->columnSpanFull(),

                        FileUpload::make('explanation_image')
                            ->label('Explanation Image (Optional)')
                            ->image()
                            ->imageEditor()
                            ->directory('questions/explanations')
                            ->maxSize(2048)
                            ->helperText('Upload images to support your explanation')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Answer Options')
                    ->description('Add 2-6 multiple choice options. Mark one as correct.')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Repeater::make('options')
                            ->relationship('options')
                            ->schema([
                                Grid::make()
                                    ->schema([
                                        TextInput::make('label')
                                            ->label('Label')
                                            ->required()
                                            ->maxLength(2)
                                            ->placeholder('A')
                                            ->helperText('A, B, C, D, E, or F')
                                            ->columnSpan(1),

                                        Textarea::make('option_text')
                                            ->label('Option Text')
                                            ->required()
                                            ->rows(2)
                                            ->placeholder('Enter the option text...')
                                            ->columnSpan(9),

                                        Toggle::make('is_correct')
                                            ->label('✓ Correct Answer')
                                            ->inline(false)
                                            ->helperText('Mark as correct')
                                            ->columnSpan(2),
                                    ])
                                    ->columns(12),

                                FileUpload::make('option_image')
                                    ->label('Option Image (Optional)')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('questions/options')
                                    ->maxSize(1024)
                                    ->helperText('Add diagram or illustration for this option'),
                            ])
                            ->defaultItems(4)
                            ->minItems(2)
                            ->maxItems(6)
                            ->orderColumn('sort_order')
                            ->reorderable()
                            ->collapsible()
                            ->collapsed(false)
                            ->itemLabel(
                                fn(array $state): ?string => ($state['label'] ?? '?') . '. ' .
                                    (strlen($state['option_text'] ?? '') > 40
                                        ? substr($state['option_text'], 0, 40) . '...'
                                        : ($state['option_text'] ?? 'Empty option'))
                            )
                            ->addActionLabel('+ Add Another Option')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),
                Section::make('Classification')
                    ->description('Tag and categorize this question')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Select::make('exam_type_id')
                                    ->label('Exam Type')
                                    ->required()
                                    ->options(ExamType::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn($state, callable $set) => $set('subject_id', null))
                                    ->prefixIcon('heroicon-o-academic-cap')
                                    ->columnSpan(2),

                                Select::make('subject_id')
                                    ->label('Subject')
                                    ->required()
                                    ->options(function (callable $get) {
                                        $examTypeId = $get('exam_type_id');
                                        if (!$examTypeId) {
                                            return Subject::where('is_active', true)->pluck('name', 'id');
                                        }
                                        return Subject::whereHas('examTypes', function ($query) use ($examTypeId) {
                                            $query->where('exam_types.id', $examTypeId);
                                        })->where('is_active', true)->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn($state, callable $set) => $set('topic_id', null))
                                    ->prefixIcon('heroicon-o-book-open')
                                    ->columnSpan(2),

                                Select::make('topic_id')
                                    ->label('Topic')
                                    ->options(function (callable $get) {
                                        $subjectId = $get('subject_id');
                                        if (!$subjectId) {
                                            return [];
                                        }
                                        return Topic::where('subject_id', $subjectId)
                                            ->where('is_active', true)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-o-folder')
                                    ->helperText('Optional: Select specific topic within the subject')
                                    ->columnSpan(2),
                            ])
                            ->columns(6),

                        Grid::make()
                            ->schema([
                                Toggle::make('is_mock')
                                    ->label('Mock Question')
                                    ->helperText('Mark this question as a mock exam question. Mock questions will not appear in regular practice or quizzes.')
                                    ->default(false)
                                    ->inline(false)
                                    ->columnSpan(1),
                                Select::make('difficulty')
                                    ->label('Difficulty Level')
                                    ->required()
                                    ->options([
                                        'easy' => 'Easy',
                                        'medium' => 'Medium',
                                        'hard' => 'Hard',
                                    ])
                                    ->default('medium')
                                    ->prefixIcon('heroicon-o-chart-bar')
                                    ->columnSpan(1),

                                TextInput::make('year')
                                    ->label('Year')
                                    ->numeric()
                                    ->minValue(2000)
                                    ->maxValue(date('Y'))
                                    ->placeholder('2024')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->helperText('Past question year (optional)')
                                    ->columnSpan(1),

                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Pending Review',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('pending')
                                    ->prefixIcon('heroicon-o-shield-check')
                                    ->visible(fn() => \Filament\Facades\Filament::auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false)
                                    ->columnSpan(1),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false)
                                    ->visible(fn() => \Filament\Facades\Filament::auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false)
                                    ->columnSpan(1),
                            ])
                            ->columns(4),

                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->rows(2)
                            ->placeholder('Explain why this question was rejected...')
                            ->visible(fn(callable $get) => $get('status') === 'rejected')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

            ]);
    }
}
