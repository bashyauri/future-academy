<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Filament\Resources\LessonResource\RelationManagers;
use App\Filament\Resources\LessonResource\Schemas\LessonForm;
use App\Filament\Resources\LessonResource\Tables\LessonsTable;
use App\Models\Lesson;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AcademicCap;

    protected static string|\UnitEnum|null $navigationGroup = '📚 Content Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Lessons';

    protected static ?string $recordTitleAttribute = 'title';

    public static function shouldRegisterNavigation(): bool
    {
        $user = \Filament\Facades\Filament::auth()->user();
        return (bool) $user?->hasAnyPermission([
            'view lessons',
            'create lessons',
            'edit lessons',
        ]);
    }

    public static function canViewAny(): bool
    {
        return \Filament\Facades\Filament::auth()->user()?->can('view lessons') ?? false;
    }

    public static function canCreate(): bool
    {
        return \Filament\Facades\Filament::auth()->user()?->can('create lessons') ?? false;
    }

    public static function canEdit($record): bool
    {
        return \Filament\Facades\Filament::auth()->user()?->can('edit lessons') ?? false;
    }

    public static function canDelete($record): bool
    {
        return \Filament\Facades\Filament::auth()->user()?->can('delete lessons') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return LessonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LessonsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'edit' => Pages\EditLesson::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'published')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
