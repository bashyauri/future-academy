<?php

namespace App\Filament\Resources\PermissionResource\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class PermissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Permission Details')
                    ->description('Define the permission name and description')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Permission Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-tag')
                                    ->placeholder('e.g., manage-content')
                                    ->helperText('Use lowercase with hyphens or spaces (e.g., manage content)')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('name', strtolower(trim($state)));
                                    })
                                    ->columnSpan(1),

                                TextInput::make('guard_name')
                                    ->label('Guard Name')
                                    ->default('web')
                                    ->required()
                                    ->prefixIcon('heroicon-o-shield-check')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Default guard for authentication')
                                    ->columnSpan(1),
                            ])
                            ->columns(2),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Describe what this permission allows users to do...')
                            ->helperText('This is for documentation purposes only (not stored in database)')
                            ->rows(3)
                            ->columnSpanFull()
                            ->dehydrated(false),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Assign to Roles')
                    ->description('Select which roles should have this permission')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        CheckboxList::make('roles')
                            ->label('Assign to Roles')
                            ->relationship('roles', 'name')
                            ->options(function () {
                                return Role::query()
                                    ->where('guard_name', 'web')
                                    ->pluck('name', 'id')
                                    ->map(fn($name) => ucwords(str_replace('-', ' ', $name)))
                                    ->toArray();
                            })
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->searchable()
                            ->helperText('Select all roles that should have this permission.')
                            ->descriptions(function () {
                                $descriptions = [
                                    'super-admin' => 'Full system access',
                                    'admin' => 'Administrative access',
                                    'teacher' => 'Teacher dashboard access',
                                    'uploader' => 'Content upload access',
                                    'guardian' => 'Guardian dashboard access',
                                    'student' => 'Student dashboard access',
                                ];

                                $roles = Role::where('guard_name', 'web')->get();
                                $result = [];

                                foreach ($roles as $role) {
                                    $result[$role->id] = $descriptions[$role->name] ?? 'Custom role';
                                }

                                return $result;
                            }),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

            ]);
    }
}
