<?php

namespace App\Services;

use App\Models\Lesson;
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
        $lesson = Lesson::with('subject')->findOrFail($lessonId);
        $progress = UserProgress::where('user_id', $user->id)
            ->where('lesson_id', $lessonId)
            ->where('type', 'lesson')
            ->first();

        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'description' => $lesson->description,
            'video_url' => $lesson->video_url,
            'duration_seconds' => $lesson->duration_seconds,
            'thumbnail_url' => $lesson->thumbnail_url,
            'subject' => [
                'id' => $lesson->subject->id,
                'name' => $lesson->subject->name,
                'code' => $lesson->subject->code,
                'slug' => $lesson->subject->slug,
            ],
            'progress' => $progress ? [
                'is_completed' => $progress->is_completed,
                'progress_percentage' => $progress->progress_percentage,
                'current_time_seconds' => $progress->current_time_seconds,
                'time_spent_seconds' => $progress->time_spent_seconds,
            ] : null,
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

            return UserProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lessonId,
                    'type' => 'lesson',
                ],
                [
                    'is_completed' => true,
                    'progress_percentage' => 100,
                    'current_time_seconds' => $lesson->duration_seconds ?? 0,
                    'time_spent_seconds' => $lesson->duration_seconds ?? 0,
                    'completed_at' => now(),
                ]
            );
        });
    }
}
