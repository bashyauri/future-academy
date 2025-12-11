<?php

namespace App\Livewire\Practice;

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
        public $questionsPerSubject = 40;
        public $showAnswersImmediately = false;
        public $showExplanations = false;
        public $shuffleQuestions = true;
        public $showReview = false;    public function mount()
    {
        // Get params from URL query string
        $this->year = request()->query('year') ?? 2023;
        $subjectsParam = request()->query('subjects');
        $this->timeLimit = (int)(request()->query('timeLimit') ?? 180);
        $this->questionsPerSubject = (int)(request()->query('questionsPerSubject') ?? 40);
        $this->showAnswersImmediately = request()->query('showAnswers') === '1';
        $this->showExplanations = request()->query('showExplanations') === '1';
        $this->shuffleQuestions = request()->query('shuffle') !== '0';
        
        if ($subjectsParam) {
            $this->subjectIds = array_filter(explode(',', $subjectsParam));
        }
        
        if (empty($this->subjectIds)) {
            return redirect()->route('practice.jamb.setup');
        }
        
        $this->timeRemaining = $this->timeLimit * 60; // Convert to seconds

        // Load subjects and questions
        $this->subjectsData = Subject::whereIn('id', $this->subjectIds)->get();
        
        // Update subjectIds to match the order of loaded subjects
        $this->subjectIds = $this->subjectsData->pluck('id')->toArray();
        
        foreach ($this->subjectIds as $subjectId) {
            $this->questionsBySubject[$subjectId] = Question::where('exam_type_id', function($query) {
                    $query->select('id')
                        ->from('exam_types')
                        ->where('slug', 'jamb');
                })
                ->where('subject_id', $subjectId)
                ->where('exam_year', $this->year)
                ->with('options')
                ->inRandomOrder()
                ->take(40)
                ->get();
        }

        // Initialize user answers
        foreach ($this->subjectIds as $subjectId) {
            $this->userAnswers[$subjectId] = array_fill(0, 40, null);
        }
    }

    #[On('timer-ended')]
    public function handleTimerEnd()
    {
        $this->submitQuiz();
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
    }

    public function nextQuestion()
    {
        if ($this->currentQuestionIndex < 39) {
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
            $this->currentQuestionIndex = 39;
        }
    }

    public function jumpToQuestion($subjectIndex, $questionIndex)
    {
        $this->currentSubjectIndex = $subjectIndex;
        $this->currentQuestionIndex = $questionIndex;
    }

    public function submitQuiz()
    {
        $this->showResults = true;
        $this->saveAttempt();
    }

    public function saveAttempt()
    {
        // Create quiz attempt first with timestamps to satisfy non-nullable columns
        $quizAttempt = QuizAttempt::create([
            'user_id' => auth()->id(),
            'exam_type_id' => \App\Models\ExamType::where('slug', 'jamb')->first()->id,
            'exam_year' => $this->year,
            'score' => 0, // placeholder, updated later
            'total_questions' => count($this->subjectIds) * 40,
            'time_taken_seconds' => ($this->timeLimit * 60) - $this->timeRemaining,
            'percentage' => 0, // placeholder, updated later
            'started_at' => now(),
            'completed_at' => now(),
            'answered_questions' => 0,
            'correct_answers' => 0,
            'score_percentage' => 0,
            'status' => 'completed',
        ]);

        $this->quizAttemptId = $quizAttempt->id;

        $scores = [];
        $totalScore = 0;
        $totalQuestions = 0;
        $answeredCount = 0;

        foreach ($this->subjectIds as $subjectId) {
            $score = 0;
            foreach ($this->questionsBySubject[$subjectId] as $index => $question) {
                $userAnswer = $this->userAnswers[$subjectId][$index] ?? null;
                if ($userAnswer) {
                    $answeredCount++;
                    $correctOption = $question->options->firstWhere('is_correct', true);
                    $isCorrect = $correctOption && $correctOption->id == $userAnswer;
                    
                    if ($isCorrect) {
                        $score++;
                    }

                    // Save user answer with quiz attempt ID
                    UserAnswer::create([
                        'user_id' => auth()->id(),
                        'quiz_attempt_id' => $quizAttempt->id,
                        'question_id' => $question->id,
                        'option_id' => $userAnswer,
                        'is_correct' => $isCorrect,
                    ]);
                }
            }
            $scores[$subjectId] = $score;
            $totalScore += $score;
            $totalQuestions += 40;
        }

        // Update quiz attempt with calculated score and metadata
        $percentage = ($totalScore / $totalQuestions) * 100;
        $quizAttempt->update([
            'score' => $totalScore,
            'percentage' => $percentage,
            'answered_questions' => $answeredCount,
            'correct_answers' => $totalScore,
            'score_percentage' => $percentage,
            'time_spent_seconds' => ($this->timeLimit * 60) - $this->timeRemaining,
            'completed_at' => now(),
        ]);
    }

    public function getScoresBySubject()
    {
        $scores = [];
        foreach ($this->subjectIds as $subjectId) {
            $score = 0;
            foreach ($this->questionsBySubject[$subjectId] as $index => $question) {
                $userAnswer = $this->userAnswers[$subjectId][$index] ?? null;
                if ($userAnswer) {
                    $correctOption = $question->options->firstWhere('is_correct', true);
                    if ($correctOption && $correctOption->id == $userAnswer) {
                        $score++;
                    }
                }
            }
            $scores[$subjectId] = $score;
        }
        return $scores;
    }

    public function toggleReview()
    {
        $this->showReview = !$this->showReview;
    }

    public function render()
    {
        return view('livewire.practice.jamb-quiz');
    }
}

