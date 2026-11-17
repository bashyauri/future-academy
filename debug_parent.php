<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$parent = App\Models\User::find(53);
if (!$parent) { echo 'Parent not found'; exit; }

$childIds = $parent->children()->pluck('users.id')->all();
echo 'Children IDs: ' . implode(', ', $childIds) . PHP_EOL;
echo 'Count: ' . count($childIds) . PHP_EOL;

if (empty($childIds)) {
    echo 'NO CHILDREN LINKED!' . PHP_EOL;
    exit;
}

$quizAgg = App\Models\QuizAttempt::whereIn('user_id', $childIds)
    ->where('status', 'completed')
    ->selectRaw('COUNT(*) as total_quizzes, AVG(score_percentage) as avg_score')
    ->first();
echo 'Quizzes taken: ' . ($quizAgg->total_quizzes ?? 0) . ', Avg Score: ' . round($quizAgg->avg_score ?? 0, 1) . PHP_EOL;

$hasMockExamsScope = method_exists(App\Models\QuizAttempt::class, 'scopeMockExams');
echo 'Has mockExams scope: ' . ($hasMockExamsScope ? 'YES' : 'NO') . PHP_EOL;

$hasVideoTable = Illuminate\Support\Facades\Schema::hasTable('video_progress');
echo 'Has video_progress table: ' . ($hasVideoTable ? 'YES' : 'NO') . PHP_EOL;
if ($hasVideoTable) {
    $v = Illuminate\Support\Facades\DB::table('video_progress')->whereIn('user_id', $childIds)->where('completed', true)->count();
    echo 'Videos watched: ' . $v . PHP_EOL;
    // Check columns
    $cols = Illuminate\Support\Facades\Schema::getColumnListing('video_progress');
    echo 'video_progress columns: ' . implode(', ', $cols) . PHP_EOL;
}

$hasLessonTable = Illuminate\Support\Facades\Schema::hasTable('user_progress');
echo 'Has user_progress table: ' . ($hasLessonTable ? 'YES' : 'NO') . PHP_EOL;
if ($hasLessonTable) {
    $cols = Illuminate\Support\Facades\Schema::getColumnListing('user_progress');
    echo 'user_progress columns: ' . implode(', ', $cols) . PHP_EOL;
    $l = Illuminate\Support\Facades\DB::table('user_progress')
        ->whereIn('user_id', $childIds)
        ->where('type', 'lesson')
        ->selectRaw('COUNT(*) as started, SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed, SUM(time_spent_seconds) as total_time')
        ->first();
    echo 'Lessons started: ' . ($l->started ?? 0) . ', completed: ' . ($l->completed ?? 0) . ', time_spent: ' . ($l->total_time ?? 0) . PHP_EOL;
}

// Check enrollments
$enrolled = Illuminate\Support\Facades\DB::table('enrollments')
    ->whereIn('user_id', $childIds)
    ->where('is_active', true)
    ->count();
echo 'Active enrollments: ' . $enrolled . PHP_EOL;
