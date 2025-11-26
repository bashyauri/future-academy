<?php

namespace App\Filament\Resources\SubjectResource\Tables;

use App\Models\Subject;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SubjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('icon')
                    ->label('')
                    ->formatStateUsing(fn(?string $state): string => $state ?? '📚'),

                TextColumn::make('name')
                    ->label('Subject Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-book-open'),

                TextColumn::make('examTypes.code')
                    ->label('Exam Types')
                    ->badge()
                    ->separator(', ')
                    ->color(fn($state, Subject $record) => $record->examTypes->firstWhere('code', $state)?->color ?? 'gray')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('topics_count')
                    ->label('Topics')
                    ->counts('topics')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-list-bullet')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->wrap()
                    ->toggleable()
                    ->placeholder('No description'),

                ColorColumn::make('color')
                    ->label('Color')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('exam_types')
                    ->label('Exam Type')
                    ->relationship('examTypes', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All subjects')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(fn() => auth()->user()?->hasRole('super-admin') ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('super-admin') ?? false),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
