<?php

namespace App\Http\Controllers;

use App\Models\UserProgress;
use App\Models\VideoProgress;
use Illuminate\Http\Request;

class VideoProgressController extends Controller
{
    public function storeProgress(Request $request)
    {
        $validated = $request->validate([
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'watched_seconds' => ['required', 'integer', 'min:0'],
            'total_seconds' => ['required', 'integer', 'min:1'],
            'percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $userId = $request->user()->id;
        $percentage = $validated['percentage'] ?? (int) round(($validated['watched_seconds'] / $validated['total_seconds']) * 100);
        $percentage = max(0, min(100, $percentage));

        VideoProgress::updateOrCreate(
            [
                'user_id' => $userId,
                'lesson_id' => $validated['lesson_id'],
            ],
            [
                'watch_time' => $validated['watched_seconds'],
                'percentage' => $percentage,
                'completed' => $percentage >= 90,
            ]
        );

        UserProgress::updateOrCreate(
            [
                'user_id' => $userId,
                'lesson_id' => $validated['lesson_id'],
                'type' => 'lesson',
            ],
            [
                'progress_percentage' => $percentage,
                'time_spent_seconds' => $validated['watched_seconds'],
                'current_time_seconds' => $validated['watched_seconds'],
            ]
        );

        return response()->json([
            'success' => true,
            'percentage' => $percentage,
        ]);
    }

    public function markCompletion(Request $request)
    {
        $validated = $request->validate([
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'watched_percentage' => ['required', 'integer', 'min:90', 'max:100'],
        ]);

        $userId = $request->user()->id;

        VideoProgress::updateOrCreate(
            [
                'user_id' => $userId,
                'lesson_id' => $validated['lesson_id'],
            ],
            [
                'percentage' => 100,
                'completed' => true,
            ]
        );

        $userProgress = UserProgress::firstOrCreate(
            [
                'user_id' => $userId,
                'lesson_id' => $validated['lesson_id'],
                'type' => 'lesson',
            ],
            [
                'progress_percentage' => 0,
                'time_spent_seconds' => 0,
                'current_time_seconds' => 0,
                'started_at' => now(),
            ]
        );

        $userProgress->markCompleted();

        return response()->json([
            'success' => true,
        ]);
    }
}
