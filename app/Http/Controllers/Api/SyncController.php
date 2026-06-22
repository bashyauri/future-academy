<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SyncRequest;
use App\Http\Resources\Api\SyncResponse;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Models\UserProgress;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @group Sync Engine
 *
 * APIs for synchronizing offline data from mobile app to server.
 * Handles quiz attempts, answers, and lesson progress with transaction safety.
 */
class SyncController extends Controller
{
    /**
     * Sync Offline Data
     *
     * Processes batch sync of offline quiz attempts, answers, and lesson progress.
     * Uses database transactions to ensure data integrity and prevent double-grading.
     *
     * @bodyParam attempts array required Array of offline quiz attempts. Example: [{"uuid":"abc123","user_id":1,"subject_id":1,"exam_year":2024,"status":"completed","started_at":"2024-01-01T10:00:00Z","completed_at":"2024-01-01T10:30:00Z","time_taken_seconds":1800,"total_questions":40,"correct_answers":32,"question_order":[1,2,3]}]
     * @bodyParam answers array required Array of offline answers. Example: [{"attempt_uuid":"abc123","question_id":1,"option_id":3,"is_correct":true}]
     * @bodyParam lesson_progress array required Array of lesson progress. Example: [{"user_id":1,"lesson_id":1,"current_time_seconds":120,"progress_percentage":30,"is_completed":false}]
     *
     * @response {
     *   "message": "Sync completed successfully",
     *   "synced_attempts": 5,
     *   "synced_answers": 200,
     *   "synced_lesson_progress": 3,
     *   "failed_attempts": 0,
     *   "failed_answers": 0
     * }
     */
    public function sync(SyncRequest $request): JsonResponse
    {
        $attempts = $request->input('attempts', []);
        $answers = $request->input('answers', []);
        $lessonProgress = $request->input('lesson_progress', []);

        $syncedAttempts = 0;
        $syncedAnswers = 0;
        $syncedLessonProgress = 0;
        $failedAttempts = 0;
        $failedAnswers = 0;

        // Sync quiz attempts and answers in a transaction
        DB::transaction(function () use ($attempts, $answers, &$syncedAttempts, &$syncedAnswers, &$failedAttempts, &$failedAnswers) {
            // Process attempts
            foreach ($attempts as $attemptData) {
                try {
                    // Check if attempt already synced (prevent double-grading)
                    $existingAttempt = QuizAttempt::where('uuid', $attemptData['uuid'])->first();

                    if ($existingAttempt) {
                        $failedAttempts++;
                        continue;
                    }

                    // Create quiz attempt
                    $attempt = QuizAttempt::create([
                        'uuid' => $attemptData['uuid'],
                        'quiz_id' => $attemptData['quiz_id'] ?? null,
                        'user_id' => $attemptData['user_id'],
                        'exam_type_id' => $attemptData['exam_type_id'] ?? null,
                        'subject_id' => $attemptData['subject_id'],
                        'mock_group_id' => $attemptData['mock_group_id'] ?? null,
                        'exam_year' => $attemptData['exam_year'],
                        'attempt_number' => $attemptData['attempt_number'] ?? 1,
                        'started_at' => $attemptData['started_at'],
                        'completed_at' => $attemptData['completed_at'] ?? null,
                        'time_spent_seconds' => $attemptData['time_taken_seconds'] ?? 0,
                        'time_taken_seconds' => $attemptData['time_taken_seconds'] ?? 0,
                        'total_questions' => $attemptData['total_questions'],
                        'answered_questions' => $attemptData['answered_questions'] ?? $attemptData['total_questions'],
                        'correct_answers' => $attemptData['correct_answers'],
                        'score' => $attemptData['score'] ?? null,
                        'percentage' => $attemptData['percentage'] ?? null,
                        'score_percentage' => $attemptData['score_percentage'] ?? $attemptData['percentage'],
                        'passed' => $attemptData['passed'] ?? false,
                        'status' => $attemptData['status'],
                        'question_order' => $attemptData['question_order'] ?? null,
                        'current_question_index' => $attemptData['current_question_index'] ?? 0,
                    ]);

                    $syncedAttempts++;
                } catch (\Exception $e) {
                    Log::error('Sync attempt creation failed', [
                        'uuid' => $attemptData['uuid'] ?? 'unknown',
                        'user_id' => $attemptData['user_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    $failedAttempts++;
                    continue;
                }
            }

            // Process answers
            foreach ($answers as $answerData) {
                try {
                    // Find the attempt by UUID
                    $attempt = QuizAttempt::where('uuid', $answerData['attempt_uuid'])->first();

                    if (!$attempt) {
                        $failedAnswers++;
                        continue;
                    }

                    // Check if answer already exists (prevent duplicates)
                    $existingAnswer = UserAnswer::where('quiz_attempt_id', $attempt->id)
                        ->where('question_id', $answerData['question_id'])
                        ->first();

                    if ($existingAnswer) {
                        continue; // Skip duplicate
                    }

                    // Create user answer
                    UserAnswer::create([
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $answerData['question_id'],
                        'option_id' => $answerData['option_id'] ?? null,
                        'is_correct' => $answerData['is_correct'],
                        'time_spent_seconds' => $answerData['time_spent_seconds'] ?? 0,
                    ]);

                    $syncedAnswers++;
                } catch (\Exception $e) {
                    $failedAnswers++;
                    continue;
                }
            }
        });

        // Sync lesson progress
        foreach ($lessonProgress as $progressData) {
            try {
                $lesson = Lesson::find($progressData['lesson_id']);

                if (!$lesson) {
                    continue;
                }

                // Update or create user progress
                UserProgress::updateOrCreate(
                    [
                        'user_id' => $progressData['user_id'],
                        'lesson_id' => $progressData['lesson_id'],
                        'type' => 'lesson',
                    ],
                    [
                        'current_time_seconds' => $progressData['current_time_seconds'] ?? 0,
                        'progress_percentage' => $progressData['progress_percentage'] ?? 0,
                        'is_completed' => $progressData['is_completed'] ?? false,
                        'completed_at' => ($progressData['is_completed'] ?? false) ? now() : null,
                        'time_spent_seconds' => $progressData['time_spent_seconds'] ?? 0,
                    ]
                );

                $syncedLessonProgress++;
            } catch (\Exception $e) {
                Log::error('Sync lesson progress failed', [
                    'user_id' => $progressData['user_id'] ?? 'unknown',
                    'lesson_id' => $progressData['lesson_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return new \App\Http\Resources\Api\SyncResponse([
            'synced_attempts' => $syncedAttempts,
            'synced_answers' => $syncedAnswers,
            'synced_lesson_progress' => $syncedLessonProgress,
            'failed_attempts' => $failedAttempts,
            'failed_answers' => $failedAnswers,
        ]);
    }

    /**
     * Get Sync Status
     *
     * Returns the count of unsynced offline data for the authenticated user.
     *
     * @response {
     *   "unsynced_attempts": 3,
     *   "unsynced_answers": 120,
     *   "unsynced_lesson_progress": 1
     * }
     */
    public function status(): JsonResponse
    {
        $user = auth()->user();

        // Count quiz attempts that were created offline (have UUID)
        // These are tracked by the mobile app for sync purposes
        $totalOfflineAttempts = QuizAttempt::where('user_id', $user->id)
            ->whereNotNull('uuid')
            ->count();

        return response()->json([
            'unsynced_attempts' => 0, // Mobile app tracks unsynced data locally
            'unsynced_answers' => 0,
            'unsynced_lesson_progress' => 0,
            'total_offline_attempts' => $totalOfflineAttempts,
        ], 200);
    }
}
