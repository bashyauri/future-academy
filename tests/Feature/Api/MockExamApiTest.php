<?php

use App\Models\ExamType;
use App\Models\MockGroup;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('can get mock groups for subject and exam type', function () {
    $subject = Subject::factory()->create();
    $examType = ExamType::factory()->create();

    MockGroup::factory()->create([
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
        'batch_number' => 1,
    ]);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/mock/groups?subject_id=' . $subject->id . '&exam_type_id=' . $examType->id);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                '*' => [
                    'id',
                    'subject_id',
                    'exam_type_id',
                    'batch_number',
                    'total_questions',
                    'subject',
                    'exam_type',
                ],
            ],
        ]);
});

test('can get specific mock group by batch number', function () {
    $subject = Subject::factory()->create();
    $examType = ExamType::factory()->create();

    $mockGroup = MockGroup::factory()->create([
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
        'batch_number' => 1,
    ]);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/mock/groups/1?subject_id=' . $subject->id . '&exam_type_id=' . $examType->id);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Mock group retrieved successfully',
            'data' => [
                'id' => $mockGroup->id,
                'batch_number' => 1,
            ],
        ]);
});

test('returns 404 for non-existent mock group', function () {
    $subject = Subject::factory()->create();
    $examType = ExamType::factory()->create();

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/mock/groups/999?subject_id=' . $subject->id . '&exam_type_id=' . $examType->id);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Mock group not found',
        ]);
});

test('can download mock group questions', function () {
    $subject = Subject::factory()->create();
    $examType = ExamType::factory()->create();

    $mockGroup = MockGroup::factory()->create([
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
        'batch_number' => 1,
    ]);

    $questions = Question::factory()->count(5)->create([
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
        'mock_group_id' => $mockGroup->id,
    ]);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/mock/groups/1/download?subject_id=' . $subject->id . '&exam_type_id=' . $examType->id);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'mock_group',
                'questions',
            ],
        ]);
});

test('can initialize multi-subject mock session', function () {
    $examType = ExamType::factory()->create();
    $subjects = Subject::factory()->count(3)->create();

    foreach ($subjects as $subject) {
        MockGroup::factory()->create([
            'subject_id' => $subject->id,
            'exam_type_id' => $examType->id,
            'batch_number' => 1,
        ]);
    }

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => $subjects->pluck('id')->toArray(),
            'exam_type_id' => $examType->id,
            'duration_minutes' => 120,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'session_id',
                'exam_type',
                'subjects',
                'duration_minutes',
                'total_questions',
                'time_limit_per_subject',
            ],
        ]);
});

test('validation fails for invalid subject ids', function () {
    $examType = ExamType::factory()->create();

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [999, 998],
            'exam_type_id' => $examType->id,
        ]);

    $response->assertStatus(422);
});

test('validation fails for missing required fields', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['subject_ids', 'exam_type_id']);
});

test('unauthenticated requests are rejected', function () {
    $response = $this->getJson('/api/v1/mock/groups?subject_id=1&exam_type_id=1');

    $response->assertStatus(401);
});
