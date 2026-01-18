<?php

namespace App\Filament\Pages;

use App\Models\ExamType;
use App\Models\Subject;
use App\Services\MockGroupService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MockGroupManager extends Page
{
    public ?int $exam_type_id = null;

    public ?int $subject_id = null;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = '📚 Academic Management';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Mock Grouping';

    protected static ?string $title = 'Mock Question Grouping';

    protected string $view = 'filament.pages.mock-group-manager';

    public static function shouldRegisterNavigation(): bool
    {
        $user = \Filament\Facades\Filament::auth()->user();
        return $user && ($user->hasRole(['super-admin', 'admin']) ||
            $user->hasAnyPermission([
                'manage questions',
                'import questions',
                'upload questions',
            ]));
    }

    public static function canAccess(): bool
    {
        $user = \Filament\Facades\Filament::auth()->user();
        return $user && ($user->hasRole(['super-admin', 'admin']) ||
            $user->hasAnyPermission([
                'manage questions',
                'import questions',
                'upload questions',
            ]));
    }

    public function groupQuestions(): void
    {
        if (!$this->exam_type_id) {
            Notification::make()
                ->danger()
                ->title('Please select an exam type')
                ->send();
            return;
        }

        $examType = ExamType::find($this->exam_type_id);
        $mockGroupService = app(MockGroupService::class);
        $groupedCount = 0;

        if (isset($this->subject_id) && $this->subject_id) {
            $subject = Subject::find($this->subject_id);
            $mockGroupService->groupMockQuestions($subject, $examType);
            $groupedCount = 1;
        } else {
            $subjects = Subject::whereHas('questions', function ($query) {
                $query->where('exam_type_id', $this->exam_type_id)
                    ->where('is_mock', true);
            })->get();

            foreach ($subjects as $subject) {
                $mockGroupService->groupMockQuestions($subject, $examType);
                $groupedCount++;
            }
        }

        Notification::make()
            ->success()
            ->title('Mock questions grouped successfully!')
            ->body("Grouped mock questions for {$groupedCount} subject(s)")
            ->send();

        $this->exam_type_id = null;
        $this->subject_id = null;
    }
}
