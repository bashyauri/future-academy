<?php

use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
});

test('can start a new practice quiz', function () {
    $subject = Subject::factory()->create();
    Question::factory()->count(10)->create([
        'subject_id' => $subject->id,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $response = $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'attempt_id',
            'total_questions',
            'all_question_ids',
            'questions',
            'loaded_up_to_index',
            'user_answers',
            'current_question_index',
            'time_limit',
            'started_at',
        ]);

    $this->assertDatabaseHas('quiz_attempts', [
        'user_id' => $this->user->id,
        'subject_id' => $subject->id,
        'status' => 'in_progress',
    ]);
});

test('can start practice quiz with exam type and year filter', function () {
    $subject = Subject::factory()->create();
    $examType = ExamType::factory()->create();

    Question::factory()->count(5)->create([
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
        'exam_year' => 2023,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    Question::factory()->count(5)->create([
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
        'exam_year' => 2024,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $response = $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
        'exam_type' => $examType->id,
        'year' => 2023,
    ]);

    $response->assertStatus(200);
    expect($response->json('total_questions'))->toBe(5);
});

test('can start practice quiz with limit', function () {
    $subject = Subject::factory()->create();
    Question::factory()->count(20)->create([
        'subject_id' => $subject->id,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $response = $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
        'limit' => 5,
    ]);

    $response->assertStatus(200);
    expect($response->json('total_questions'))->toBe(5);
});

test('can start practice quiz with time limit', function () {
    $subject = Subject::factory()->create();
    Question::factory()->count(10)->create([
        'subject_id' => $subject->id,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $response = $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
        'time' => 30,
    ]);

    $response->assertStatus(200);
    expect($response->json('time_limit'))->toBe(30);
});

test('can resume existing in-progress attempt', function () {
    $subject = Subject::factory()->create();
    Question::factory()->count(10)->create([
        'subject_id' => $subject->id,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $firstResponse = $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
    ]);

    $attemptId = $firstResponse->json('attempt_id');

    $secondResponse = $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
    ]);

    $secondResponse->assertStatus(200);
    expect($secondResponse->json('attempt_id'))->toBe($attemptId);
});

test('can get active attempts', function () {
    $subject = Subject::factory()->create();
    Question::factory()->count(10)->create([
        'subject_id' => $subject->id,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
    ]);

    $response = $this->getJson('/api/v1/practice/active-attempts');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'attempts' => [
                '*' => [
                    'id',
                    'subject_id',
                    'subject_name',
                    'exam_type_id',
                    'exam_type_name',
                    'exam_year',
                    'total_questions',
                    'current_question_index',
                    'started_at',
                    'time_limit',
                ],
            ],
        ]);

    expect($response->json('attempts'))->toHaveCount(1);
});

test('can delete an active attempt', function () {
    $subject = Subject::factory()->create();
    Question::factory()->count(10)->create([
        'subject_id' => $subject->id,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $startResponse = $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
    ]);

    $attemptId = $startResponse->json('attempt_id');

    $response = $this->deleteJson("/api/v1/practice/attempts/{$attemptId}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Attempt dismissed successfully.',
        ]);

    $this->assertDatabaseMissing('quiz_attempts', [
        'id' => $attemptId,
    ]);
});

test('can get question count for specific filters', function () {
    $subject = Subject::factory()->create();
    $examType = ExamType::factory()->create();

    Question::factory()->count(5)->create([
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
        'exam_year' => 2023,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    Question::factory()->count(5)->create([
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
        'exam_year' => 2024,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $response = $this->getJson('/api/v1/practice/question-count', [
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
        'year' => 2023,
    ]);

    $response->assertStatus(200);
    expect($response->json('count'))->toBe(5);
});

test('can save answers during quiz', function () {
    $subject = Subject::factory()->create();
    $questions = Question::factory()->count(10)->create([
        'subject_id' => $subject->id,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $startResponse = $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
        'time' => 30,
    ]);

    $attemptId = $startResponse->json('attempt_id');
    $allQuestionIds = $startResponse->json('all_question_ids');
    $questionsData = $startResponse->json('questions');

    $answers = array_fill(0, count($allQuestionIds), null);
    $answers[0] = $questionsData[0]['options'][0]['id'];

    $response = $this->postJson('/api/v1/practice/save', [
        'attempt_id' => $attemptId,
        'answers' => $answers,
        'current_question_index' => 1,
        'all_question_ids' => $allQuestionIds,
        'questions' => $questionsData,
        'loaded_up_to_index' => 4,
        'time_limit' => 30,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Progress saved',
        ]);

    $attempt = QuizAttempt::find($attemptId);
    expect($attempt->current_question_index)->toBe(1);

    $cacheKey = "practice_attempt_{$attemptId}";
    $cached = Cache::get($cacheKey);
    expect($cached['time_limit'])->toBe(30);
});

test('can submit quiz and get results', function () {
    $subject = Subject::factory()->create();
    $questions = Question::factory()->count(5)->create([
        'subject_id' => $subject->id,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $startResponse = $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
    ]);

    $attemptId = $startResponse->json('attempt_id');
    $allQuestionIds = $startResponse->json('all_question_ids');
    $questionsData = $startResponse->json('questions');

    $answers = [];
    foreach ($questionsData as $question) {
        $correctOption = collect($question['options'])->firstWhere('is_correct', true);
        $answers[] = $correctOption['id'];
    }

    $response = $this->postJson('/api/v1/practice/submit', [
        'attempt_id' => $attemptId,
        'answers' => $answers,
        'all_question_ids' => $allQuestionIds,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'score',
            'total',
            'percentage',
            'time_spent',
        ]);

    $attempt = QuizAttempt::find($attemptId);
    expect($attempt->status)->toBe('completed');
    expect($attempt->correct_answers)->toBe(5);
});

test('can exit quiz and save progress', function () {
    $subject = Subject::factory()->create();
    $questions = Question::factory()->count(10)->create([
        'subject_id' => $subject->id,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $startResponse = $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
    ]);

    $attemptId = $startResponse->json('attempt_id');
    $allQuestionIds = $startResponse->json('all_question_ids');
    $questionsData = $startResponse->json('questions');

    $answers = array_fill(0, count($allQuestionIds), null);
    $answers[0] = $questionsData[0]['options'][0]['id'];

    $response = $this->postJson('/api/v1/practice/exit', [
        'attempt_id' => $attemptId,
        'answers' => $answers,
        'all_question_ids' => $allQuestionIds,
        'current_question_index' => 1,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Progress saved. You can resume later.',
        ]);

    $attempt = QuizAttempt::find($attemptId);
    expect($attempt->status)->toBe('in_progress');
    expect($attempt->current_question_index)->toBe(1);

    $this->assertDatabaseHas('user_answers', [
        'quiz_attempt_id' => $attemptId,
    ]);
});

test('cannot access another users attempt', function () {
    $otherUser = User::factory()->create();
    $subject = Subject::factory()->create();
    Question::factory()->count(10)->create([
        'subject_id' => $subject->id,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $this->actingAs($otherUser, 'sanctum');
    $startResponse = $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
    ]);

    $attemptId = $startResponse->json('attempt_id');

    $this->actingAs($this->user, 'sanctum');
    $response = $this->getJson("/api/v1/practice/load/{$attemptId}");

    $response->assertStatus(403);
});

test('active attempts excludes expired timed quizzes', function () {
    $subject = Subject::factory()->create();
    Question::factory()->count(10)->create([
        'subject_id' => $subject->id,
        'is_active' => true,
        'status' => 'approved',
        'is_mock' => false,
    ]);

    $this->postJson('/api/v1/practice/start', [
        'subject' => $subject->id,
        'time' => 1, // 1 minute
    ]);

    $attempt = QuizAttempt::where('user_id', $this->user->id)->first();
    $attempt->update(['started_at' => now()->subMinutes(2)]);

    $response = $this->getJson('/api/v1/practice/active-attempts');

    $response->assertStatus(200);
    expect($response->json('attempts'))->toHaveCount(0);
});
