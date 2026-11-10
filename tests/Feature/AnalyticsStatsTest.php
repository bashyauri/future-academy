<?php

use App\Enums\QuizType;
use App\Livewire\Dashboard\Analytics;
use App\Models\ExamType;
use App\Models\Lesson;
use App\Models\MockGroup;
use App\Models\MockSession;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('mock exam scope counts MockSession and mock groups and mock quizzes', function () {
    $user = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Mathematics',
        'code' => 'MTH',
        'slug' => 'mathematics',
        'is_active' => true,
    ]);
    $examType = ExamType::create([
        'name' => 'JAMB',
        'slug' => 'jamb',
    ]);

    // 1. Regular quiz attempt (should NOT be in mockExams)
    $quiz = Quiz::create([
        'title' => 'Sample Quiz',
        'slug' => 'sample-quiz',
        'type' => QuizType::Practice->value,
        'status' => 'published',
        'is_active' => true,
        'subject_id' => $subject->id,
    ]);
    QuizAttempt::create([
        'user_id' => $user->id,
        'quiz_id' => $quiz->id,
        'subject_id' => $subject->id,
        'score_percentage' => 80,
        'status' => 'completed',
    ]);

    // 2. MockSession attempt with quiz_id = null
    $mockAttempt1 = QuizAttempt::create([
        'user_id' => $user->id,
        'quiz_id' => null,
        'subject_id' => $subject->id,
        'score_percentage' => 75,
        'status' => 'completed',
    ]);
    MockSession::create([
        'user_id' => $user->id,
        'exam_type_id' => $examType->id,
        'quiz_attempt_id' => $mockAttempt1->id,
        'subject_ids' => [$subject->id],
        'questions_per_subject' => [$subject->id => 10],
        'status' => 'completed',
        'time_limit' => 60,
    ]);

    // 3. MockGroup attempt
    $mockGroup = MockGroup::create([
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
        'batch_number' => 1,
    ]);
    $mockAttempt2 = QuizAttempt::create([
        'user_id' => $user->id,
        'quiz_id' => null,
        'mock_group_id' => $mockGroup->id,
        'score_percentage' => 90,
        'status' => 'completed',
    ]);

    // 4. Mock Quiz attempt (quiz with type=mock)
    $mockQuiz = Quiz::create([
        'title' => 'Mock Exam Quiz',
        'slug' => 'mock-exam-quiz',
        'type' => QuizType::Mock->value,
        'status' => 'published',
        'is_active' => true,
        'subject_id' => $subject->id,
    ]);
    $mockAttempt3 = QuizAttempt::create([
        'user_id' => $user->id,
        'quiz_id' => $mockQuiz->id,
        'subject_id' => $subject->id,
        'score_percentage' => 85,
        'status' => 'completed',
    ]);

    // Verify scope
    $allMocks = QuizAttempt::where('user_id', $user->id)->mockExams()->completed()->get();
    expect($allMocks)->toHaveCount(3)
        ->and($allMocks->pluck('id')->all())->toEqualCanonicalizing([$mockAttempt1->id, $mockAttempt2->id, $mockAttempt3->id]);

    expect($user->quizAttempts()->mockExams()->completed()->max('score_percentage'))->toEqual(90);
});

test('analytics service correctly calculates overview and excludes in-progress attempts from average', function () {
    $user = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'code' => 'PHY',
        'slug' => 'physics',
        'is_active' => true,
    ]);

    Lesson::create([
        'title' => 'Intro to Physics',
        'description' => 'Basics',
        'subject_id' => $subject->id,
        'video_url' => 'https://example.com/video.mp4',
    ]);

    // Completed quiz with 60%
    QuizAttempt::create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'score_percentage' => 60,
        'time_taken_seconds' => 120,
        'status' => 'completed',
    ]);

    // In-progress dummy attempt initialized with 0% (should NOT be in average score)
    QuizAttempt::create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'score_percentage' => 0,
        'time_taken_seconds' => 0,
        'status' => 'in_progress',
    ]);

    $service = app(AnalyticsService::class);
    $overview = $service->getUserOverview($user);

    expect($overview['total_quizzes'])->toBe(1)
        ->and($overview['average_score'])->toEqual(60.0)
        ->and($overview['total_time_spent'])->toBe(120)
        ->and($overview['total_videos'])->toBe(1);
});

test('analytics livewire page calculates mock exams and videos accurately', function () {
    $user = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Chemistry',
        'code' => 'CHM',
        'slug' => 'chemistry',
        'is_active' => true,
    ]);
    $examType = ExamType::create([
        'name' => 'JAMB',
        'slug' => 'jamb',
    ]);
    $mockGroup = MockGroup::create([
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
        'batch_number' => 1,
    ]);

    QuizAttempt::create([
        'user_id' => $user->id,
        'quiz_id' => null,
        'mock_group_id' => $mockGroup->id,
        'subject_id' => $subject->id,
        'score_percentage' => 88,
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->assertViewHas('mockExamsTaken', 1)
        ->assertViewHas('bestMockScore', 88.0)
        ->assertSee('Mock Exams Taken');
});

test('livewire analytics component handles toJSON calls gracefully', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->call('toJSON')
        ->assertSuccessful();
});
