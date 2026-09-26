<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Filament\Resources\SubjectResource\RelationManagers\TopicsRelationManager;
use App\Filament\Resources\SubjectResource\Schemas\SubjectForm;
use App\Filament\Resources\SubjectResource\Tables\SubjectsTable;
use App\Models\Subject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BookOpen;

    protected static ?string $navigationLabel = 'Subjects';

    protected static ?string $pluralModelLabel = 'Subjects';

    protected static ?string $modelLabel = 'Subject';

    protected static string|\UnitEnum|null $navigationGroup = '📚 Academic Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SubjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TopicsRelationManager::class,
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin', 'teacher']);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin']);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin']);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->hasRole('super-admin');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
