<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Filament\Resources\PermissionResource\Schemas\PermissionForm;
use App\Filament\Resources\PermissionResource\Tables\PermissionsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Key;

    protected static ?string $navigationLabel = 'Permissions';

    protected static ?string $pluralModelLabel = 'Permissions';

    protected static ?string $modelLabel = 'Permission';

    protected static string|\UnitEnum|null $navigationGroup = '👥 Administration';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PermissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    // Access control: only super-admin can manage permissions
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super-admin');
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super-admin');
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->hasRole('super-admin');
    }

    public static function canDelete($record): bool
    {
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

        if (in_array($record->name, $protectedPermissions)) {
            return false;
        }

        return auth()->check() && auth()->user()->hasRole('super-admin');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
