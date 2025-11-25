<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    protected static ?string $navigationLabel = 'Roles';

    protected static ?string $pluralModelLabel = 'Roles';

    protected static ?string $modelLabel = 'Role';

    protected static string|\UnitEnum|null $navigationGroup = '👥 Administration';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return \App\Filament\Resources\RoleResource\Schemas\RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return \App\Filament\Resources\RoleResource\Tables\RolesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    // Access control: only admin & super-admin can view resource in nav
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin']);
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
        // Protect system roles
        $protectedRoles = ['super-admin', 'admin', 'teacher', 'uploader', 'guardian', 'student'];
        if (in_array($record->name, $protectedRoles)) {
            return false;
        }
        return auth()->check() && auth()->user()->hasRole('super-admin');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
