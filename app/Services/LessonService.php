<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Facades\DB;

class LessonService
{
    /**
     * Get lessons for a subject with user progress
     */
    public function getLessonsForSubject(User $user, int $subjectId): array
    {
        $lessons = Lesson::where('subject_id', $subjectId)
            ->orderBy('order', 'asc')
            ->get();

        return $lessons->map(function ($lesson) use ($user) {
            $progress = UserProgress::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->where('type', 'lesson')
                ->first();

            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'duration_seconds' => $lesson->duration_seconds,
                'thumbnail_url' => $lesson->thumbnail_url,
                'order' => $lesson->order,
                'is_completed' => $progress?->is_completed ?? false,
                'progress_percentage' => $progress?->progress_percentage ?? 0,
                'current_time_seconds' => $progress?->current_time_seconds ?? 0,
                'time_spent_seconds' => $progress?->time_spent_seconds ?? 0,
            ];
        })->toArray();
    }

    /**
     * Get lesson details with video URL
     */
    public function getLessonDetails(User $user, int $lessonId): array
    {
        $lesson = Lesson::with(['subject', 'topic', 'questions.options'])->findOrFail($lessonId);
        $progress = UserProgress::where('user_id', $user->id)
            ->where('lesson_id', $lessonId)
            ->where('type', 'lesson')
            ->first();

        $previousLesson = Lesson::where('subject_id', $lesson->subject_id)
            ->where('status', 'published')
            ->where('order', '<', $lesson->order)
            ->orderBy('order', 'desc')
            ->first();

        $nextLesson = Lesson::where('subject_id', $lesson->subject_id)
            ->where('status', 'published')
            ->where('order', '>', $lesson->order)
            ->orderBy('order', 'asc')
            ->first();

        $lessonQuiz = Quiz::query()
            ->active()
            ->available()
            ->where('lesson_id', $lesson->id)
            ->orderByDesc('created_at')
            ->first();

        $quizCompleted = false;
        $quizDetails = null;
        if ($lessonQuiz) {
            $quizCompleted = QuizAttempt::query()
                ->where('quiz_id', $lessonQuiz->id)
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->exists();

            $quizService = app(QuizGeneratorService::class);
            $userStats = $quizService->getUserStats($lessonQuiz, $user);

            $quizDetails = [
                'id' => $lessonQuiz->id,
                'title' => $lessonQuiz->title,
                'description' => $lessonQuiz->description,
                'type' => $lessonQuiz->type,
                'duration_minutes' => $lessonQuiz->duration_minutes,
                'question_count' => $lessonQuiz->question_count,
                'user_stats' => [
                    'total_attempts' => $userStats['total_attempts'] ?? 0,
                    'best_score' => $userStats['best_score'] ?? null,
                    'can_attempt' => ! ($user->isParentViewing ?? false) && $lessonQuiz->canUserAttempt($user),
                ],
            ];
        }

        $practiceQuestions = $lesson->questions->map(function ($question) {
            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_text_html' => $question->question_text_html ?? $question->question_text,
                'question_image' => $question->question_image,
                'difficulty' => $question->difficulty,
                'explanation' => $question->explanation,
                'explanation_html' => $question->explanation_html ?? $question->explanation,
                'options' => $question->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'option_text' => $option->option_text,
                        'option_text_html' => $option->option_text_html ?? $option->option_text,
                        'is_correct' => $option->is_correct,
                    ];
                })->toArray(),
            ];
        })->toArray();

        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'description' => $lesson->description,
            'content' => $lesson->content,
            'video_url' => $lesson->video_url,
            'video_type' => $lesson->video_type,
            'video_embed_url' => $lesson->getVideoEmbedUrl(),
            'video_stream_url' => $lesson->getStreamingUrl(),
            'video_playback_url' => $lesson->getSignedVideoUrl(),
            'duration_minutes' => $lesson->duration_minutes,
            'duration_seconds' => ($lesson->duration_minutes ?? 0) * 60,
            'thumbnail_url' => $lesson->thumbnail_url,
            'subject' => [
                'id' => $lesson->subject->id,
                'name' => $lesson->subject->name,
                'code' => $lesson->subject->code,
                'slug' => $lesson->subject->slug,
            ],
            'topic' => $lesson->topic ? [
                'id' => $lesson->topic->id,
                'name' => $lesson->topic->name,
            ] : null,
            'previous_lesson' => $previousLesson ? [
                'id' => $previousLesson->id,
                'title' => $previousLesson->title,
            ] : null,
            'next_lesson' => $nextLesson ? [
                'id' => $nextLesson->id,
                'title' => $nextLesson->title,
            ] : null,
            'progress' => $progress ? [
                'is_completed' => $progress->is_completed,
                'progress_percentage' => $progress->progress_percentage,
                'current_time_seconds' => $progress->current_time_seconds,
                'time_spent_seconds' => $progress->time_spent_seconds,
            ] : null,
            'quiz' => $quizDetails,
            'quiz_completed' => $quizCompleted,
            'practice_questions' => $practiceQuestions,
        ];
    }

    /**
     * Update lesson progress
     */
    public function updateProgress(User $user, int $lessonId, array $data): UserProgress
    {
        return DB::transaction(function () use ($user, $lessonId, $data) {
            return UserProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lessonId,
                    'type' => 'lesson',
                ],
                [
                    'current_time_seconds' => $data['current_time_seconds'] ?? 0,
                    'progress_percentage' => $data['progress_percentage'] ?? 0,
                    'time_spent_seconds' => $data['time_spent_seconds'] ?? 0,
                    'is_completed' => $data['is_completed'] ?? false,
                ]
            );
        });
    }

    /**
     * Mark lesson as completed
     */
    public function markAsCompleted(User $user, int $lessonId): UserProgress
    {
        return DB::transaction(function () use ($user, $lessonId) {
            $lesson = Lesson::findOrFail($lessonId);

            $lessonQuiz = Quiz::query()
                ->active()
                ->available()
                ->where('lesson_id', $lessonId)
                ->first();

            if ($lessonQuiz) {
                $quizCompleted = QuizAttempt::query()
                    ->where('quiz_id', $lessonQuiz->id)
                    ->where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->exists();

                if (! $quizCompleted) {
                    throw new \Exception('Please complete the lesson quiz before marking this lesson as complete.');
                }
            }

            return UserProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lessonId,
                    'type' => 'lesson',
                ],
                [
                    'is_completed' => true,
                    'progress_percentage' => 100,
                    'current_time_seconds' => ($lesson->duration_minutes ?? 0) * 60,
                    'time_spent_seconds' => ($lesson->duration_minutes ?? 0) * 60,
                    'completed_at' => now(),
                ]
            );
        });
    }
}
