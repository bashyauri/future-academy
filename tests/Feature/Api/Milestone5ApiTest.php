<?php

use App\Models\ExamType;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

// Configuration Tests
test('can get subjects list', function () {
    Subject::factory()->count(3)->create(['is_active' => true]);
    Subject::factory()->create(['is_active' => false]);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/config/subjects');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                '*' => ['id', 'name', 'code', 'slug', 'icon', 'color', 'is_active'],
            ],
        ]);
});

test('can get exam types list', function () {
    ExamType::factory()->count(2)->create();

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/config/exam-types');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                '*' => ['id', 'name', 'slug', 'exam_format'],
            ],
        ]);
});

test('can get available years', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/config/years');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data',
        ]);
});

test('can get mock formats configuration', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/config/mock-formats');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data',
        ]);
});

// Analytics Tests
test('can get analytics overview', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/analytics/overview');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => ['total_quizzes', 'average_score', 'total_time_spent', 'study_streak'],
        ]);
});

test('can get subject performance', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/analytics/subject-performance');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data',
        ]);
});

test('can get quiz history with limit', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/analytics/quiz-history?limit=5');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data',
        ]);
});

test('can get study streak', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/analytics/study-streak');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => ['current_streak', 'last_activity_date'],
        ]);
});

// Lesson Tests
test('can get lessons for subject', function () {
    $subject = Subject::factory()->create();
    Lesson::factory()->count(3)->create(['subject_id' => $subject->id]);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/lessons?subject_id='.$subject->id);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                '*' => ['id', 'title', 'description', 'duration_seconds', 'is_completed', 'progress_percentage'],
            ],
        ]);
});

test('can get lesson details', function () {
    $lesson = Lesson::factory()->create();

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/lessons/'.$lesson->id);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'title', 'video_url', 'duration_seconds', 'subject', 'progress'],
        ]);
});

test('can update lesson progress', function () {
    $lesson = Lesson::factory()->create();

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/lessons/'.$lesson->id.'/progress', [
            'current_time_seconds' => 120,
            'progress_percentage' => 30,
            'time_spent_seconds' => 120,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => ['lesson_id', 'progress_percentage', 'is_completed'],
        ]);
});

test('can mark lesson as completed', function () {
    $lesson = Lesson::factory()->create();

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/lessons/'.$lesson->id.'/complete');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Lesson marked as completed successfully',
            'data' => [
                'lesson_id' => $lesson->id,
                'is_completed' => true,
            ],
        ]);
});

// Quiz Tests
test('can get quizzes list', function () {
    Quiz::factory()->count(3)->create();

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/quizzes');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                '*' => ['id', 'title', 'type', 'duration_minutes', 'question_count'],
            ],
        ]);
});

test('can get quiz details', function () {
    $quiz = Quiz::factory()->create();

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/quizzes/'.$quiz->id);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'title', 'questions'],
        ]);
});

test('can start quiz attempt', function () {
    $quiz = Quiz::factory()->create();

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/quizzes/'.$quiz->id.'/start', [
            'question_count' => 10,
            'time_limit' => 45,
            'shuffle' => true,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => ['attempt_id', 'quiz_id', 'total_questions', 'question_order', 'time_limit', 'started_at'],
        ])
        ->assertJsonPath('data.time_limit', 45);
});

test('jamb session requires exactly four subjects', function () {
    $jamb = ExamType::factory()->create([
        'slug' => 'jamb',
    ]);

    $subjects = Subject::factory()->count(3)->create(['is_active' => true]);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/jamb/sessions', [
            'subject_ids' => $subjects->pluck('id')->toArray(),
            'year' => 2024,
            'questions_per_subject' => 40,
            'time_limit' => 180,
            'shuffle' => false,
        ]);

    $response->assertStatus(422);
});

test('unauthenticated requests are rejected', function () {
    $response = $this->getJson('/api/v1/config/subjects');
    $response->assertStatus(401);
});
