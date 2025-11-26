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
                    ->limit(70)
                    ->searchable()
                    ->weight('medium')
                    ->wrap()
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 70) {
                            return null;
                        }
                        return $state;
                    }),

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

                TextColumn::make('times_used')
                    ->label('Used')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('System'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->visible(fn() => \Filament\Facades\Filament::auth()->user()?->hasRole('super-admin') ?? false),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
