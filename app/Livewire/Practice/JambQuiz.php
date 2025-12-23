<?php

namespace App\Livewire\Practice;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\UserAnswer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class JambQuiz extends Component
{
    public $year = null;
    public $subjects = null;
    public $subjectIds = [];
    public $subjectsData = [];
    public $questionsBySubject = [];
    public $currentSubjectIndex = 0;
    public $currentQuestionIndex = 0;
    public $userAnswers = [];
    public $showResults = false;
    public $quizAttemptId = null;
    public $timeRemaining;
    public $timeLimit = 180;
    public $timerStartedAt;
    public $questionsPerSubject = 40;
    public $showAnswersImmediately = false;
    public $showExplanations = false;
    public $shuffleQuestions = true;
    public $showReview = false;
    public ?QuizAttempt $attempt = null;
    public array $questionOrder = [];

    public function mount()
    {
        // Get params from URL query string
        $this->year = request()->query('year') ?? 2023;
        $subjectsParam = request()->query('subjects');
        $this->timeLimit = (int)(request()->query('timeLimit') ?? 180);
        $this->questionsPerSubject = (int)(request()->query('questionsPerSubject') ?? 40);
        $this->shuffleQuestions = request()->query('shuffle') === '1';
        $this->showResults = request()->boolean('results', false);
        $this->quizAttemptId = request()->query('attempt');

        if ($subjectsParam) {
            $this->subjectIds = array_filter(explode(',', $subjectsParam));
        }

        if (empty($this->subjectIds)) {
            return redirect()->route('practice.jamb.setup');
        }

        // Initialize timer defaults
        if (!$this->timerStartedAt) {
            $this->timerStartedAt = now();
        }
        if (!$this->timeRemaining) {
            $this->timeRemaining = $this->timeLimit * 60; // Convert to seconds
        }

        $examType = ExamType::where('slug', 'jamb')->first();

        // For authenticated users, persist attempts and timer across refreshes
        if (auth()->check()) {
            $attemptFromQuery = $this->quizAttemptId ? QuizAttempt::find($this->quizAttemptId) : null;

            // If showing results, always use the provided attempt (completed or in_progress)
            if ($this->showResults && $attemptFromQuery) {
                $activeAttempt = $attemptFromQuery;
            } else {
                // Otherwise, find in_progress attempt
                $activeAttempt = ($attemptFromQuery && $attemptFromQuery->status === 'in_progress')
                    ? $attemptFromQuery
                    : $this->findActiveAttempt($examType);
            }

            if ($activeAttempt && $this->attemptMatchesContext($activeAttempt)) {
                $this->hydrateFromAttempt($activeAttempt);
                return;
            }

            // Only start new attempt if we're NOT showing results
            if (!$this->showResults) {
                $this->startNewAttempt($examType);
            } else {
                // If showing results but no valid attempt, redirect back to setup
                return redirect()->route('practice.jamb.setup');
            }
            return;
        }

        // Fallback for guests: generate a fresh in-memory session (not persistent)
        $this->subjectsData = Subject::whereIn('id', $this->subjectIds)->get();
        $this->subjectIds = $this->subjectsData->pluck('id')->toArray();
        $this->generateQuestionsForSubjects();
        $this->initializeUserAnswers();
    }

    public function getCurrentSubjectId()
    {
        return $this->subjectsData[$this->currentSubjectIndex]->id;
    }

    public function getCurrentQuestions()
    {
        return $this->questionsBySubject[$this->getCurrentSubjectId()];
    }

    public function getCurrentQuestion()
    {
        return $this->getCurrentQuestions()[$this->currentQuestionIndex];
    }

    public function switchSubject($index)
    {
        $this->currentSubjectIndex = $index;
        $this->currentQuestionIndex = 0;
    }

    public function selectAnswer($optionId)
    {
        $currentSubjectId = $this->getCurrentSubjectId();
        $this->userAnswers[$currentSubjectId][$this->currentQuestionIndex] = $optionId;
        // Instant feedback - user stays on question to see answer and explanation

        if (auth()->check() && $this->attempt) {
            $question = $this->getCurrentQuestion();
            if ($question) {
                $isCorrect = (bool) ($question->options->firstWhere('id', $optionId)?->is_correct);
                $this->persistAnswer($question->id, $optionId, $isCorrect);
            }
        }
    }

    public function nextQuestion()
    {
        if ($this->currentQuestionIndex < ($this->questionsPerSubject - 1)) {
            $this->currentQuestionIndex++;
        } elseif ($this->currentSubjectIndex < count($this->subjectIds) - 1) {
            $this->currentSubjectIndex++;
            $this->currentQuestionIndex = 0;
        }
    }

    public function previousQuestion()
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        } elseif ($this->currentSubjectIndex > 0) {
            $this->currentSubjectIndex--;
            $this->currentQuestionIndex = $this->questionsPerSubject - 1;
        }
    }

    public function jumpToQuestion($subjectIndex, $questionIndex)
    {
        $this->currentSubjectIndex = $subjectIndex;
        $this->currentQuestionIndex = $questionIndex;
    }

    #[On('timer-ended')]
    public function handleTimerEnd()
    {
        $this->timeRemaining = 0;
        $this->submitQuiz();
    }

    public function submitQuiz()
    {
        // Persist attempt and then redirect to results route to avoid morph issues
        if (auth()->check()) {
            try {
                $this->saveAttempt();
            } catch (\Throwable $e) {
                \Log::error('JambQuiz saveAttempt failed: '.$e->getMessage(), ['exception' => $e]);
            }
        }

        $params = [
            'year' => $this->year,
            'subjects' => implode(',', $this->subjectIds),
            'timeLimit' => $this->timeLimit,
            'questionsPerSubject' => $this->questionsPerSubject,
            'shuffle' => $this->shuffleQuestions ? '1' : '0',
            'results' => 1,
        ];
        if ($this->quizAttemptId) {
            $params['attempt'] = $this->quizAttemptId;
        }

        return redirect()->route('practice.jamb.quiz', $params);
    }

    public function saveAttempt()
    {
        if (!auth()->check()) {
            return;
        }

        // Ensure we have an attempt to finalize
        if (!$this->attempt) {
            $this->startNewAttempt(ExamType::where('slug', 'jamb')->first());
        }

        if (!$this->attempt) {
            return;
        }

        $timeSpent = $this->computeTimeSpent();
        $totalQuestions = count($this->subjectIds) * $this->questionsPerSubject;
        $answeredCount = 0;
        $correctCount = 0;

        foreach ($this->subjectIds as $subjectId) {
            foreach ($this->questionsBySubject[$subjectId] as $index => $question) {
                $userAnswer = $this->userAnswers[$subjectId][$index] ?? null;
                if ($userAnswer) {
                    $answeredCount++;
                    $isCorrect = (bool) ($question->options->firstWhere('id', $userAnswer)?->is_correct);
                    if ($isCorrect) {
                        $correctCount++;
                    }
                    $this->persistAnswer($question->id, $userAnswer, $isCorrect);
                }
            }
        }

        $percentage = ($correctCount / max(1, $totalQuestions)) * 100;

        $this->attempt->update([
            'score' => $correctCount,
            'percentage' => $percentage,
            'answered_questions' => $answeredCount,
            'correct_answers' => $correctCount,
            'score_percentage' => $percentage,
            'time_spent_seconds' => $timeSpent,
            'time_taken_seconds' => $timeSpent,
            'completed_at' => now(),
            'status' => 'completed',
        ]);
    }

    public function toggleReview()
    {
        $this->showReview = !$this->showReview;
    }

    public function getScoresBySubject()
    {
        // Initialize scores for all subjects
        $scores = array_fill_keys($this->subjectIds, 0);

        // If we have a persisted attempt ID, load scores from DB
        if ($this->quizAttemptId) {
            $answers = UserAnswer::where('quiz_attempt_id', $this->quizAttemptId)
                ->where('is_correct', true)
                ->with(['question:id,subject_id'])
                ->get();

            foreach ($answers as $answer) {
                $subjectId = $answer->question->subject_id ?? null;
                if ($subjectId && isset($scores[$subjectId])) {
                    $scores[$subjectId]++;
                }
            }
            return $scores;
        }

        // Fallback to in-memory computation
        foreach ($this->subjectIds as $subjectId) {
            $score = 0;
            if (isset($this->questionsBySubject[$subjectId])) {
                foreach ($this->questionsBySubject[$subjectId] as $index => $question) {
                    $userAnswer = $this->userAnswers[$subjectId][$index] ?? null;
                    if ($userAnswer) {
                        $correctOption = $question->options->firstWhere('is_correct', true);
                        if ($correctOption && $correctOption->id == $userAnswer) {
                            $score++;
                        }
                    }
                }
            }
            $scores[$subjectId] = $score;
        }
        return $scores;
    }

    private function computeTimeSpent(): int
    {
        if (!$this->timerStartedAt) {
            return 0;
        }

        $elapsed = now()->diffInSeconds($this->timerStartedAt);
        return max(0, min($elapsed, $this->timeLimit * 60));
    }

    private function computeRemainingTime(): int
    {
        $durationSeconds = $this->timeLimit * 60;
        return max(0, $durationSeconds - $this->computeTimeSpent());
    }

    private function findActiveAttempt(?ExamType $examType): ?QuizAttempt
    {
        if (!$examType) {
            return null;
        }

        return QuizAttempt::where('user_id', auth()->id())
            ->where('exam_type_id', $examType->id)
            ->where('exam_year', $this->year)
            ->where('status', 'in_progress')
            ->latest('created_at')
            ->first();
    }

    private function attemptMatchesContext(QuizAttempt $attempt): bool
    {
        if ((int) $attempt->exam_year !== (int) $this->year) {
            return false;
        }

        $order = $attempt->question_order ?? [];
        if (empty($order)) {
            return false;
        }

        $subjectsFromAttempt = array_keys($order);
        $requestedSubjects = array_map('intval', $this->subjectIds);

        sort($subjectsFromAttempt);
        sort($requestedSubjects);

        if ($subjectsFromAttempt !== $requestedSubjects) {
            return false;
        }

        foreach ($order as $subjectId => $questionIds) {
            if (count($questionIds) !== $this->questionsPerSubject) {
                return false;
            }
        }

        return true;
    }

    private function hydrateFromAttempt(QuizAttempt $attempt): void
    {
        $this->attempt = $attempt;
        $this->quizAttemptId = $attempt->id;
        $this->questionOrder = $attempt->question_order ?? [];
        $this->timerStartedAt = $attempt->started_at;
        $this->timeRemaining = $this->computeRemainingTime();

        // Preserve subject order from question_order
        $this->subjectIds = array_map('intval', array_keys($this->questionOrder));
        $orderMap = array_flip($this->subjectIds);
        $this->subjectsData = Subject::whereIn('id', $this->subjectIds)->get()
            ->sortBy(function ($subject) use ($orderMap) {
                return $orderMap[$subject->id] ?? 0;
            })
            ->values();

        $this->generateQuestionsForSubjects($this->questionOrder);
        $this->initializeUserAnswers();

        // Load saved answers and map them back into the arrays
        $answers = UserAnswer::where('quiz_attempt_id', $attempt->id)
            ->with(['question:id,subject_id'])
            ->get();

        foreach ($answers as $answer) {
            $questionId = $answer->question_id;
            $subjectId = $answer->question?->subject_id;
            if (!$subjectId || !isset($this->questionOrder[$subjectId])) {
                continue;
            }

            $index = array_search($questionId, $this->questionOrder[$subjectId], true);
            if ($index !== false) {
                $this->userAnswers[$subjectId][$index] = $answer->option_id;
            }
        }

        // If timer already expired, submit immediately
        if ($this->timeRemaining <= 0) {
            $this->handleTimerEnd();
        }
    }

    private function startNewAttempt(?ExamType $examType): void
    {
        $this->subjectsData = Subject::whereIn('id', $this->subjectIds)->get();
        $this->subjectIds = $this->subjectsData->pluck('id')->toArray();

        $this->generateQuestionsForSubjects();
        $this->initializeUserAnswers();

        $this->attempt = QuizAttempt::create([
            'user_id' => auth()->id(),
            'exam_type_id' => $examType?->id,
            'exam_year' => $this->year,
            'score' => 0,
            'total_questions' => count($this->subjectIds) * $this->questionsPerSubject,
            'time_taken_seconds' => 0,
            'time_spent_seconds' => 0,
            'percentage' => 0,
            'started_at' => now(),
            'completed_at' => null,
            'answered_questions' => 0,
            'correct_answers' => 0,
            'score_percentage' => 0,
            'status' => 'in_progress',
            'question_order' => $this->questionOrder,
        ]);

        $this->quizAttemptId = $this->attempt->id;
        $this->timerStartedAt = $this->attempt->started_at;
        $this->timeRemaining = $this->computeRemainingTime();
    }

    private function generateQuestionsForSubjects(?array $existingOrder = null): void
    {
        $this->questionsBySubject = [];
        $this->questionOrder = [];

        $examTypeId = ExamType::where('slug', 'jamb')->value('id');

        foreach ($this->subjectIds as $subjectId) {
            if ($existingOrder && isset($existingOrder[$subjectId])) {
                $questionIds = $existingOrder[$subjectId];
                $questions = Question::whereIn('id', $questionIds)
                    ->with('options')
                    ->get()
                    ->sortBy(function ($question) use ($questionIds) {
                        return array_search($question->id, $questionIds);
                    })
                    ->values();

                $this->questionsBySubject[$subjectId] = $questions;
                $this->questionOrder[$subjectId] = $questionIds;
                continue;
            }

            $query = Question::where('exam_type_id', $examTypeId)
                ->where('subject_id', $subjectId)
                ->where('exam_year', $this->year)
                ->with('options');

            if ($this->shuffleQuestions) {
                $query->inRandomOrder();
            }

            $questions = $query->take($this->questionsPerSubject)->get();

            $this->questionsBySubject[$subjectId] = $questions;
            $this->questionOrder[$subjectId] = $questions->pluck('id')->toArray();
        }
    }

    private function initializeUserAnswers(): void
    {
        $this->userAnswers = [];
        foreach ($this->subjectIds as $subjectId) {
            $count = $this->questionsPerSubject;
            if (isset($this->questionOrder[$subjectId])) {
                $count = count($this->questionOrder[$subjectId]);
            }
            $this->userAnswers[$subjectId] = array_fill(0, $count, null);
        }
    }

    private function persistAnswer(int $questionId, int $optionId, ?bool $isCorrect = null): void
    {
        if (!$this->attempt) {
            return;
        }

        if ($isCorrect === null) {
            $question = $this->findQuestionById($questionId);
            $isCorrect = (bool) ($question?->options->firstWhere('id', $optionId)?->is_correct);
        }

        UserAnswer::updateOrCreate(
            [
                'quiz_attempt_id' => $this->attempt->id,
                'question_id' => $questionId,
            ],
            [
                'option_id' => $optionId,
                'is_correct' => $isCorrect,
            ]
        );
    }

    private function findQuestionById(int $questionId): ?Question
    {
        foreach ($this->questionsBySubject as $questions) {
            foreach ($questions as $question) {
                if ((int) $question->id === (int) $questionId) {
                    return $question;
                }
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.practice.jamb-quiz');
    }
}

