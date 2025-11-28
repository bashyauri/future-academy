<?php

namespace App\Livewire\Quizzes;

use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class QuizzesBySubject extends Component
{
    public Subject $subject;
    public ?int $topicFilter = null;
    public string $typeFilter = 'all';

    public function mount($subject)
    {
        $this->subject = Subject::findOrFail($subject);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        // Get topics that have quizzes (using JSON search)
        $topics = Topic::where('subject_id', $this->subject->id)
            ->get()
            ->map(function ($topic) {
                $topic->quizzes_count = DB::table('quizzes')
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('available_from')
                            ->orWhere('available_from', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('available_until')
                            ->orWhere('available_until', '>=', now());
                    })
                    ->whereRaw("JSON_CONTAINS(topic_ids, ?)", [json_encode([$topic->id])])
                    ->count();
                return $topic;
            })
            ->filter(fn($topic) => $topic->quizzes_count > 0)
            ->values();

        // Get quizzes for this subject
        $quizzes = Quiz::query()
            ->active()
            ->available()
            ->whereRaw("JSON_CONTAINS(subject_ids, ?)", [json_encode([$this->subject->id])])
            ->when($this->topicFilter, function ($query) {
                $query->whereRaw("JSON_CONTAINS(topic_ids, ?)", [json_encode([$this->topicFilter])]);
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $query->where('type', $this->typeFilter);
            })
            ->with('creator')
            ->latest()
            ->get()
            ->map(function ($quiz) {
                $stats = app(\App\Services\QuizGeneratorService::class)
                    ->getUserStats($quiz, auth()->user());
                $quiz->user_stats = $stats;
                $quiz->can_attempt = $quiz->canUserAttempt(auth()->user());

                // Get subject and topic names from JSON arrays
                if ($quiz->subject_ids) {
                    $quiz->subject_names = Subject::whereIn('id', $quiz->subject_ids)->pluck('name');
                }
                if ($quiz->topic_ids) {
                    $quiz->topic_names = Topic::whereIn('id', $quiz->topic_ids)->pluck('name');
                }

                return $quiz;
            });

        return view('livewire.quizzes.quizzes-by-subject', [
            'quizzes' => $quizzes,
            'topics' => $topics,
        ]);
    }

    public function startQuiz($quizId)
    {
        return redirect()->route('quiz.take', $quizId);
    }
}
