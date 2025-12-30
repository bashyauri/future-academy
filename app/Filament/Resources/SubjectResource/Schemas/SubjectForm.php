<?php

namespace App\Filament\Resources\SubjectResource\Schemas;

use App\Models\ExamType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Subject Information')
                    ->description('Define the subject details')
                    ->icon('heroicon-o-book-open')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Subject Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-book-open')
                                    ->placeholder('e.g., Mathematics, English Language')
                                    ->helperText('Full name of the subject')
                                    ->columnSpan(1),

                                    TextInput::make('code')
                                        ->label('Subject Code')
                                        ->required()
                                        ->maxLength(50)
                                        ->helperText('Unique code for this subject (required).')
                                        ->columnSpan(1),

                                    TextInput::make('icon')
                                        ->label('Icon/Emoji')
                                        ->maxLength(50)
                                        ->prefixIcon('heroicon-o-face-smile')
                                        ->placeholder('📐 or heroicon-o-calculator')
                                        ->helperText('Emoji or icon class')
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
                            ->placeholder('Brief description of this subject...')
                            ->helperText('Optional: Add details about this subject')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Exam Types')
                    ->description('Select which exam types include this subject')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        CheckboxList::make('examTypes')
                            ->label('Available in these exams')
                            ->relationship('examTypes', 'name')
                            ->options(function () {
                                return ExamType::query()
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->searchable()
                            ->helperText('Select all exam types that include this subject.')
                            ->required()
                            ->minItems(1)
                            ->descriptions(function () {
                                $examTypes = ExamType::where('is_active', true)->get();
                                $result = [];

                                foreach ($examTypes as $examType) {
                                    $result[$examType->id] = $examType->code . ' - ' . ($examType->description ?? 'No description');
                                }

                                return $result;
                            }),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Display Settings')
                    ->description('Customize how this subject appears')
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
                                    ->helperText('Inactive subjects are hidden from students')
                                    ->columnSpan(2),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

            ]);
    }
}
