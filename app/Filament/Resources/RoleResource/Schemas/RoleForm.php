<?php

namespace App\Filament\Resources\RoleResource\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Role Details')
                    ->description('Define the role name and description')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Role Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-tag')
                                    ->placeholder('e.g., content-manager')
                                    ->helperText('Use lowercase with hyphens (e.g., content-manager)')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('name', Str::slug($state));
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
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Permissions')
                    ->description('Select permissions to assign to this role')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        CheckboxList::make('permissions')
                            ->label('Assign Permissions')
                            ->relationship('permissions', 'name')
                            ->options(function () {
                                return Permission::query()
                                    ->where('guard_name', 'web')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->searchable()
                            ->helperText('Select all permissions that users with this role should have access to.')
                            ->descriptions(function () {
                                $descriptions = [
                                    'manage users' => 'Create, edit, and delete users',
                                    'upload questions' => 'Upload and manage quiz questions',
                                    'view stats' => 'View system statistics and analytics',
                                    'manage subjects' => 'Create and manage subjects',
                                    'manage topics' => 'Create and manage topics',
                                    'manage quizzes' => 'Create and manage quizzes',
                                    'view reports' => 'View user reports and progress',
                                    'manage subscriptions' => 'Manage user subscriptions',
                                ];

                                $permissions = Permission::where('guard_name', 'web')->get();
                                $result = [];

                                foreach ($permissions as $permission) {
                                    $result[$permission->id] = $descriptions[$permission->name] ?? 'No description available';
                                }

                                return $result;
                            }),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

            ]);
    }
}
