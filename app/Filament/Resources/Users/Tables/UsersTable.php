<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;

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

                TextColumn::make('trial_ends_at')
                    ->label('Trial Status')
                    ->badge()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) {
                            return 'No Trial';
                        }
                        $trialEndsAt = \Carbon\Carbon::parse($state);
                        if ($trialEndsAt->isAfter(now())) {
                            $interval = now()->diff($trialEndsAt);
                            $days = $interval->days;
                            $hours = $interval->h;

                            if ($days > 0) {
                                return "{$days}d {$hours}h left";
                            } else {
                                return "{$hours}h {$interval->i}m left";
                            }
                        }
                        return 'Expired';
                    })
                    ->color(function (?string $state): string {
                        if (!$state) {
                            return 'gray';
                        }
                        $trialEndsAt = \Carbon\Carbon::parse($state);
                        if ($trialEndsAt->isAfter(now())) {
                            return 'success';
                        }
                        return 'danger';
                    })
                    ->icon(function (?string $state): ?string {
                        if (!$state) {
                            return 'heroicon-o-x-circle';
                        }
                        $trialEndsAt = \Carbon\Carbon::parse($state);
                        if ($trialEndsAt->isAfter(now())) {
                            return 'heroicon-o-clock';
                        }
                        return 'heroicon-o-x-mark';
                    }),
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
                    ->visible(fn() => Auth::user()?->can('manage users') ?? false),

                Action::make('extendTrial')
                    ->label('Extend Trial')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('trial_ends_at')
                            ->label('Trial Ends At')
                            ->type('datetime-local')
                            ->required()
                            ->default(fn() => now()->startOfDay()->format('Y-m-d\TH:i'))
                            ->helperText('Pick the exact date and time when trial should expire'),
                    ])
                    ->action(function (User $record, array $data) {
                        $trialDate = \Carbon\Carbon::createFromFormat('Y-m-d\\TH:i', $data['trial_ends_at']);
                        $record->update([
                            'trial_ends_at' => $trialDate,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Trial Extended')
                            ->body("Trial extended until " . $trialDate->format('M d, Y H:i'))
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Extend Trial')
                    ->modalDescription('Set when this user\'s trial should expire')
                    ->modalSubmitActionLabel('Extend Trial')
                    ->visible(fn(User $record) => Auth::user()?->hasRole('super-admin') && $record->account_type !== 'guardian'),

                Action::make('cancelTrial')
                    ->label('Cancel Trial')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (User $record) {
                        $record->update([
                            'trial_ends_at' => null,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Trial Cancelled')
                            ->body('Trial has been cancelled for this user')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Trial')
                    ->modalDescription('Cancel this user\'s trial access?')
                    ->modalSubmitActionLabel('Cancel Trial')
                    ->visible(fn(User $record) => Auth::user()?->hasRole('super-admin') && $record->trial_ends_at),

                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(function (User $record): bool {
                        $user = Auth::user();
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
                    Action::make('extendTrialBulk')
                        ->label('Extend Trial')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            TextInput::make('trial_ends_at')
                                ->label('Trial Ends At')
                                ->type('datetime-local')
                                ->required()
                                ->default(fn() => now()->startOfDay()->format('Y-m-d\TH:i'))
                                ->helperText('Pick the exact date and time when trial should expire'),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update([
                                    'trial_ends_at' => \Carbon\Carbon::createFromFormat('Y-m-d\\TH:i', $data['trial_ends_at']),
                                ]);
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Trial Extended')
                                ->body('Trial extended for ' . $records->count() . ' user(s)')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->accessSelectedRecords()
                        ->visible(fn() => Auth::user()?->hasRole('super-admin') ?? false),

                    Action::make('cancelTrialBulk')
                        ->label('Cancel Trial')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Trial for Selected Users')
                        ->modalDescription('Remove trial access for all selected users?')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'trial_ends_at' => null,
                                ]);
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Trial Cancelled')
                                ->body('Trial cancelled for ' . $records->count() . ' user(s)')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->accessSelectedRecords()
                        ->visible(fn() => Auth::user()?->hasRole('super-admin') ?? false),

                    DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()?->hasRole('super-admin') ?? false),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
