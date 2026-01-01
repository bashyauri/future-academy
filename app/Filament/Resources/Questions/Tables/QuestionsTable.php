<?php

namespace App\Filament\Resources\Questions\Tables;

use App\Models\Question;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\TextInput;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('question_text')
                    ->label('Question')
                    ->formatStateUsing(function (Question $record): string {
                        $prefix = "Q#{$record->id}";
                        $text = $record->question_text;

                        // Truncate if too long
                        if (strlen($text) > 60) {
                            $text = substr($text, 0, 60) . '...';
                        }

                        return "{$prefix}: {$text}";
                    })
                    ->description(fn(Question $record): string =>
                        $record->subject?->name . ($record->topic ? " › {$record->topic->name}" : '')
                    )
                    ->searchable()
                    ->weight('medium')
                    ->wrap()
                    ->tooltip(fn(Question $record): string =>
                        "Q#{$record->id}\n\n{$record->question_text}"
                    ),

                TextColumn::make('examType.name')
                    ->label('Exam')
                    ->badge()
                    ->color(fn(Question $record) => $record->examType?->color ?? 'gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-book-open'),

                TextColumn::make('topic.name')
                    ->label('Topic')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('No topic'),

                TextColumn::make('difficulty')
                    ->label('Difficulty')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'easy' => 'success',
                        'medium' => 'warning',
                        'hard' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('options_count')
                    ->label('Options')
                    ->counts('options')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_mock')
                    ->label('Mock')
                    ->boolean()
                    ->trueIcon('heroicon-o-beaker')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('times_used')
                    ->label('Used')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('System'),

                TextColumn::make('batch_name')
                    ->label('Batch')
                    ->formatStateUsing(function (Question $record): ?string {
                        if ($record->batch_name) {
                            return $record->batch_name;
                        }

                        // Fallback to formatted upload_batch if no batch_name
                        if ($record->upload_batch && strlen($record->upload_batch) >= 10 && is_numeric(substr($record->upload_batch, 0, 10))) {
                            $timestamp = substr($record->upload_batch, 0, 10);
                            $date = date('M d, Y H:i', (int)$timestamp);
                            return $date;
                        }

                        return null;
                    })
                    ->description(fn(Question $record): ?string =>
                        $record->upload_batch ? "ID: ···" . substr($record->upload_batch, -8) : null
                    )
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('No batch')
                    ->icon('heroicon-o-tag')
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_mock')
                    ->label('Mock Question')
                    ->placeholder('All')
                    ->trueLabel('Mock Only')
                    ->falseLabel('Non-Mock Only'),
                SelectFilter::make('exam_type_id')
                    ->label('Exam Type')
                    ->relationship('examType', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('All Exams'),

                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('All Subjects'),

                SelectFilter::make('topic_id')
                    ->label('Topic')
                    ->relationship('topic', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('All Topics'),

                SelectFilter::make('difficulty')
                    ->label('Difficulty')
                    ->options([
                        'easy' => '🟢 Easy',
                        'medium' => '🟡 Medium',
                        'hard' => '🔴 Hard',
                    ])
                    ->multiple()
                    ->placeholder('All Levels'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => '⏳ Pending Review',
                        'approved' => '✓ Approved',
                        'rejected' => '✗ Rejected',
                    ])
                    ->multiple()
                    ->placeholder('All Statuses'),

                SelectFilter::make('year')
                    ->options(function () {
                        $years = [];
                        for ($y = date('Y'); $y >= 2000; $y--) {
                            $years[$y] = (string) $y;
                        }
                        return $years;
                    })
                    ->multiple(),

                SelectFilter::make('batch_name')
                    ->label('Batch')
                    ->options(function () {
                        return Question::query()
                            ->select('batch_name', 'upload_batch', 'created_at')
                            ->where(function ($query) {
                                $query->whereNotNull('batch_name')
                                      ->orWhereNotNull('upload_batch');
                            })
                            ->orderByDesc('created_at')
                            ->get()
                            ->unique(fn($q) => $q->batch_name ?: $q->upload_batch)
                            ->mapWithKeys(function ($question) {
                                if ($question->batch_name) {
                                    return [$question->batch_name => $question->batch_name];
                                }

                                // Fallback for old records without batch_name
                                if ($question->upload_batch && strlen($question->upload_batch) >= 10 && is_numeric(substr($question->upload_batch, 0, 10))) {
                                    $timestamp = substr($question->upload_batch, 0, 10);
                                    $date = date('M d, Y H:i', (int)$timestamp);
                                    return [$question->upload_batch => $date];
                                }

                                return [];
                            })
                            ->filter();
                    })
                    ->searchable()
                    ->multiple()
                    ->placeholder('All Batches'),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All questions')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info'),

                EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning'),

                Action::make('approve')
                    ->label('✓ Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Question $record) => $record->status === 'pending' && (\Filament\Facades\Filament::auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('✓ Approve This Question?')
                    ->modalDescription('This will mark the question as approved and make it available for use in quizzes.')
                    ->modalIcon('heroicon-o-check-circle')
                    ->modalIconColor('success')
                    ->action(function (Question $record) {
                        $record->approve(\Filament\Facades\Filament::auth()->user());
                    }),

                Action::make('reject')
                    ->label('✗ Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Question $record) => $record->status === 'pending' && (\Filament\Facades\Filament::auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false))
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Why are you rejecting this question?')
                            ->required()
                            ->rows(4)
                            ->placeholder('Provide clear feedback: incorrect answer, poor wording, duplicate question, etc.')
                            ->helperText('This reason will be visible to the question creator'),
                    ])
                    ->modalHeading('✗ Reject Question')
                    ->modalIcon('heroicon-o-x-circle')
                    ->modalIconColor('danger')
                    ->action(function (Question $record, array $data) {
                        $record->reject(\Filament\Facades\Filament::auth()->user(), $data['rejection_reason']);
                    }),

                DeleteAction::make()
                    ->label('Delete')
                    ->modalHeading('Delete Question Permanently?')
                    ->modalDescription('This action cannot be undone. The question and all its options will be permanently deleted.')
                    ->icon('heroicon-o-trash')
                    ->visible(fn() => \Filament\Facades\Filament::auth()->user()?->hasRole('super-admin') ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('bulkApprove')
                        ->label('✓ Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn() => \Filament\Facades\Filament::auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false)
                        ->requiresConfirmation()
                        ->modalHeading('Approve Multiple Questions')
                        ->modalDescription('This will approve all selected pending questions.')
                        ->modalIcon('heroicon-o-check-circle')
                        ->modalIconColor('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->approve(\Filament\Facades\Filament::auth()->user());
                                }
                            }
                        })
                        ->accessSelectedRecords()
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->visible(fn() => \Filament\Facades\Filament::auth()->user()?->hasRole('super-admin') ?? false),

                    Action::make('approveBatch')
                        ->label('Approve Batch')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn() => \Filament\Facades\Filament::auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false)
                        ->form([
                            TextInput::make('upload_batch')
                                ->label('Upload Batch')
                                ->required()
                                ->placeholder('Paste batch ID')
                                ->helperText('Approves all pending questions with this batch ID.'),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Approve Batch')
                        ->modalDescription('Approve all pending questions in the provided batch ID?')
                        ->modalIcon('heroicon-o-check-circle')
                        ->modalIconColor('success')
                        ->action(function (array $data) {
                            $batch = trim($data['upload_batch'] ?? '');
                            if ($batch === '') {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Batch ID is required')
                                    ->send();
                                return;
                            }

                            $user = \Filament\Facades\Filament::auth()->user();
                            $query = Question::query()
                                ->where('upload_batch', $batch)
                                ->where('status', 'pending');

                            $count = $query->count();

                            if ($count === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('No pending questions found for this batch')
                                    ->send();
                                return;
                            }

                            $query->each(fn(Question $question) => $question->approve($user));

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Batch approved')
                                ->body("Approved {$count} pending questions in batch {$batch}.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Action::make('rejectBatch')
                        ->label('Reject Batch')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn() => \Filament\Facades\Filament::auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false)
                        ->form([
                            TextInput::make('upload_batch')
                                ->label('Upload Batch')
                                ->required()
                                ->placeholder('Paste batch ID')
                                ->helperText('Rejects all pending questions with this batch ID.'),

                            Textarea::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->rows(3)
                                ->placeholder('State clearly why this batch is rejected.'),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Reject Batch')
                        ->modalDescription('Reject all pending questions in the provided batch ID?')
                        ->modalIcon('heroicon-o-x-circle')
                        ->modalIconColor('danger')
                        ->action(function (array $data) {
                            $batch = trim($data['upload_batch'] ?? '');
                            if ($batch === '') {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Batch ID is required')
                                    ->send();
                                return;
                            }

                            $user = \Filament\Facades\Filament::auth()->user();
                            $query = Question::query()
                                ->where('upload_batch', $batch)
                                ->where('status', 'pending');

                            $count = $query->count();

                            if ($count === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('No pending questions found for this batch')
                                    ->send();
                                return;
                            }

                            $query->each(fn(Question $question) => $question->reject($user, $data['rejection_reason']));

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Batch rejected')
                                ->body("Rejected {$count} pending questions in batch {$batch}.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Action::make('bulkDeleteByCriteria')
                        ->label('Bulk Delete by Criteria')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn() => \Filament\Facades\Filament::auth()->user()?->hasRole('super-admin') ?? false)
                        ->form([
                            FormSelect::make('exam_type_id')
                                ->label('Exam Type')
                                ->relationship('examType', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('Any'),

                            FormSelect::make('subject_id')
                                ->label('Subject')
                                ->relationship('subject', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('Any'),

                            FormSelect::make('topic_id')
                                ->label('Topic')
                                ->relationship('topic', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('Any'),

                            FormSelect::make('difficulty')
                                ->label('Difficulty')
                                ->options([
                                    'easy' => 'Easy',
                                    'medium' => 'Medium',
                                    'hard' => 'Hard',
                                ])
                                ->placeholder('Any'),

                            FormSelect::make('status')
                                ->label('Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                ])
                                ->placeholder('Any'),

                            FormSelect::make('year')
                                ->label('Year')
                                ->options(function () {
                                    $years = [];
                                    for ($y = date('Y'); $y >= 2000; $y--) {
                                        $years[$y] = (string) $y;
                                    }
                                    return $years;
                                })
                                ->placeholder('Any'),

                            TextInput::make('upload_batch')
                                ->label('Upload Batch')
                                ->placeholder('Paste batch ID (optional)'),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Delete by Criteria')
                        ->modalDescription('Deletes all questions matching the criteria below. This cannot be undone.')
                        ->modalIcon('heroicon-o-trash')
                        ->modalIconColor('danger')
                        ->action(function (array $data) {
                            $query = Question::query();

                            foreach (['exam_type_id', 'subject_id', 'topic_id', 'difficulty', 'status', 'year', 'upload_batch'] as $field) {
                                if (!empty($data[$field])) {
                                    $query->where($field, $data[$field]);
                                }
                            }

                            $count = $query->count();

                            if ($count === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('No questions matched the criteria')
                                    ->send();
                                return;
                            }

                            $query->delete();

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Questions deleted')
                                ->body("Deleted {$count} questions matching the criteria.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
