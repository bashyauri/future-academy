<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use App\Models\User;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn(User $record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=6366f1&bold=true')
                    ->size(40),

                TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->description(fn(User $record): string => $record->email ?? 'No email'),

                TextColumn::make('account_type')
                    ->label('Primary Role')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->colors([
                        'danger' => 'super-admin',
                        'warning' => 'admin',
                        'info' => 'teacher',
                        'success' => 'uploader',
                        'gray' => fn($state): bool => in_array($state, ['guardian', 'student']),
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'super-admin' => '🔴 Super Admin',
                        'admin' => '🟠 Admin',
                        'teacher' => '🔵 Teacher',
                        'uploader' => '🟢 Uploader',
                        'guardian' => '🟣 Guardian',
                        'student' => '⚪ Student',
                        default => ucfirst($state),
                    }),

                TextColumn::make('roles.name')
                    ->label('Additional Roles')
                    ->badge()
                    ->separator(', ')
                    ->colors([
                        'primary',
                    ])
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->icon('heroicon-o-phone')
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->copyMessageDuration(1500)
                    ->placeholder('—')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description(fn(User $record): string => $record->created_at->diffForHumans()),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->label('Primary Role')
                    ->options([
                        'super-admin' => '🔴 Super Admin',
                        'admin' => '🟠 Admin',
                        'teacher' => '🔵 Teacher',
                        'uploader' => '🟢 Uploader',
                        'guardian' => '🟣 Guardian',
                        'student' => '⚪ Student',
                    ])
                    ->multiple()
                    ->searchable(),

                TernaryFilter::make('is_active')
                    ->label('Account Status')
                    ->placeholder('All users')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn() => auth()->user()?->can('manage users') ?? false),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(function (User $record): bool {
                        $user = auth()->user();
                        if (!$user?->hasAnyRole(['super-admin', 'admin'])) {
                            return false;
                        }
                        if ($record->id === $user->id) {
                            return false;
                        }
                        if ($record->hasAnyRole(['super-admin', 'admin'])) {
                            return $user->hasRole('super-admin');
                        }
                        return true;
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
