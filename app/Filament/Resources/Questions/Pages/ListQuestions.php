<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Imports\QuestionsImport;
use App\Models\ExamType;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Maatwebsite\Excel\Facades\Excel;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('📤 Import Questions')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalWidth('3xl')
                ->form([
                    FileUpload::make('file')
                        ->label('Select CSV or Excel File')
                        ->disk('public')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required()
                        ->maxSize(5120) // 5MB
                        ->helperText('Maximum file size: 5MB. Supported formats: CSV, XLS, XLSX')
                        ->columnSpanFull(),

                    Textarea::make('instructions')
                        ->label('📋 How to Format Your CSV File')
                        ->default("REQUIRED COLUMNS:\n• question_text - The question\n• option_a, option_b - At least 2 options (up to option_f for 6 options)\n• correct_answer - A, B, C, D, E, or F\n• exam_type - Name (e.g., WAEC, NECO, JAMB)\n• subject - Name (e.g., Mathematics, English)\n\nOPTIONAL COLUMNS:\n• topic - Subtopic name (e.g., Algebra)\n• explanation - Why the answer is correct\n• difficulty - easy, medium, or hard\n• year - Past question year\n\nSPECIAL CHARACTERS SUPPORTED:\n✓ Nigerian Naira: ₦\n✓ Math symbols: √, π, ∑, ∞, ∫\n✓ Fractions: ½, ¼, ¾\n✓ Exponents: ², ³")
                        ->disabled()
                        ->rows(10)
                        ->columnSpanFull(),

                    Select::make('default_exam_type_id')
                        ->label('Default Exam Type (Optional)')
                        ->options(ExamType::where('is_active', true)->pluck('name', 'id'))
                        ->helperText('Used for rows without exam_type column')
                        ->searchable()
                        ->prefixIcon('heroicon-o-academic-cap')
                        ->columnSpan(1),

                    Select::make('default_subject_id')
                        ->label('Default Subject (Optional)')
                        ->options(Subject::where('is_active', true)->pluck('name', 'id'))
                        ->helperText('Used for rows without subject column')
                        ->searchable()
                        ->prefixIcon('heroicon-o-book-open')
                        ->columnSpan(1),

                    \Filament\Forms\Components\TextInput::make('batch_name')
                        ->label('Batch Name (Optional)')
                        ->placeholder('e.g., WAEC 2024 Math, Dec 2025 Import')
                        ->helperText('Give this import batch a memorable name for easy tracking')
                        ->maxLength(100)
                        ->prefixIcon('heroicon-o-tag')
                        ->columnSpanFull(),
                ])
                ->modalHeading('📤 Import Questions from CSV/Excel')
                ->modalDescription('Upload a CSV or Excel file to bulk import questions. Download the template below to see the exact format.')
                ->modalSubmitActionLabel('Import Questions')
                ->action(function (array $data) {
                    try {
                        $storedPath = $data['file'] ?? null;
                        if (!$storedPath) {
                            throw new \Exception('No file provided');
                        }

                        $filePath = \Illuminate\Support\Facades\Storage::disk('public')->path($storedPath);

                        if (!file_exists($filePath)) {
                            Notification::make()
                                ->title('File not found')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Create importer instance
                        $import = new QuestionsImport(
                            $data['default_exam_type_id'] ?? null,
                            $data['default_subject_id'] ?? null,
                            \Filament\Facades\Filament::auth()->id(),
                            $data['batch_name'] ?? null
                        );

                        // Import with Laravel Excel
                        Excel::import($import, $filePath);

                        // Get summary
                        $summary = $import->getSummary();

                        // Show notification with results
                        $message = "Successfully imported {$summary['imported']} questions.";
                        if ($summary['skipped'] > 0) {
                            $message .= " {$summary['skipped']} rows skipped.";
                        }

                        if (count($summary['errors']) > 0) {
                            Notification::make()
                                ->title('⚠️ Import Completed with Errors')
                                ->body($message . "\n\n" . count($summary['errors']) . " error(s) found.\nFirst error: " . ($summary['errors'][0] ?? ''))
                                ->warning()
                                ->duration(15000)
                                ->send();
                        } else {
                            Notification::make()
                                ->title('✓ Import Successful!')
                                ->body($message . " All questions are pending review.")
                                ->success()
                                ->duration(5000)
                                ->send();
                        }

                        // Clean up uploaded file
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($storedPath);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('❌ Import Failed')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->duration(10000)
                            ->send();
                    }
                }),

            Action::make('downloadTemplate')
                ->label('📥 Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->tooltip('Download CSV template with examples')
                ->action(function () {
                    return response()->download(
                        storage_path('app/public/templates/questions_import_template.csv'),
                        'questions_template.csv'
                    );
                }),

            CreateAction::make()
                ->label('➕ New Question')
                ->icon('heroicon-o-plus-circle')
                ->modalWidth('5xl'),
        ];
    }
}
