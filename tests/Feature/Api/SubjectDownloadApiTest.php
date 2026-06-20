<?php

use App\Models\Option;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can download single subject questions', function () {
    $user = User::factory()->create(['is_active' => true]);
    $subject = Subject::factory()->create(['is_active' => true]);

    $questions = Question::factory()
        ->count(3)
        ->for($subject)
        ->create(['is_active' => true, 'status' => 'approved', 'is_mock' => false]);

    foreach ($questions as $question) {
        Option::factory()
            ->count(4)
            ->for($question)
            ->create();
    }

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/subjects/{$subject->id}/download");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'subject' => ['id', 'name', 'code', 'slug'],
            'questions' => [
                '*' => [
                    'id',
                    'question_text',
                    'options' => [
                        '*' => ['id', 'label', 'option_text', 'is_correct'],
                    ],
                ],
            ],
            'total_questions',
        ])
        ->assertJsonPath('subject.id', $subject->id)
        ->assertJsonPath('total_questions', 3);
});

test('download respects year filter', function () {
    $user = User::factory()->create(['is_active' => true]);
    $subject = Subject::factory()->create(['is_active' => true]);

    Question::factory()
        ->for($subject)
        ->create(['is_active' => true, 'status' => 'approved', 'is_mock' => false, 'year' => 2023]);

    Question::factory()
        ->for($subject)
        ->create(['is_active' => true, 'status' => 'approved', 'is_mock' => false, 'year' => 2024]);

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/subjects/{$subject->id}/download?year=2024");

    $response->assertStatus(200)
        ->assertJsonPath('total_questions', 1)
        ->assertJsonPath('year_filter', 2024);
});

test('download excludes inactive and non-approved questions', function () {
    $user = User::factory()->create(['is_active' => true]);
    $subject = Subject::factory()->create(['is_active' => true]);

    Question::factory()
        ->for($subject)
        ->create(['is_active' => true, 'status' => 'approved', 'is_mock' => false]);

    Question::factory()
        ->for($subject)
        ->create(['is_active' => false, 'status' => 'approved', 'is_mock' => false]);

    Question::factory()
        ->for($subject)
        ->create(['is_active' => true, 'status' => 'pending', 'is_mock' => false]);

    Question::factory()
        ->for($subject)
        ->create(['is_active' => true, 'status' => 'approved', 'is_mock' => true]);

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/subjects/{$subject->id}/download");

    $response->assertStatus(200)
        ->assertJsonPath('total_questions', 1);
});

test('unauthenticated user cannot download subject questions', function () {
    $subject = Subject::factory()->create(['is_active' => true]);

    $response = $this->getJson("/api/v1/subjects/{$subject->id}/download");

    $response->assertStatus(401);
});

test('authenticated user can download jamb practice package', function () {
    $user = User::factory()->create(['is_active' => true]);

    $subjects = Subject::factory()
        ->count(3)
        ->create(['is_active' => true]);

    foreach ($subjects as $subject) {
        $questions = Question::factory()
            ->count(2)
            ->for($subject)
            ->create(['is_active' => true, 'status' => 'approved', 'is_mock' => false]);

        foreach ($questions as $question) {
            Option::factory()
                ->count(4)
                ->for($question)
                ->create();
        }
    }

    $subjectIds = $subjects->pluck('id')->implode(',');

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/jamb/download?subjects={$subjectIds}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'subjects' => [
                '*' => [
                    'id',
                    'name',
                    'code',
                    'questions' => [
                        '*' => [
                            'id',
                            'question_text',
                            'options' => [
                                '*' => ['id', 'label', 'option_text', 'is_correct'],
                            ],
                        ],
                    ],
                    'total_questions',
                ],
            ],
            'total_questions',
        ])
        ->assertJsonPath('total_questions', 6);
});

test('jamb download respects year filter', function () {
    $user = User::factory()->create(['is_active' => true]);

    $subject1 = Subject::factory()->create(['is_active' => true]);
    $subject2 = Subject::factory()->create(['is_active' => true]);

    Question::factory()
        ->for($subject1)
        ->create(['is_active' => true, 'status' => 'approved', 'is_mock' => false, 'year' => 2023]);

    Question::factory()
        ->for($subject1)
        ->create(['is_active' => true, 'status' => 'approved', 'is_mock' => false, 'year' => 2024]);

    Question::factory()
        ->for($subject2)
        ->create(['is_active' => true, 'status' => 'approved', 'is_mock' => false, 'year' => 2024]);

    $subjectIds = "{$subject1->id},{$subject2->id}";
    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/jamb/download?subjects={$subjectIds}&year=2024");

    $response->assertStatus(200)
        ->assertJsonPath('total_questions', 2)
        ->assertJsonPath('year_filter', 2024);
});

test('jamb download requires subjects parameter', function () {
    $user = User::factory()->create(['is_active' => true]);

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/jamb/download');

    $response->assertStatus(422);
});

test('jamb download limits to 4 subjects', function () {
    $user = User::factory()->create(['is_active' => true]);

    $subjects = Subject::factory()
        ->count(5)
        ->create(['is_active' => true]);

    $subjectIds = $subjects->pluck('id')->implode(',');
    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/jamb/download?subjects={$subjectIds}");

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Please provide between 1 and 4 subject IDs.');
});

test('download prevents n+1 queries', function () {
    $user = User::factory()->create(['is_active' => true]);
    $subject = Subject::factory()->create(['is_active' => true]);

    $questions = Question::factory()
        ->count(10)
        ->for($subject)
        ->create(['is_active' => true, 'status' => 'approved', 'is_mock' => false]);

    foreach ($questions as $question) {
        Option::factory()
            ->count(4)
            ->for($question)
            ->create();
    }

    $token = $user->createToken('Test Device')->plainTextToken;

    \DB::enableQueryLog();

    $this->withToken($token)
        ->getJson("/api/v1/subjects/{$subject->id}/download");

    $queryCount = count(\DB::getQueryLog());

    // Should be around 3 queries: subject, questions with options, count
    expect($queryCount)->toBeLessThan(10);
});

test('download returns 404 for non-existent subject', function () {
    $user = User::factory()->create(['is_active' => true]);

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/subjects/999/download');

    $response->assertStatus(404);
});

test('year validation accepts valid years', function () {
    $user = User::factory()->create(['is_active' => true]);
    $subject = Subject::factory()->create(['is_active' => true]);

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/subjects/{$subject->id}/download?year=2024");

    $response->assertStatus(200);
});

test('year validation rejects invalid years', function () {
    $user = User::factory()->create(['is_active' => true]);
    $subject = Subject::factory()->create(['is_active' => true]);

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/subjects/{$subject->id}/download?year=1999");

    $response->assertStatus(422);

    $response = $this->withToken($token)
        ->getJson("/api/v1/subjects/{$subject->id}/download?year=2101");

    $response->assertStatus(422);
});
