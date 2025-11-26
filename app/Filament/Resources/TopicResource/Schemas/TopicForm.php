<?php

namespace App\Filament\Resources\TopicResource\Schemas;

use App\Models\Subject;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Topic Information')
                    ->description('Define the topic details')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Select::make('subject_id')
                            ->label('Subject')
                            ->relationship('subject', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->prefixIcon('heroicon-o-book-open')
                            ->helperText('Select the parent subject')
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->label('Subject Name'),
                            ])
                            ->columnSpanFull(),

                        Grid::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Topic Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-list-bullet')
                                    ->placeholder('e.g., Algebra, Comprehension, Mechanics')
                                    ->helperText('Name of the topic within the subject')
                                    ->columnSpan(2),

                                TextInput::make('slug')
                                    ->label('URL Slug')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-link')
                                    ->placeholder('Auto-generated from name')
                                    ->helperText('Leave blank to auto-generate')
                                    ->columnSpan(2),
                            ])
                            ->columns(4),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Brief description of this topic...')
                            ->helperText('Optional: Add details about this topic')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Settings')
                    ->description('Configure topic settings')
                    ->icon('heroicon-o-cog')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefixIcon('heroicon-o-arrows-up-down')
                                    ->helperText('Lower numbers appear first')
                                    ->columnSpan(1),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Inactive topics are hidden from students')
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

            ]);
    }
}
