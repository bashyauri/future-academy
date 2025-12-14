<?php

namespace App\Filament\Resources\LessonResource\RelationManagers;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class QuizzesRelationManager extends RelationManager
{
    protected static string $relationship = 'quizzes';

    protected static ?string $title = 'Lesson Quizzes';

    protected static ?string $recordTitleAttribute = 'title';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->description)
                    ->wrap(),

                TextColumn::make('type')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'practice' => 'Practice',
                        'timed' => 'Timed',
                        'mock' => 'Mock Exam',
                        default => ucfirst($state),
                    }),

                TextColumn::make('total_questions')
                    ->label('Questions')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn($state) => $state ? "{$state} min" : 'Untimed')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'gray',
                        'archived' => 'warning',
                        default => 'gray',
                    }),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'practice' => 'Practice',
                        'timed' => 'Timed',
                        'mock' => 'Mock Exam',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
            ])
            ->headerActions([
                Action::make('linkQuiz')
                    ->label('Link Existing Quiz')
                    ->icon('heroicon-o-link')
                    ->form([
                        Select::make('quiz_id')
                            ->label('Quiz')
                            ->searchable()
                            ->preload()
                            ->options(fn() => Quiz::query()
                                ->whereNull('lesson_id')
                                ->orderBy('title')
                                ->pluck('title', 'id'))
                            ->required()
                            ->helperText('Shows quizzes that are not yet linked to a lesson.'),
                    ])
                    ->action(function (array $data) {
                        $quiz = Quiz::findOrFail($data['quiz_id']);

                        if ($quiz->lesson_id && $quiz->lesson_id !== $this->getOwnerRecord()->id) {
                            throw ValidationException::withMessages([
                                'quiz_id' => 'This quiz is already linked to another lesson.',
                            ]);
                        }

                        $quiz->update([
                            'lesson_id' => $this->getOwnerRecord()->id,
                        ]);
                    }),

                Action::make('createQuiz')
                    ->label('Create Quiz')
                    ->icon('heroicon-o-plus-circle')
                    ->url(fn() => QuizResource::getUrl('create', [
                        'lesson_id' => $this->getOwnerRecord()->id,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Open')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn($record) => QuizResource::getUrl('edit', [
                        'record' => $record,
                    ]))
                    ->openUrlInNewTab(),

                Action::make('unlink')
                    ->label('Unlink')
                    ->icon('heroicon-o-x-mark')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->update(['lesson_id' => null])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Action::make('unlinkSelected')
                        ->label('Unlink Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            /** @var \Illuminate\Support\Collection $records */
                            $records->each->update(['lesson_id' => null]);
                        }),
                ]),
            ]);
    }
}
