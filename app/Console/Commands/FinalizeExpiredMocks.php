<?php

namespace App\Console\Commands;

use App\Services\MockExamService;
use Illuminate\Console\Command;

class FinalizeExpiredMocks extends Command
{
    protected $signature = 'mock:finalize-expired';

    protected $description = 'Grade mock exams whose time limit has passed from their saved answers';

    public function handle(MockExamService $mockExamService): int
    {
        $this->info('Finalized '.$mockExamService->finalizeUnfinished().' expired mock(s).');

        return self::SUCCESS;
    }
}
