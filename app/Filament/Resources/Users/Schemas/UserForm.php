<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Personal Information')
                    ->description('Basic user details and contact information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-user')
                                    ->placeholder('Enter full name')
                                    ->columnSpan(2),

                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->unique(User::class, 'email', ignoreRecord: true)
                                    ->nullable()
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->placeholder('user@example.com')
                                    ->helperText('Optional, but must be unique if provided.')
                                    ->columnSpan(1),

                                TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->unique(User::class, 'phone', ignoreRecord: true)
                                    ->nullable()
                                    ->prefixIcon('heroicon-o-phone')
                                    ->placeholder('+1 (555) 000-0000')
                                    ->helperText('Optional, but must be unique if provided.')
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Security & Authentication')
                    ->description('Password and account security settings')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn($livewire) => $livewire instanceof \App\Filament\Resources\Users\Pages\CreateUser)
                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->revealable()
                            ->prefixIcon('heroicon-o-key')
                            ->placeholder('••••••••')
                            ->helperText(fn($livewire) => $livewire instanceof \App\Filament\Resources\Users\Pages\EditUser ? 'Leave blank to keep current password.' : 'Minimum 8 characters recommended.')
                            ->minLength(6),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Role & Permissions')
                    ->description('Assign user roles and access level')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Select::make('account_type')
                                    ->label('Primary Role (Account Type)')
                                    ->options([
                                        'super-admin' => '🔴 Super Admin',
                                        'admin' => '🟠 Admin',
                                        'teacher' => '🔵 Teacher',
                                        'uploader' => '🟢 Uploader',
                                        'guardian' => '🟣 Guardian',
                                        'student' => '⚪ Student',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-user-circle')
                                    ->helperText('This determines the user\'s main dashboard and access level.')
                                    ->columnSpan(1),

                                Select::make('roles')
                                    ->label('Additional Roles')
                                    ->relationship('roles', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-users')
                                    ->helperText('Assign multiple Spatie permission roles.')
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Profile & Status')
                    ->description('Profile picture and account status')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Grid::make()
                            ->schema([
                                FileUpload::make('avatar')
                                    ->label('Profile Picture')
                                    ->image()
                                    ->imageEditor()
                                    ->circleCropper()
                                    ->directory('avatars')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->maxSize(2048)
                                    ->helperText('Recommended: Square image, max 2MB')
                                    ->columnSpan(1),

                                Toggle::make('is_active')
                                    ->label('Account Active')
                                    ->inline(false)
                                    ->helperText('Inactive users cannot log in to the system.')
                                    ->default(true)
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Trial Management')
                    ->description('Manage user trial period access')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('trial_ends_at')
                                    ->label('Trial Ends At')
                                    ->type('datetime-local')
                                    ->nullable()
                                    ->default(fn() => now()->startOfDay()->format('Y-m-d\TH:i'))
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->helperText('Set when the user\'s trial period expires. Leave empty to cancel trial.')
                                    ->columnSpan(2),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->columnSpanFull()
                    ->visible(fn() => Auth::user()?->hasRole('super-admin') ?? false),

            ]);
    }
}
