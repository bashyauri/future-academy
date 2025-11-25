<?php

namespace App\Filament\Resources\PermissionResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Permission Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-key')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('-', ' ', $state))),

                TextColumn::make('roles.name')
                    ->label('Assigned Roles')
                    ->badge()
                    ->separator(', ')
                    ->wrap()
                    ->colors([
                        'danger' => 'super-admin',
                        'warning' => 'admin',
                        'info' => 'teacher',
                        'success' => 'uploader',
                        'gray' => fn($state): bool => in_array($state, ['guardian', 'student']),
                    ])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('-', ' ', $state))),

                TextColumn::make('roles_count')
                    ->label('Roles')
                    ->counts('roles')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-shield-check')
                    ->sortable(),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description(fn(Permission $record): string => $record->created_at->diffForHumans()),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn() => auth()->user()?->hasRole('super-admin') ?? false),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(function (Permission $record): bool {
                        if (!auth()->user()?->hasRole('super-admin')) {
                            return false;
                        }
                        // Protect core permissions
                        $protectedPermissions = [
                            'manage users',
                            'upload questions',
                            'view stats',
                            'manage subjects',
                            'manage topics',
                            'manage quizzes',
                            'view reports',
                            'manage subscriptions',
                        ];
                        return !in_array($record->name, $protectedPermissions);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('super-admin') ?? false),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
