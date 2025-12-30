<?php

namespace App\Filament\Resources\LessonResource\RelationManagers;

use App\Models\Question;
use Filament\Actions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Lesson Questions';

    protected static ?string $recordTitleAttribute = 'question_text';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable()
                    ->width(60),

                TextColumn::make('question_text')
                    ->label('Question')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn($record) => $record->question_text),

                TextColumn::make('subject.name')
                    ->badge()
                    ->searchable(),

                TextColumn::make('topic.name')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('difficulty')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'easy' => 'success',
                        'medium' => 'warning',
                        'hard' => 'danger',
                        default => 'gray',
                    }),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Create Question')
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading('Create Question for this Lesson')
                    ->modalWidth('5xl')
                    ->form([
                        Section::make('Question Details')
                            ->schema([
                                Textarea::make('question_text')
                                    ->required()
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->label('Question')
                                    ->columnSpanFull(),

                                Select::make('subject_id')
                                    ->label('Subject')
                                    ->relationship('subject', 'name')
                                    ->default(fn() => $this->getOwnerRecord()->subject_id)
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Select::make('topic_id')
                                    ->label('Topic')
                                    ->relationship('topic', 'name')
                                    ->default(fn() => $this->getOwnerRecord()->topic_id)
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Select::make('exam_type_id')
                                    ->label('Exam Type')
                                    ->relationship('examType', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Select::make('difficulty')
                                    ->options([
                                        'easy' => 'Easy',
                                        'medium' => 'Medium',
                                        'hard' => 'Hard',
                                    ])
                                    ->default('medium')
                                    ->required()
                                    ->columnSpan(1),

                                Textarea::make('explanation')
                                    ->label('Explanation (Optional)')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->helperText('Explain the correct answer')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('Answer Options')
                            ->description('Add at least 2 options. Mark the correct answer(s).')
                            ->schema([
                                Repeater::make('options')
                                    ->relationship()
                                    ->schema([
                                        TextInput::make('option_text')
                                            ->required()
                                            ->maxLength(500)
                                            ->label('Option Text')
                                            ->columnSpan(2),

                                        Checkbox::make('is_correct')
                                            ->label('Correct Answer')
                                            ->default(false)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(4)
                                    ->minItems(2)
                                    ->maxItems(6)
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['option_text'] ?? 'New Option')
                                    ->addActionLabel('Add Option')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Lesson Settings')
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending Review',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('approved')
                                    ->required()
                                    ->helperText('Questions created from lessons are auto-approved')
                                    ->columnSpan(1),

                                TextInput::make('order')
                                    ->numeric()
                                    ->default(fn() => $this->getOwnerRecord()->questions()->max('order') + 1)
                                    ->required()
                                    ->label('Order in Lesson')
                                    ->helperText('Position of this question in the lesson')
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['is_active'] = true;
                        $data['created_by'] = \Illuminate\Support\Facades\Auth::id();
                        return $data;
                    })
                    ->after(function ($record) {
                        // Attach the newly created question to this lesson with the order, only if not already attached
                        $order = request()->input('data.order') ?? $this->getOwnerRecord()->questions()->max('order') + 1;
                        $lesson = $this->getOwnerRecord();
                        if (!$lesson->questions()->where('questions.id', $record->id)->exists()) {
                            $lesson->questions()->attach($record->id, ['order' => $order]);
                        }
                    }),

                Actions\AttachAction::make()
                    ->label('Attach Existing Question')
                    ->icon('heroicon-o-link')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(
                        fn(Builder $query) => $query
                            ->where('status', 'approved')
                            ->where('is_active', true)
                            // Filter by same subject as the lesson
                            ->where('subject_id', $this->getOwnerRecord()->subject_id)
                            // Optionally filter by same topic (comment out if too restrictive)
                            ->where(function ($q) {
                                $q->where('topic_id', $this->getOwnerRecord()->topic_id)
                                    ->orWhereNull('topic_id');
                            })
                    )
                    ->form(fn(Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('order')
                            ->numeric()
                            ->default(fn() => $this->getOwnerRecord()->questions()->max('order') + 1)
                            ->required()
                            ->label('Order'),
                    ])
                    ->color('gray'),
            ])
            ->recordActions([
                Actions\EditAction::make()
                    ->form([
                        TextInput::make('order')
                            ->numeric()
                            ->required()
                            ->label('Order'),
                    ]),
                Actions\DetachAction::make(),
            ])
            ->toolbarActions([
                //
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('order')
            ->reorderable('order');
    }
}
