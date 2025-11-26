<?php

namespace App\Filament\Resources\TopicResource\Tables;

use App\Models\Topic;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TopicsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn(Topic $record) => $record->subject->color ?? 'gray')
                    ->icon('heroicon-o-book-open'),

                TextColumn::make('name')
                    ->label('Topic Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-list-bullet')
                    ->description(fn(Topic $record): ?string => $record->description ? \Illuminate\Support\Str::limit($record->description, 50) : null),

                TextColumn::make('subject.examTypes.code')
                    ->label('Exam Types')
                    ->badge()
                    ->separator(', ')
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('No description'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All topics')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(fn() => auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false),
                ]),
            ])
            ->defaultSort('subject.name', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
