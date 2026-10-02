<?php

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function addApprovedMockQuestions(Subject $subject, ExamType $examType, int $count): void
{
    $timestamp = now();

    for ($index = 0; $index < $count; $index++) {
        $questionId = Question::query()->insertGetId([
            'subject_id' => $subject->id,
            'exam_type_id' => $examType->id,
            'question_text' => 'Mock question '.$index,
            'is_mock' => true,
            'is_active' => true,
            'status' => 'approved',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        DB::table('options')->insert([
            [
                'question_id' => $questionId,
                'option_text' => 'Correct answer',
                'is_correct' => true,
                'sort_order' => 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'question_id' => $questionId,
                'option_text' => 'Incorrect answer',
                'is_correct' => false,
                'sort_order' => 2,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);
    }
}

function createMockSessionToken(): string
{
    return User::factory()->create()->createToken('Mock test device')->plainTextToken;
}

test('mock session uses configured JAMB counts and duration instead of client duration', function () {
    $examTypeId = DB::table('exam_types')->insertGetId([
        'name' => 'JAMB',
        'slug' => 'jamb',
        'exam_format' => 'jamb',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $examType = ExamType::findOrFail($examTypeId);
    $english = Subject::query()->create([
        'name' => 'English Language',
        'code' => 'ENG-001',
        'is_active' => true,
    ]);
    $mathematics = Subject::query()->create([
        'name' => 'Mathematics',
        'code' => 'MTH-001',
        'is_active' => true,
    ]);

    addApprovedMockQuestions($english, $examType, 70);
    addApprovedMockQuestions($mathematics, $examType, 50);

    $response = $this->withToken(createMockSessionToken())
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$english->id, $mathematics->id],
            'exam_type_id' => $examType->id,
            'duration_minutes' => 120,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.duration_minutes', 100)
        ->assertJsonPath('data.total_questions', 120)
        ->assertJsonPath('data.subjects.0.question_count', 70)
        ->assertJsonPath('data.subjects.0.time_limit_minutes', null)
        ->assertJsonPath('data.subjects.0.first_group.total_questions', 70)
        ->assertJsonPath('data.subjects.1.question_count', 50);
});

test('mock session sums configured SSCE subject durations', function () {
    $examTypeId = DB::table('exam_types')->insertGetId([
        'name' => 'SSCE',
        'slug' => 'ssce',
        'exam_format' => 'ssce',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $examType = ExamType::findOrFail($examTypeId);
    $english = Subject::query()->create([
        'name' => 'English Language',
        'code' => 'ENG-001',
        'is_active' => true,
    ]);
    $mathematics = Subject::query()->create([
        'name' => 'Mathematics',
        'code' => 'MTH-001',
        'is_active' => true,
    ]);

    addApprovedMockQuestions($english, $examType, 100);
    addApprovedMockQuestions($mathematics, $examType, 60);

    $this->withToken(createMockSessionToken())
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$english->id, $mathematics->id],
            'exam_type_id' => $examType->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.duration_minutes', 100)
        ->assertJsonPath('data.total_questions', 160)
        ->assertJsonPath('data.subjects.0.question_count', 100)
        ->assertJsonPath('data.subjects.0.time_limit_minutes', 50)
        ->assertJsonPath('data.subjects.1.question_count', 60)
        ->assertJsonPath('data.subjects.1.time_limit_minutes', 50);
});

test('mock session rejects subject with fewer questions than configured', function () {
    $examTypeId = DB::table('exam_types')->insertGetId([
        'name' => 'JAMB',
        'slug' => 'jamb',
        'exam_format' => 'jamb',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $examType = ExamType::findOrFail($examTypeId);
    $english = Subject::query()->create([
        'name' => 'English Language',
        'code' => 'ENG-001',
        'is_active' => true,
    ]);

    addApprovedMockQuestions($english, $examType, 69);

    $this->withToken(createMockSessionToken())
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$english->id],
            'exam_type_id' => $examType->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Not enough mock questions for English Language.');
});

test('JAMB mock session is limited to four subjects', function () {
    $examTypeId = DB::table('exam_types')->insertGetId([
        'name' => 'JAMB',
        'slug' => 'jamb',
        'exam_format' => 'jamb',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $subjectIds = [];

    for ($index = 1; $index <= 5; $index++) {
        $subjectIds[] = Subject::query()->create([
            'name' => 'Subject '.$index,
            'code' => 'SUB-00'.$index,
            'is_active' => true,
        ])->id;
    }

    $this->withToken(createMockSessionToken())
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => $subjectIds,
            'exam_type_id' => $examTypeId,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('subject_ids');
});

test('SSCE mock session accepts more than four eligible subjects', function () {
    $examTypeId = DB::table('exam_types')->insertGetId([
        'name' => 'SSCE',
        'slug' => 'ssce',
        'exam_format' => 'ssce',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $examType = ExamType::findOrFail($examTypeId);
    $subjectSpecs = [
        ['name' => 'English Language', 'code' => 'ENG-001', 'count' => 100],
        ['name' => 'Mathematics', 'code' => 'MTH-001', 'count' => 60],
        ['name' => 'Physics', 'code' => 'PHY-001', 'count' => 60],
        ['name' => 'Biology', 'code' => 'BIO-001', 'count' => 60],
        ['name' => 'Chemistry', 'code' => 'CHE-001', 'count' => 60],
    ];
    $subjectIds = [];

    foreach ($subjectSpecs as $subjectSpec) {
        $subject = Subject::query()->create([
            'name' => $subjectSpec['name'],
            'code' => $subjectSpec['code'],
            'is_active' => true,
        ]);
        $subjectIds[] = $subject->id;
        addApprovedMockQuestions($subject, $examType, $subjectSpec['count']);
    }

    $this->withToken(createMockSessionToken())
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => $subjectIds,
            'exam_type_id' => $examTypeId,
        ])
        ->assertSuccessful()
        ->assertJsonCount(5, 'data.subjects')
        ->assertJsonPath('data.total_questions', 340)
        ->assertJsonPath('data.duration_minutes', 205);
});
