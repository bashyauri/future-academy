<?php

namespace App\Http\Controllers\Practice;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use Illuminate\Http\Request;

class PracticeQuizController extends Controller
{
    /**
     * Autosave endpoint for Alpine.js practice quiz
     * Receives answers from the client and CACHES them (does NOT save to database)
     * Answers are only saved to database on explicit exit or submit
     *
     * This implements the "minimal server involvement" principle:
     * - Server only called every 10 seconds (not per answer)
     * - Cache is updated to prevent data loss on refresh
     * - Database is only written on exit/submit (reducing write load)
     * - All answer feedback shown instantly client-side
     * - No page re-render on autosave
     */
    public function autosave(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'attempt_id' => 'required|integer|exists:quiz_attempts,id',
            'answers' => 'required|array',
            'current_question_index' => 'required|integer',
        ]);

        $attempt = QuizAttempt::findOrFail($validated['attempt_id']);

        // Verify ownership
        if ($attempt->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Prevent saving completed attempts
        if ($attempt->status !== 'in_progress') {
            return response()->json(['error' => 'Quiz is no longer in progress'], 422);
        }

        $answers = $validated['answers'];

        // Update ONLY cache with latest state (NOT database)
        $cacheKey = "practice_attempt_{$attempt->id}";
        cache()->put($cacheKey, [
            'answers' => $answers,
            'position' => $validated['current_question_index'],
            'updated_at' => now(),
        ], now()->addHours(3));

        // Update current position in attempt record (for resume point)
        $attempt->update([
            'current_question_index' => $validated['current_question_index'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Progress cached (saved to database on exit/submit)',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
