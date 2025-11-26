<?php

namespace App\Filament\Resources\ExamTypeResource\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExamTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Exam Type Information')
                    ->description('Define the exam type details')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Exam Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-academic-cap')
                                    ->placeholder('e.g., WAEC, NECO, JAMB')
                                    ->helperText('Full name of the examination')
                                    ->columnSpan(1),

                                TextInput::make('code')
                                    ->label('Exam Code')
                                    ->required()
                                    ->maxLength(10)
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-hashtag')
                                    ->placeholder('e.g., WAEC')
                                    ->helperText('Short code (auto-generated from name if empty)')
                                    ->columnSpan(1),
                            ])
                            ->columns(2),

                        TextInput::make('slug')
                            ->label('URL Slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->prefixIcon('heroicon-o-link')
                            ->placeholder('Auto-generated from name')
                            ->helperText('Leave blank to auto-generate')
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Brief description of this exam type...')
                            ->helperText('Optional: Add details about this examination')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Display Settings')
                    ->description('Customize how this exam type appears')
                    ->icon('heroicon-o-paint-brush')
                    ->schema([
                        Grid::make()
                            ->schema([
                                ColorPicker::make('color')
                                    ->label('Display Color')
                                    ->default('#3B82F6')
                                    ->helperText('Color for badges and UI elements')
                                    ->columnSpan(1),

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
                                    ->helperText('Inactive exam types are hidden from students')
                                    ->columnSpan(2),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

            ]);
    }
}
