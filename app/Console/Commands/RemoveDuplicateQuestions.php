<?php

namespace App\Console\Commands;

use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveDuplicateQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:remove-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate questions from the database, keeping only the oldest one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Searching for duplicate questions...');

        // Find duplicates
        $duplicates = Question::select('question_text', 'subject_id', 'exam_type_id', 'exam_year', DB::raw('COUNT(*) as count'), DB::raw('MIN(id) as keep_id'))
            ->groupBy('question_text', 'subject_id', 'exam_type_id', 'exam_year')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate questions found!');
            return 0;
        }

        $this->warn("Found {$duplicates->count()} sets of duplicate questions.");

        $totalDeleted = 0;

        foreach ($duplicates as $duplicate) {
            // Get all questions with same text, subject, exam type, and year
            $questions = Question::where('question_text', $duplicate->question_text)
                ->where('subject_id', $duplicate->subject_id)
                ->where('exam_type_id', $duplicate->exam_type_id)
                ->where('exam_year', $duplicate->exam_year)
                ->orderBy('id')
                ->get();

            $keepQuestion = $questions->first();
            $deleteQuestions = $questions->slice(1);

            $this->line("Question: " . substr($duplicate->question_text, 0, 60) . "...");
            $this->line("  Keeping ID: {$keepQuestion->id}");
            $this->line("  Deleting IDs: " . $deleteQuestions->pluck('id')->join(', '));

            // Delete user answers associated with duplicate questions
            foreach ($deleteQuestions as $question) {
                DB::table('user_answers')->where('question_id', $question->id)->delete();
                $question->options()->delete();
                $question->delete();
                $totalDeleted++;
            }
        }

        $this->info("Successfully removed {$totalDeleted} duplicate questions!");
        $this->info('Remaining questions: ' . Question::count());

        return 0;
    }
}
