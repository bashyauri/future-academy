<?php

namespace App\Livewire\Dashboard;

use App\Models\Subject;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserProgress;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

class Index extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        $user = auth()->user();

        // Get recent quiz attempts
        $recentAttempts = QuizAttempt::with('quiz')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // Get overall stats
        $totalQuizzes = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $averageScore = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->avg('score_percentage') ?? 0;

        $lessonsCompleted = UserProgress::where('user_id', $user->id)
            ->where('type', 'lesson')
            ->where('is_completed', true)
            ->count();

        $quizzesCompleted = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $totalTimeSpent = UserProgress::where('user_id', $user->id)
            ->sum('time_spent_seconds');

        // Subject performance
        $subjectPerformance = QuizAttempt::select(
            'quizzes.subject_ids',
            DB::raw('AVG(quiz_attempts.score_percentage) as avg_score'),
            DB::raw('COUNT(*) as total_attempts')
        )
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->where('quiz_attempts.user_id', $user->id)
            ->where('quiz_attempts.status', 'completed')
            ->whereNotNull('quizzes.subject_ids')
            ->groupBy('quizzes.subject_ids')
            ->get()
            ->map(function ($item) {
                $subjectIds = json_decode($item->subject_ids, true);
                if (!empty($subjectIds)) {
                    $subject = Subject::find($subjectIds[0]);
                    return [
                        'subject' => $subject?->name ?? 'Unknown',
                        'avg_score' => round($item->avg_score, 1),
                        'total_attempts' => $item->total_attempts,
                    ];
                }
                return null;
            })
            ->filter()
            ->take(5);

        // Continue learning - incomplete lessons
        $continueLesson = UserProgress::with('lesson.subject')
            ->where('user_id', $user->id)
            ->where('type', 'lesson')
            ->where('is_completed', false)
            ->latest('updated_at')
            ->first();

        return view('livewire.dashboard.index', [
            'recentAttempts' => $recentAttempts,
            'totalQuizzes' => $totalQuizzes,
            'averageScore' => round($averageScore, 1),
            'lessonsCompleted' => $lessonsCompleted,
            'quizzesCompleted' => $quizzesCompleted,
            'totalTimeSpent' => $totalTimeSpent,
            'subjectPerformance' => $subjectPerformance,
            'continueLesson' => $continueLesson,
        ]);
    }
}
