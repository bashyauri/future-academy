<?php

namespace App\Console\Commands;

use App\Models\ExamType;
use App\Models\Subject;
use App\Services\MockGroupService;
use Illuminate\Console\Command;

class GroupMockQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:group-mock-questions {--batch-size=40}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Group mock questions into batches for each subject-exam combination';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mockGroupService = app(MockGroupService::class);
        $batchSize = (int) $this->option('batch-size');

        $this->info("Grouping mock questions into batches of {$batchSize}...");

        $subjects = Subject::all();
        $examTypes = ExamType::all();

        if ($subjects->isEmpty() || $examTypes->isEmpty()) {
            $this->warn('No subjects or exam types found.');
            return;
        }

        $totalGrouped = 0;

        foreach ($subjects as $subject) {
            foreach ($examTypes as $examType) {
                $mockGroupService->groupMockQuestions($subject, $examType, $batchSize);
                $groups = $mockGroupService->getMockGroups($subject, $examType);

                if ($groups->isNotEmpty()) {
                    $this->line("✓ {$subject->name} ({$examType->name}): {$groups->count()} mock groups");
                    $totalGrouped += $groups->sum('total_questions');
                }
            }
        }

        $this->info("✓ Successfully grouped {$totalGrouped} mock questions!");
    }
}

