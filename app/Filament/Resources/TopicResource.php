<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TopicResource\Pages;
use App\Models\Topic;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ListBullet;

    protected static ?string $navigationLabel = 'Topics';

    protected static ?string $pluralModelLabel = 'Topics';

    protected static ?string $modelLabel = 'Topic';

    protected static string|\UnitEnum|null $navigationGroup = '📚 Academic Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return \App\Filament\Resources\TopicResource\Schemas\TopicForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return \App\Filament\Resources\TopicResource\Tables\TopicsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin', 'teacher', 'uploader']);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin', 'teacher']);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin', 'teacher']);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopics::route('/'),
            'create' => Pages\CreateTopic::route('/create'),
            'edit' => Pages\EditTopic::route('/{record}/edit'),
        ];
    }
}
