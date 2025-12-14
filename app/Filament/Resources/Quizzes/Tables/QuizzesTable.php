<?php

namespace App\Filament\Resources\Quizzes\Tables;

use App\Enums\QuizType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class QuizzesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->description),

                TextColumn::make('type')
                    ->badge()
                    ->color(function ($state): string {
                        $type = $state instanceof QuizType ? $state : QuizType::tryFrom((string) $state);
                        return $type?->color() ?? 'gray';
                    })
                    ->formatStateUsing(function ($state): string {
                        $type = $state instanceof QuizType ? $state : QuizType::tryFrom((string) $state);
                        if ($type) {
                            return $type->label();
                        }

                        return is_scalar($state) ? ucfirst((string) $state) : '-';
                    })
                    ->sortable(),

                TextColumn::make('question_count')
                    ->label('Questions')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn($state) => $state ? "{$state} min" : 'Untimed')
                    ->sortable(),

                TextColumn::make('passing_score')
                    ->label('Pass %')
                    ->suffix('%')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('lesson.title')
                    ->label('Lesson')
                    ->sortable()
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('attempts_count')
                    ->label('Attempts')
                    ->counts('attempts')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'gray',
                        'archived' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('available_from')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('available_until')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(QuizType::options()),

                SelectFilter::make('status')
                    ->label('Publish Status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),

                SelectFilter::make('lesson_link')
                    ->label('Lesson Link')
                    ->options([
                        'linked' => 'Linked to Lesson',
                        'unlinked' => 'No Lesson',
                    ])
                    ->query(function ($query, $state) {
                        return match ($state) {
                            'linked' => $query->whereNotNull('lesson_id'),
                            'unlinked' => $query->whereNull('lesson_id'),
                            default => $query,
                        };
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status !== 'published' && \Filament\Facades\Filament::auth()->user()?->can('publish quizzes'))
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'published',
                            'published_at' => now(),
                        ]);
                    }),
                Action::make('unpublish')
                    ->label('Unpublish')
                    ->icon('heroicon-o-x-mark')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === 'published' && \Filament\Facades\Filament::auth()->user()?->can('publish quizzes'))
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'draft',
                            'published_at' => null,
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
