<?php

namespace App\Filament\Resources\RoleResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Role Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-shield-check')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'super-admin' => 'danger',
                        'admin' => 'warning',
                        'teacher' => 'info',
                        'uploader' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('-', ' ', $state))),

                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-key')
                    ->sortable(),

                TextColumn::make('permissions.name')
                    ->label('Permission List')
                    ->badge()
                    ->separator(', ')
                    ->wrap()
                    ->limit(50)
                    ->toggleable()
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('-', ' ', $state))),

                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-users')
                    ->sortable(),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description(fn(Role $record): string => $record->created_at->diffForHumans()),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn(Role $record) => auth()->user()?->hasRole('super-admin') ?? false),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(function (Role $record): bool {
                        if (!auth()->user()?->hasRole('super-admin')) {
                            return false;
                        }
                        // Protect system roles
                        $protectedRoles = ['super-admin', 'admin', 'teacher', 'uploader', 'guardian', 'student'];
                        return !in_array($record->name, $protectedRoles);
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
