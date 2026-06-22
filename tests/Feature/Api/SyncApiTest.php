<?php

use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Models\UserProgress;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\Question;
use App\Models\Option;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-device')->plainTextToken;
});

test('sync endpoint requires authentication', function () {
    $response = $this->postJson('/api/v1/sync', [
        'attempts' => [],
        'answers' => [],
        'lesson_progress' => [],
    ]);

    $response->assertStatus(401);
});

test('sync endpoint validates required fields', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'attempts' => [
                [
                    'uuid' => 'test-uuid',
                    'user_id' => $this->user->id,
                    'subject_id' => 999, // Invalid subject
                    'exam_year' => 2024,
                    'status' => 'completed',
                    'started_at' => now()->toIso8601String(),
                    'total_questions' => 10,
                    'correct_answers' => 5,
                ],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['attempts.0.subject_id']);
});

test('sync creates quiz attempt successfully', function () {
    $subject = Subject::factory()->create();
    $quiz = Quiz::factory()->create();

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'attempts' => [
                [
                    'uuid' => 'test-uuid-123',
                    'user_id' => $this->user->id,
                    'quiz_id' => $quiz->id,
                    'subject_id' => $subject->id,
                    'exam_year' => 2024,
                    'status' => 'completed',
                    'started_at' => now()->subHour()->toIso8601String(),
                    'completed_at' => now()->toIso8601String(),
                    'time_taken_seconds' => 3600,
                    'total_questions' => 40,
                    'correct_answers' => 32,
                    'score_percentage' => 80,
                    'passed' => true,
                    'question_order' => [1, 2, 3],
                ],
            ],
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Sync completed successfully',
            'synced_attempts' => 1,
            'synced_answers' => 0,
            'synced_lesson_progress' => 0,
        ]);

    $this->assertDatabaseHas('quiz_attempts', [
        'user_id' => $this->user->id,
        'subject_id' => $subject->id,
        'status' => 'completed',
    ]);
});

test('sync prevents double-grading of same attempt', function () {
    $subject = Subject::factory()->create();
    $uuid = 'duplicate-uuid-test';

    // First sync
    $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'attempts' => [
                [
                    'uuid' => $uuid,
                    'user_id' => $this->user->id,
                    'subject_id' => $subject->id,
                    'exam_year' => 2024,
                    'status' => 'completed',
                    'started_at' => now()->subHour()->toIso8601String(),
                    'total_questions' => 40,
                    'correct_answers' => 32,
                ],
            ],
        ]);

    // Attempt to sync same attempt again (should fail)
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'attempts' => [
                [
                    'uuid' => $uuid,
                    'user_id' => $this->user->id,
                    'subject_id' => $subject->id,
                    'exam_year' => 2024,
                    'status' => 'completed',
                    'started_at' => now()->subHour()->toIso8601String(),
                    'total_questions' => 40,
                    'correct_answers' => 35, // Different score
                ],
            ],
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'synced_attempts' => 0,
            'failed_attempts' => 1,
        ]);

    // Verify only one attempt exists
    $this->assertDatabaseCount('quiz_attempts', 1);
});

test('sync creates user answers successfully', function () {
    $subject = Subject::factory()->create();
    $question = Question::factory()->create(['subject_id' => $subject->id]);
    $option = Option::factory()->create(['question_id' => $question->id, 'is_correct' => true]);

    $uuid = 'test-uuid-answers';

    // First sync the attempt
    $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'attempts' => [
                [
                    'uuid' => $uuid,
                    'user_id' => $this->user->id,
                    'subject_id' => $subject->id,
                    'exam_year' => 2024,
                    'status' => 'completed',
                    'started_at' => now()->subHour()->toIso8601String(),
                    'total_questions' => 1,
                    'correct_answers' => 1,
                ],
            ],
        ]);

    // Then sync answers
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'answers' => [
                [
                    'attempt_uuid' => $uuid,
                    'question_id' => $question->id,
                    'option_id' => $option->id,
                    'is_correct' => true,
                    'time_spent_seconds' => 30,
                ],
            ],
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'synced_attempts' => 0,
            'synced_answers' => 1,
        ]);

    $this->assertDatabaseHas('user_answers', [
        'question_id' => $question->id,
        'option_id' => $option->id,
        'is_correct' => true,
    ]);
});

test('sync prevents duplicate answers', function () {
    $subject = Subject::factory()->create();
    $question = Question::factory()->create(['subject_id' => $subject->id]);
    $option = Option::factory()->create(['question_id' => $question->id]);

    $uuid = 'test-uuid-duplicate-answers';

    // Sync attempt
    $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'attempts' => [
                [
                    'uuid' => $uuid,
                    'user_id' => $this->user->id,
                    'subject_id' => $subject->id,
                    'exam_year' => 2024,
                    'status' => 'completed',
                    'started_at' => now()->subHour()->toIso8601String(),
                    'total_questions' => 1,
                    'correct_answers' => 1,
                ],
            ],
        ]);

    // Sync answers first time
    $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'answers' => [
                [
                    'attempt_uuid' => $uuid,
                    'question_id' => $question->id,
                    'option_id' => $option->id,
                    'is_correct' => true,
                ],
            ],
        ]);

    // Try to sync same answer again (should be skipped)
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'answers' => [
                [
                    'attempt_uuid' => $uuid,
                    'question_id' => $question->id,
                    'option_id' => $option->id,
                    'is_correct' => true,
                ],
            ],
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'synced_answers' => 0, // No new answers synced
        ]);

    // Verify only one answer exists
    $this->assertDatabaseCount('user_answers', 1);
});

test('sync creates lesson progress successfully', function () {
    $lesson = Lesson::factory()->create();

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'lesson_progress' => [
                [
                    'user_id' => $this->user->id,
                    'lesson_id' => $lesson->id,
                    'current_time_seconds' => 120,
                    'progress_percentage' => 30,
                    'is_completed' => false,
                    'time_spent_seconds' => 120,
                ],
            ],
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'synced_lesson_progress' => 1,
        ]);

    $this->assertDatabaseHas('user_progress', [
        'user_id' => $this->user->id,
        'lesson_id' => $lesson->id,
        'type' => 'lesson',
        'progress_percentage' => 30,
    ]);
});

test('sync updates existing lesson progress', function () {
    $lesson = Lesson::factory()->create();

    // Create initial progress
    UserProgress::create([
        'user_id' => $this->user->id,
        'lesson_id' => $lesson->id,
        'type' => 'lesson',
        'current_time_seconds' => 60,
        'progress_percentage' => 15,
        'is_completed' => false,
    ]);

    // Sync updated progress
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'lesson_progress' => [
                [
                    'user_id' => $this->user->id,
                    'lesson_id' => $lesson->id,
                    'current_time_seconds' => 240,
                    'progress_percentage' => 60,
                    'is_completed' => false,
                    'time_spent_seconds' => 240,
                ],
            ],
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('user_progress', [
        'user_id' => $this->user->id,
        'lesson_id' => $lesson->id,
        'progress_percentage' => 60,
        'current_time_seconds' => 240,
    ]);
});

test('sync handles transaction rollback on error', function () {
    $subject = Subject::factory()->create();

    // Attempt to sync with invalid data that should cause transaction rollback
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'attempts' => [
                [
                    'uuid' => 'test-uuid-rollback',
                    'user_id' => $this->user->id,
                    'subject_id' => $subject->id,
                    'exam_year' => 2024,
                    'status' => 'invalid_status', // Invalid status
                    'started_at' => now()->subHour()->toIso8601String(),
                    'total_questions' => 40,
                    'correct_answers' => 32,
                ],
            ],
        ]);

    $response->assertStatus(422);

    // Verify no partial data was created
    $this->assertDatabaseMissing('quiz_attempts', [
        'user_id' => $this->user->id,
        'subject_id' => $subject->id,
    ]);
});

test('sync handles empty payload', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'attempts' => [],
            'answers' => [],
            'lesson_progress' => [],
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'synced_attempts' => 0,
            'synced_answers' => 0,
            'synced_lesson_progress' => 0,
        ]);
});

test('sync status endpoint returns correct data', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/sync/status');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'unsynced_attempts',
            'unsynced_answers',
            'unsynced_lesson_progress',
        ]);
});

test('sync handles batch of multiple attempts', function () {
    $subject = Subject::factory()->create();

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/sync', [
            'attempts' => [
                [
                    'uuid' => 'batch-uuid-1',
                    'user_id' => $this->user->id,
                    'subject_id' => $subject->id,
                    'exam_year' => 2024,
                    'status' => 'completed',
                    'started_at' => now()->subHours(2)->toIso8601String(),
                    'total_questions' => 40,
                    'correct_answers' => 30,
                ],
                [
                    'uuid' => 'batch-uuid-2',
                    'user_id' => $this->user->id,
                    'subject_id' => $subject->id,
                    'exam_year' => 2024,
                    'status' => 'completed',
                    'started_at' => now()->subHours(1)->toIso8601String(),
                    'total_questions' => 40,
                    'correct_answers' => 35,
                ],
                [
                    'uuid' => 'batch-uuid-3',
                    'user_id' => $this->user->id,
                    'subject_id' => $subject->id,
                    'exam_year' => 2024,
                    'status' => 'completed',
                    'started_at' => now()->subMinutes(30)->toIso8601String(),
                    'total_questions' => 40,
                    'correct_answers' => 28,
                ],
            ],
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'synced_attempts' => 3,
        ]);

    $this->assertDatabaseCount('quiz_attempts', 3);
});
