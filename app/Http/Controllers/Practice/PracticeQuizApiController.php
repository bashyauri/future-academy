<?php

namespace App\Http\Controllers\Practice;

use App\Http\Controllers\Controller;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\UserAnswer;
use Illuminate\Http\Request;

class PracticeQuizApiController extends Controller
{
    /**
     * Start a new quiz attempt or resume existing
     */
    public function startQuiz(Request $request)
    {
        $validated = $request->validate([
            'exam_type' => 'nullable|exists:exam_types,id',
            'subject' => 'required|exists:subjects,id',
            'year' => 'nullable|integer',
            'shuffle' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:200',
            'time' => 'nullable|integer|min:1|max:300',
        ]);

        // Check for existing in-progress attempt
        $activeAttempt = QuizAttempt::where('user_id', auth()->id())
            ->where('exam_type_id', $validated['exam_type'] ?? null)
            ->where('subject_id', $validated['subject'])
            ->where('exam_year', $validated['year'] ?? null)
            ->where('status', 'in_progress')
            ->latest('created_at')
            ->first();

        if ($activeAttempt) {
            return $this->loadAttempt($activeAttempt->id);
        }

        // Get question IDs first
        $query = Question::query()
            ->where('subject_id', $validated['subject'])
            ->where('is_active', true)
            ->where('status', 'approved')
            ->where('is_mock', false);

        if ($validated['exam_type'] ?? null) {
            $query->where('exam_type_id', $validated['exam_type']);
        }

        if ($validated['year'] ?? null) {
            $query->where(function ($q) use ($validated) {
                $q->where('exam_year', $validated['year'])
                  ->orWhere(function ($sub) use ($validated) {
                      $sub->whereNull('exam_year')->where('year', $validated['year']);
                  });
            });
        }

        $totalQuestions = $query->count();

        if ($validated['limit'] ?? null) {
            $totalQuestions = min($validated['limit'], $totalQuestions);
        }

        $questionIds = $query
            ->when($validated['limit'] ?? null, fn($q) => $q->limit($validated['limit']))
            ->pluck('id')
            ->toArray();

        // Shuffle if requested
        if ($validated['shuffle'] ?? false) {
            shuffle($questionIds);
        }

        // Create new attempt
        $attempt = QuizAttempt::create([
            'user_id' => auth()->id(),
            'exam_type_id' => $validated['exam_type'] ?? null,
            'subject_id' => $validated['subject'],
            'exam_year' => $validated['year'] ?? null,
            'total_questions' => $totalQuestions,
            'correct_answers' => 0,
            'score_percentage' => 0,
            'status' => 'in_progress',
            'started_at' => now(),
            'time_taken_seconds' => 0,
            'question_order' => $questionIds,
            'current_question_index' => 0,
        ]);

        // Load first 5 questions
        $firstBatch = array_slice($questionIds, 0, 5);
        $questions = $this->loadQuestionsBatch($firstBatch, $validated['shuffle'] ?? false);

        return response()->json([
            'success' => true,
            'attempt_id' => $attempt->id,
            'total_questions' => $totalQuestions,
            'all_question_ids' => $questionIds,
            'questions' => $questions,
            'loaded_up_to_index' => min(4, $totalQuestions - 1),
            'user_answers' => array_fill(0, $totalQuestions, null),
            'current_question_index' => 0,
            'time_limit' => $validated['time'] ?? null,
            'started_at' => $attempt->started_at->toIso8601String(),
        ]);
    }

    /**
     * Load an existing attempt
     */
    public function loadAttempt($attemptId)
    {
        $attempt = QuizAttempt::findOrFail($attemptId);

        // Verify ownership
        if ($attempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Try cache first
        $cacheKey = "practice_attempt_{$attempt->id}";
        $cached = cache()->get($cacheKey);

        if ($cached) {
            return response()->json([
                'success' => true,
                'attempt_id' => $attempt->id,
                'total_questions' => $cached['total_questions'],
                'all_question_ids' => $cached['all_question_ids'],
                'questions' => $cached['questions'],
                'loaded_up_to_index' => $cached['loaded_up_to_index'],
                'user_answers' => $cached['answers'],
                'current_question_index' => $cached['position'],
                'time_limit' => $attempt->time_limit ?? null,
                'started_at' => $attempt->started_at->toIso8601String(),
                'status' => $attempt->status,
            ]);
        }

        // Load from database
        $questionIds = $attempt->question_order ?? [];
        $totalQuestions = count($questionIds);

        // Load first 5 questions
        $firstBatch = array_slice($questionIds, 0, 5);
        $questions = $this->loadQuestionsBatch($firstBatch, false);

        // Load existing answers
        $userAnswers = array_fill(0, $totalQuestions, null);
        $answers = UserAnswer::where('quiz_attempt_id', $attempt->id)->get();

        foreach ($answers as $answer) {
            $index = array_search($answer->question_id, $questionIds);
            if ($index !== false) {
                $userAnswers[$index] = $answer->option_id;
            }
        }

        return response()->json([
            'success' => true,
            'attempt_id' => $attempt->id,
            'total_questions' => $totalQuestions,
            'all_question_ids' => $questionIds,
            'questions' => $questions,
            'loaded_up_to_index' => min(4, $totalQuestions - 1),
            'user_answers' => $userAnswers,
            'current_question_index' => $attempt->current_question_index ?? 0,
            'time_limit' => $attempt->time_limit ?? null,
            'started_at' => $attempt->started_at->toIso8601String(),
            'status' => $attempt->status,
        ]);
    }

    /**
     * Load a batch of questions by IDs
     */
    public function loadBatch(Request $request)
    {
        $validated = $request->validate([
            'question_ids' => 'required|array',
            'shuffle' => 'nullable|boolean',
        ]);

        $questions = $this->loadQuestionsBatch($validated['question_ids'], $validated['shuffle'] ?? false);

        return response()->json([
            'success' => true,
            'questions' => $questions,
        ]);
    }

    /**
     * Load a batch of questions by IDs (helper method)
     */
    private function loadQuestionsBatch(array $questionIds, bool $shuffle = false)
    {
        if (empty($questionIds)) {
            return [];
        }

        $questions = Question::whereIn('id', $questionIds)
            ->select('id', 'question_text', 'question_image', 'difficulty', 'explanation')
            ->with('options:id,question_id,option_text,option_image,is_correct')
            ->get();

        // Sort by provided order
        $questions = $questions->sortBy(function ($q) use ($questionIds) {
            return array_search($q->id, $questionIds);
        });

        return $questions->map(function ($question) use ($shuffle) {
            $options = $question->options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'option_image' => $option->option_image,
                    'is_correct' => $option->is_correct,
                ];
            })->toArray();

            if ($shuffle) {
                shuffle($options);
            }

            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_image' => $question->question_image,
                'explanation' => $question->explanation,
                'options' => $options,
            ];
        })->values()->toArray();
    }

    /**
     * Save answers (autosave endpoint)
     */
    public function saveAnswers(Request $request)
    {
        $validated = $request->validate([
            'attempt_id' => 'required|exists:quiz_attempts,id',
            'answers' => 'required|array',
            'current_question_index' => 'required|integer|min:0',
            'all_question_ids' => 'required|array',
            'questions' => 'required|array',
            'loaded_up_to_index' => 'required|integer',
        ]);

        $attempt = QuizAttempt::findOrFail($validated['attempt_id']);

        // Verify ownership
        if ($attempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Update cache
        $cacheKey = "practice_attempt_{$attempt->id}";
        cache()->put($cacheKey, [
            'questions' => $validated['questions'],
            'answers' => $validated['answers'],
            'position' => $validated['current_question_index'],
            'all_question_ids' => $validated['all_question_ids'],
            'loaded_up_to_index' => $validated['loaded_up_to_index'],
            'total_questions' => count($validated['all_question_ids']),
        ], now()->addHours(3));

        // Update current position
        $attempt->update([
            'current_question_index' => $validated['current_question_index'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Progress saved',
        ]);
    }

    /**
     * Submit quiz (final submission)
     */
    public function submitQuiz(Request $request)
    {
        $validated = $request->validate([
            'attempt_id' => 'required|exists:quiz_attempts,id',
            'answers' => 'required|array',
            'all_question_ids' => 'required|array',
        ]);

        $attempt = QuizAttempt::findOrFail($validated['attempt_id']);

        // Verify ownership
        if ($attempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Load all questions to validate answers
        $questions = Question::whereIn('id', $validated['all_question_ids'])
            ->with('options')
            ->get()
            ->keyBy('id');

        $correctCount = 0;

        // Save all answers
        foreach ($validated['all_question_ids'] as $index => $questionId) {
            $userAnswer = $validated['answers'][$index] ?? null;

            if ($userAnswer && isset($questions[$questionId])) {
                $question = $questions[$questionId];
                $selectedOption = $question->options->firstWhere('id', $userAnswer);
                $isCorrect = $selectedOption && $selectedOption->is_correct;

                if ($isCorrect) {
                    $correctCount++;
                }

                UserAnswer::updateOrCreate(
                    [
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $questionId,
                    ],
                    [
                        'user_id' => auth()->id(),
                        'option_id' => $userAnswer,
                        'is_correct' => $isCorrect,
                    ]
                );
            }
        }

        // Update attempt
        $timeSpent = $attempt->started_at->diffInSeconds(now());
        $totalQuestions = count($validated['all_question_ids']);

        $attempt->update([
            'correct_answers' => $correctCount,
            'score' => $correctCount,
            'score_percentage' => $totalQuestions > 0 ? ($correctCount / $totalQuestions) * 100 : 0,
            'time_taken_seconds' => $timeSpent,
            'total_questions' => $totalQuestions,
            'completed_at' => now(),
            'status' => 'completed',
        ]);

        // Clear cache
        cache()->forget("practice_attempt_{$attempt->id}");

        return response()->json([
            'success' => true,
            'score' => $correctCount,
            'total' => $totalQuestions,
            'percentage' => $attempt->score_percentage,
            'time_spent' => $timeSpent,
        ]);
    }

    /**
     * Exit quiz (save and exit)
     */
    public function exitQuiz(Request $request)
    {
        $validated = $request->validate([
            'attempt_id' => 'required|exists:quiz_attempts,id',
            'answers' => 'required|array',
            'all_question_ids' => 'required|array',
            'current_question_index' => 'required|integer',
        ]);

        $attempt = QuizAttempt::findOrFail($validated['attempt_id']);

        // Verify ownership
        if ($attempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Load questions to validate answers
        $questions = Question::whereIn('id', $validated['all_question_ids'])
            ->with('options')
            ->get()
            ->keyBy('id');

        // Save only answered questions
        foreach ($validated['all_question_ids'] as $index => $questionId) {
            $userAnswer = $validated['answers'][$index] ?? null;

            if ($userAnswer && isset($questions[$questionId])) {
                $question = $questions[$questionId];
                $selectedOption = $question->options->firstWhere('id', $userAnswer);
                $isCorrect = $selectedOption && $selectedOption->is_correct;

                UserAnswer::updateOrCreate(
                    [
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $questionId,
                    ],
                    [
                        'user_id' => auth()->id(),
                        'option_id' => $userAnswer,
                        'is_correct' => $isCorrect,
                    ]
                );
            }
        }

        // Update current position
        $attempt->update([
            'current_question_index' => $validated['current_question_index'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Progress saved. You can resume later.',
        ]);
    }
}
