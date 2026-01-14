<?php

namespace App\Livewire\Quizzes;

use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SubjectsList extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        $subjects = Subject::where('is_active', true)
            ->get()
            ->map(function ($subject) {
                // Count quizzes that have this subject_id in their subject_ids JSON array
                $subject->quizzes_count = DB::table('quizzes')
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('available_from')
                            ->orWhere('available_from', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('available_until')
                            ->orWhere('available_until', '>=', now());
                    })
                    ->whereRaw("JSON_CONTAINS(subject_ids, ?)", [json_encode([$subject->id])])
                    ->count();

                // Count topics that have quizzes with this subject
                $subject->topics_with_quizzes = DB::table('topics')
                    ->where('subject_id', $subject->id)
                    ->whereExists(function ($query) use ($subject) {
                        $query->select(DB::raw(1))
                            ->from('quizzes')
                            ->whereColumn('topics.id', DB::raw('JSON_EXTRACT(quizzes.topic_ids, "$[0]")'))
                            ->where('quizzes.is_active', true);
                    })
                    ->count();

                return $subject;
            })
            ->filter(fn($subject) => $subject->quizzes_count > 0);

        return view('livewire.quizzes.subjects-list', [
            'subjects' => $subjects,
        ]);
    }
}
