<?php

namespace App\Livewire\Practice;

use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PracticeQuiz extends Component
{
    #[Url]
    public $exam_type;

    #[Url]
    public $subject;

    #[Url]
    public $year;
    
    public $questions = [];
    public $currentQuestionIndex = 0;
    public $userAnswers = [];
    public $timeStarted;
    public $showResults = false;
    public $score = 0;
    public $totalQuestions = 0;
    
    public function mount()
    {
        $this->timeStarted = now();
        
        // Load questions
        $this->questions = Question::where('exam_type_id', $this->exam_type)
            ->where('subject_id', $this->subject)
            ->where('exam_year', $this->year)
            ->with('options')
            ->get()
            ->toArray();
        
        $this->totalQuestions = count($this->questions);
        
        // Initialize user answers array
        $this->userAnswers = array_fill(0, $this->totalQuestions, null);
    }
    
    public function selectAnswer($optionId)
    {
        $this->userAnswers[$this->currentQuestionIndex] = $optionId;
    }
    
    public function nextQuestion()
    {
        if ($this->currentQuestionIndex < $this->totalQuestions - 1) {
            $this->currentQuestionIndex++;
        }
    }
    
    public function previousQuestion()
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        }
    }
    
    public function jumpToQuestion($index)
    {
        if ($index >= 0 && $index < $this->totalQuestions) {
            $this->currentQuestionIndex = $index;
        }
    }
    
    public function submitQuiz()
    {
        $this->calculateScore();
        $this->showResults = true;
        $this->saveAttempt();
    }
    
    private function calculateScore()
    {
        $this->score = 0;
        
        foreach ($this->userAnswers as $index => $selectedOptionId) {
            if ($selectedOptionId && isset($this->questions[$index])) {
                $question = $this->questions[$index];
                
                // Find the selected option
                $selectedOption = collect($question['options'])
                    ->firstWhere('id', $selectedOptionId);
                
                if ($selectedOption && $selectedOption['is_correct']) {
                    $this->score++;
                }
            }
        }
    }
    
    private function saveAttempt()
    {
        $user = auth()->user();
        
        // Create attempt record
        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => null, // This is a practice session
            'exam_type_id' => $this->exam_type,
            'subject_id' => $this->subject,
            'exam_year' => $this->year,
            'total_questions' => $this->totalQuestions,
            'correct_answers' => $this->score,
            'score_percentage' => ($this->score / $this->totalQuestions) * 100,
            'status' => 'completed',
            'started_at' => $this->timeStarted,
            'completed_at' => now(),
            'time_taken_seconds' => $this->timeStarted->diffInSeconds(now()),
        ]);
        
        // Save individual answers
        foreach ($this->userAnswers as $index => $selectedOptionId) {
            if (isset($this->questions[$index])) {
                UserAnswer::create([
                    'user_id' => $user->id,
                    'question_id' => $this->questions[$index]['id'],
                    'selected_option_id' => $selectedOptionId,
                    'quiz_attempt_id' => $attempt->id,
                ]);
            }
        }
    }
    
    public function render()
    {
        return view('livewire.practice.practice-quiz');
    }
}

