<?php

use App\Models\ExamType;
use App\Models\MockGroup;
use App\Models\MockSession;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'mock.formats.default.overall.time_limit' => 30,
        'mock.formats.default.default.questions' => 2,
        'mock.formats.default.default.time' => null,
    ]);

    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('Mock mobile test')->plainTextToken;
});

function createMobileSessionExamType(string $format = 'default'): ExamType
{
    $examTypeId = DB::table('exam_types')->insertGetId([
        'name' => strtoupper($format),
        'slug' => $format,
        'exam_format' => $format,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return ExamType::findOrFail($examTypeId);
}

function createMobileSessionSubject(string $name = 'Mock Mathematics', int $number = 1): Subject
{
    return Subject::query()->create([
        'name' => $name,
        'code' => 'MOCK-'.$number,
        'is_active' => true,
    ]);
}

function createMobileSessionQuestions(Subject $subject, ExamType $examType, int $count, bool $isMock = true): array
{
    $questionIds = [];
    $correctOptionIds = [];
    $timestamp = now();

    for ($index = 1; $index <= $count; $index++) {
        $questionId = DB::table('questions')->insertGetId([
            'subject_id' => $subject->id,
            'exam_type_id' => $examType->id,
            'question_text' => "Question {$index}",
            'is_mock' => $isMock,
            'is_active' => true,
            'status' => 'approved',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $questionIds[] = $questionId;

        $correctOptionIds[$questionId] = DB::table('options')->insertGetId([
            'question_id' => $questionId,
            'option_text' => "Correct {$index}",
            'is_correct' => true,
            'sort_order' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        DB::table('options')->insert([
            'question_id' => $questionId,
            'option_text' => "Incorrect {$index}",
            'is_correct' => false,
            'sort_order' => 2,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    return [
        'question_ids' => $questionIds,
        'correct_option_ids' => $correctOptionIds,
    ];
}

test('full mock sessions hide answers, save progress without restoring it, and submit server-side scores', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    $fixture = createMobileSessionQuestions($subject, $examType, 3);

    $startResponse = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$subject->id],
            'exam_type_id' => $examType->id,
            'duration_minutes' => 120,
        ]);

    $startResponse->assertSuccessful()
        ->assertJsonPath('data.duration_minutes', 30)
        ->assertJsonPath('data.total_questions', 2)
        ->assertJsonPath('data.mode', 'full');

    $sessionId = $startResponse->json('data.session_id');
    $session = MockSession::query()->findOrFail($sessionId);
    $attempt = QuizAttempt::query()->findOrFail($session->quiz_attempt_id);

    expect($session->user_id)->toBe($this->user->id)
        ->and($attempt->status)->toBe('in_progress')
        ->and($attempt->question_order)->toHaveCount(2);

    $questionId = $attempt->question_order[0];
    $correctOptionId = $fixture['correct_option_ids'][$questionId];

    $this->withToken($this->token)
        ->getJson("/api/v1/mock/sessions/{$sessionId}")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonMissingPath("data.questions_by_subject.{$subject->id}.0.options.0.is_correct");

    $this->withToken($this->token)
        ->putJson("/api/v1/mock/sessions/{$sessionId}/progress", [
            'question_id' => $questionId,
            'option_id' => $correctOptionId,
            'current_question_index' => 1,
        ])
        ->assertSuccessful();

    $this->withToken($this->token)
        ->getJson("/api/v1/mock/sessions/{$sessionId}")
        ->assertSuccessful()
        ->assertJsonMissingPath('data.answers_by_question')
        ->assertJsonPath('data.current_question_index', 0);

    $submitResponse = $this->withToken($this->token)
        ->postJson("/api/v1/mock/sessions/{$sessionId}/submit", [
            'answers' => [
                $questionId => $correctOptionId,
            ],
        ]);

    $submitResponse->assertSuccessful()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.score', 1)
        ->assertJsonPath('data.total', 2)
        ->assertJsonPath('data.percentage', 50);

    $attempt->refresh();
    expect($attempt->status)->toBe('completed')
        ->and($attempt->correct_answers)->toBe(1)
        ->and((float) $attempt->percentage)->toBe(50.0);

    $this->assertDatabaseHas('user_answers', [
        'user_id' => $this->user->id,
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $questionId,
        'option_id' => $correctOptionId,
        'is_correct' => true,
    ]);

    $this->withToken($this->token)
        ->getJson("/api/v1/mock/sessions/{$sessionId}")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath("data.questions_by_subject.{$subject->id}.0.options.0.is_correct", fn ($value) => is_bool($value));
});

test('a blank submit never erases an answer that was already saved', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    $fixture = createMobileSessionQuestions($subject, $examType, 2);

    $sessionId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$subject->id],
            'exam_type_id' => $examType->id,
        ])
        ->assertSuccessful()
        ->json('data.session_id');

    $attempt = MockSession::query()->findOrFail($sessionId)->quizAttempt;
    $questionId = $attempt->question_order[0];
    $correctOptionId = $fixture['correct_option_ids'][$questionId];

    $this->withToken($this->token)
        ->putJson("/api/v1/mock/sessions/{$sessionId}/progress", [
            'answers' => [$questionId => $correctOptionId],
        ])
        ->assertSuccessful();

    $this->withToken($this->token)
        ->putJson("/api/v1/mock/sessions/{$sessionId}/progress", [
            'answers' => [$questionId => null],
        ])
        ->assertSuccessful();

    $this->withToken($this->token)
        ->postJson("/api/v1/mock/sessions/{$sessionId}/submit", [
            'answers' => [$questionId => null],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.score', 1);
});

test('expired mocks are graded from saved answers by the scheduled command', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    $fixture = createMobileSessionQuestions($subject, $examType, 2);

    $expiredId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', ['subject_ids' => [$subject->id], 'exam_type_id' => $examType->id])
        ->json('data.session_id');
    $expiredAttempt = MockSession::query()->findOrFail($expiredId)->quizAttempt;
    $questionId = $expiredAttempt->question_order[0];

    $this->withToken($this->token)
        ->putJson("/api/v1/mock/sessions/{$expiredId}/progress", [
            'answers' => [$questionId => $fixture['correct_option_ids'][$questionId]],
        ])
        ->assertSuccessful();
    $expiredAttempt->update(['started_at' => now()->subMinutes(31)]);

    $this->artisan('mock:finalize-expired')->assertSuccessful();

    expect(MockSession::query()->find($expiredId)->status)->toBe('completed')
        ->and($expiredAttempt->refresh()->status)->toBe('completed')
        ->and($expiredAttempt->correct_answers)->toBe(1);
});

test('a running mock is not touched by the scheduled command', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    createMobileSessionQuestions($subject, $examType, 2);

    $sessionId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', ['subject_ids' => [$subject->id], 'exam_type_id' => $examType->id])
        ->json('data.session_id');

    $this->artisan('mock:finalize-expired')->assertSuccessful();

    expect(MockSession::query()->find($sessionId)->status)->toBe('active');
});

test('starting a new mock grades the unfinished one first', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    $fixture = createMobileSessionQuestions($subject, $examType, 2);

    $firstId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', ['subject_ids' => [$subject->id], 'exam_type_id' => $examType->id])
        ->json('data.session_id');
    $firstAttempt = MockSession::query()->findOrFail($firstId)->quizAttempt;
    $questionId = $firstAttempt->question_order[0];

    $this->withToken($this->token)
        ->putJson("/api/v1/mock/sessions/{$firstId}/progress", [
            'answers' => [$questionId => $fixture['correct_option_ids'][$questionId]],
        ])
        ->assertSuccessful();

    $secondId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', ['subject_ids' => [$subject->id], 'exam_type_id' => $examType->id])
        ->assertSuccessful()
        ->json('data.session_id');

    expect(MockSession::query()->find($firstId)->status)->toBe('completed')
        ->and($firstAttempt->refresh()->correct_answers)->toBe(1)
        ->and(MockSession::query()->find($secondId)->status)->toBe('active');

    $this->withToken($this->token)
        ->getJson("/api/v1/mock/sessions/{$firstId}")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.score', 1);
});

test('submit returns scores only and the review loads separately', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    $fixture = createMobileSessionQuestions($subject, $examType, 2);

    $sessionId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', ['subject_ids' => [$subject->id], 'exam_type_id' => $examType->id])
        ->json('data.session_id');
    $questionId = MockSession::query()->findOrFail($sessionId)->quizAttempt->question_order[0];

    $this->withToken($this->token)
        ->postJson("/api/v1/mock/sessions/{$sessionId}/submit", [
            'answers' => [$questionId => $fixture['correct_option_ids'][$questionId]],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.score', 1)
        ->assertJsonPath('data.questions_by_subject', [])
        ->assertJsonMissingPath('data.answers_by_question');

    $this->withToken($this->token)
        ->getJson("/api/v1/mock/sessions/{$sessionId}")
        ->assertSuccessful()
        ->assertJsonPath("data.answers_by_question.{$questionId}", $fixture['correct_option_ids'][$questionId])
        ->assertJsonCount(2, "data.questions_by_subject.{$subject->id}");
});

test('active mock session question batches preserve the server question order', function () {
    config(['mock.formats.default.default.questions' => 12]);

    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    createMobileSessionQuestions($subject, $examType, 13);

    $sessionId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$subject->id],
            'exam_type_id' => $examType->id,
        ])
        ->assertSuccessful()
        ->json('data.session_id');

    $attempt = MockSession::query()->findOrFail($sessionId)->quizAttempt;
    $this->assertDatabaseMissing('mock_groups', [
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
    ]);

    $firstBatch = $this->withToken($this->token)
        ->getJson("/api/v1/mock/sessions/{$sessionId}")
        ->assertSuccessful()
        ->assertJsonPath('data.loaded_question_count', 5)
        ->json("data.questions_by_subject.{$subject->id}");

    $secondBatch = $this->withToken($this->token)
        ->getJson("/api/v1/mock/sessions/{$sessionId}/subjects/{$subject->id}/questions/5")
        ->assertSuccessful()
        ->assertJsonPath('data.question_offset', 5)
        ->assertJsonPath('data.loaded_question_count', 5)
        ->json("data.questions_by_subject.{$subject->id}");

    $lastBatch = $this->withToken($this->token)
        ->getJson("/api/v1/mock/sessions/{$sessionId}/subjects/{$subject->id}/questions/10")
        ->assertSuccessful()
        ->assertJsonPath('data.loaded_question_count', 2)
        ->json("data.questions_by_subject.{$subject->id}");

    $reloadedFirstBatch = $this->withToken($this->token)
        ->getJson("/api/v1/mock/sessions/{$sessionId}/subjects/{$subject->id}/questions/0")
        ->assertSuccessful()
        ->json("data.questions_by_subject.{$subject->id}");

    expect(array_column(array_merge($firstBatch, $secondBatch, $lastBatch), 'id'))
        ->toBe($attempt->question_order)
        ->and(array_column($reloadedFirstBatch[0]['options'], 'id'))
        ->toBe(array_column($firstBatch[0]['options'], 'id'));
});

test('mock question batches can load a later subject directly', function () {
    config(['mock.formats.default.default.questions' => 6]);

    $examType = createMobileSessionExamType();
    $firstSubject = createMobileSessionSubject('First Subject', 1);
    $secondSubject = createMobileSessionSubject('Second Subject', 2);
    createMobileSessionQuestions($firstSubject, $examType, 6);
    createMobileSessionQuestions($secondSubject, $examType, 6);

    $sessionId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$firstSubject->id, $secondSubject->id],
            'exam_type_id' => $examType->id,
        ])
        ->assertSuccessful()
        ->json('data.session_id');

    $attempt = MockSession::query()->findOrFail($sessionId)->quizAttempt;
    $secondSubjectBatch = $this->withToken($this->token)
        ->getJson("/api/v1/mock/sessions/{$sessionId}/subjects/{$secondSubject->id}/questions/0")
        ->assertSuccessful()
        ->assertJsonPath("data.questions_by_subject.{$firstSubject->id}", [])
        ->json("data.questions_by_subject.{$secondSubject->id}");

    expect(array_column($secondSubjectBatch, 'id'))
        ->toBe(array_slice($attempt->question_order, 6, 5));
});

test('batch sessions use the selected group and report completion and best score', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    $fixture = createMobileSessionQuestions($subject, $examType, 3);
    $group = MockGroup::query()->create([
        'subject_id' => $subject->id,
        'exam_type_id' => $examType->id,
        'batch_number' => 2,
        'total_questions' => 3,
    ]);
    Question::query()->whereIn('id', $fixture['question_ids'])->update(['mock_group_id' => $group->id]);

    $startResponse = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$subject->id],
            'exam_type_id' => $examType->id,
            'mock_group_id' => $group->id,
        ]);

    $startResponse->assertSuccessful()
        ->assertJsonPath('data.mode', 'batch')
        ->assertJsonPath('data.mock_group_id', $group->id)
        ->assertJsonPath('data.duration_minutes', 60)
        ->assertJsonPath('data.total_questions', 3);

    $sessionId = $startResponse->json('data.session_id');
    $attempt = QuizAttempt::query()->where('mock_group_id', $group->id)->firstOrFail();
    $questionId = $attempt->question_order[0];

    $this->withToken($this->token)
        ->postJson("/api/v1/mock/sessions/{$sessionId}/submit", [
            'answers' => [
                $questionId => $fixture['correct_option_ids'][$questionId],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.score', 1)
        ->assertJsonPath('data.total', 3);

    $this->withToken($this->token)
        ->getJson('/api/v1/mock/groups?subject_id='.$subject->id.'&exam_type_id='.$examType->id)
        ->assertSuccessful()
        ->assertJsonPath('data.0.is_completed', true)
        ->assertJsonPath('data.0.best_score', 33.33);
});

test('mock subject list omits subjects that only contain ordinary questions', function () {
    $examType = createMobileSessionExamType();
    $mockSubject = createMobileSessionSubject('Mock Subject', 1);
    $practiceSubject = createMobileSessionSubject('Practice Subject', 2);
    createMobileSessionQuestions($mockSubject, $examType, 2);
    createMobileSessionQuestions($practiceSubject, $examType, 3, false);

    $this->withToken($this->token)
        ->getJson('/api/v1/mock/subjects?exam_type_id='.$examType->id)
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mockSubject->id);
});

test('users cannot load another users mock session', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    createMobileSessionQuestions($subject, $examType, 2);

    $sessionId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$subject->id],
            'exam_type_id' => $examType->id,
        ])
        ->json('data.session_id');

    $otherUser = User::factory()->create();

    $this->actingAs($otherUser, 'sanctum')
        ->getJson("/api/v1/mock/sessions/{$sessionId}")
        ->assertNotFound();
});

test('web mock autosave persists answers to the database while the session is active', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    $fixture = createMobileSessionQuestions($subject, $examType, 2);

    $sessionId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$subject->id],
            'exam_type_id' => $examType->id,
        ])
        ->assertSuccessful()
        ->json('data.session_id');

    $session = MockSession::query()->findOrFail($sessionId);
    $attempt = $session->quizAttempt;
    $questionId = $attempt->question_order[0];
    $optionId = $fixture['correct_option_ids'][$questionId];

    $this->actingAs($this->user, 'web')
        ->postJson('/api/v1/mock/save-web-progress', [
            'session_id' => $sessionId,
            'questions' => [$subject->id => [
                ['id' => $questionId],
            ]],
            'answers' => [
                $questionId => $optionId,
            ],
            'position' => [
                'subjectIndex' => 0,
                'questionIndex' => 0,
            ],
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('user_answers', [
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $questionId,
        'option_id' => $optionId,
        'user_id' => $this->user->id,
    ]);
});

test('web session users can autosave mock progress without a Sanctum bearer token', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    $fixture = createMobileSessionQuestions($subject, $examType, 2);

    $sessionId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$subject->id],
            'exam_type_id' => $examType->id,
        ])
        ->assertSuccessful()
        ->json('data.session_id');

    $this->actingAs($this->user, 'web');

    $session = MockSession::query()->findOrFail($sessionId);
    $attempt = $session->quizAttempt;
    $questionId = $attempt->question_order[0];
    $optionId = $fixture['correct_option_ids'][$questionId];

    $this->postJson('/api/v1/mock/save-web-progress', [
        'session_id' => $sessionId,
        'questions' => [$subject->id => [
            ['id' => $questionId],
        ]],
        'answers' => [
            $questionId => $optionId,
        ],
        'position' => [
            'subjectIndex' => 0,
            'questionIndex' => 0,
        ],
    ])->assertSuccessful();
});

test('web route accepts browser autosave without the api prefix', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    $fixture = createMobileSessionQuestions($subject, $examType, 2);

    $sessionId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$subject->id],
            'exam_type_id' => $examType->id,
        ])
        ->assertSuccessful()
        ->json('data.session_id');

    $this->actingAs($this->user, 'web');

    $session = MockSession::query()->findOrFail($sessionId);
    $attempt = $session->quizAttempt;
    $questionId = $attempt->question_order[0];
    $optionId = $fixture['correct_option_ids'][$questionId];

    $this->postJson('/mock/save-web-progress', [
        'session_id' => $sessionId,
        'questions' => [$subject->id => [
            ['id' => $questionId],
        ]],
        'answers' => [
            $questionId => $optionId,
        ],
        'position' => [
            'subjectIndex' => 0,
            'questionIndex' => 0,
        ],
    ])->assertSuccessful();
});

test('browser-style nested answers are normalized and saved for active mock sessions', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    $fixture = createMobileSessionQuestions($subject, $examType, 2);

    $sessionId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$subject->id],
            'exam_type_id' => $examType->id,
        ])
        ->assertSuccessful()
        ->json('data.session_id');

    $this->actingAs($this->user, 'web');

    $session = MockSession::query()->findOrFail($sessionId);
    $attempt = $session->quizAttempt;
    $questionId = $attempt->question_order[0];
    $optionId = $fixture['correct_option_ids'][$questionId];

    $this->postJson('/mock/save-web-progress', [
        'session_id' => $sessionId,
        'questions' => [$subject->id => [['id' => $questionId]]],
        'answers' => [
            $subject->id => [$optionId],
        ],
        'position' => [
            'subjectIndex' => 0,
            'questionIndex' => 0,
        ],
    ])->assertSuccessful();

    $this->assertDatabaseHas('user_answers', [
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $questionId,
        'option_id' => $optionId,
        'user_id' => $this->user->id,
    ]);
});

test('object-shaped saved mock questions keep their option list when restored', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    $fixture = createMobileSessionQuestions($subject, $examType, 2);

    $sessionId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$subject->id],
            'exam_type_id' => $examType->id,
        ])
        ->assertSuccessful()
        ->json('data.session_id');

    $this->actingAs($this->user, 'web');

    $session = MockSession::query()->findOrFail($sessionId);
    $attempt = $session->quizAttempt;
    $questionId = $attempt->question_order[0];
    $optionId = $fixture['correct_option_ids'][$questionId];

    $this->postJson('/mock/save-web-progress', [
        'session_id' => $sessionId,
        'questions' => [
            $subject->id => [
                (object) [
                    'id' => $questionId,
                    'options' => [
                        (object) ['id' => $optionId, 'option_text' => 'Correct Option'],
                        (object) ['id' => 999999, 'option_text' => 'Wrong Option'],
                    ],
                ],
            ],
        ],
        'answers' => [
            $subject->id => [$optionId],
        ],
        'position' => [
            'subjectIndex' => 0,
            'questionIndex' => 0,
        ],
    ])->assertSuccessful();

    $this->assertDatabaseHas('user_answers', [
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $questionId,
        'option_id' => $optionId,
        'user_id' => $this->user->id,
    ]);
});

test('expired session ignores late answers and submits saved progress', function () {
    $examType = createMobileSessionExamType();
    $subject = createMobileSessionSubject();
    $fixture = createMobileSessionQuestions($subject, $examType, 2);

    $sessionId = $this->withToken($this->token)
        ->postJson('/api/v1/mock/sessions', [
            'subject_ids' => [$subject->id],
            'exam_type_id' => $examType->id,
        ])
        ->assertSuccessful()
        ->json('data.session_id');
    $session = MockSession::query()->findOrFail($sessionId);
    $attempt = $session->quizAttempt;
    $attempt->update(['started_at' => now()->subMinutes(31)]);
    $questionId = $attempt->question_order[0];

    $this->withToken($this->token)
        ->postJson("/api/v1/mock/sessions/{$sessionId}/submit", [
            'answers' => [
                $questionId => $fixture['correct_option_ids'][$questionId],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.score', 0)
        ->assertJsonPath('data.status', 'completed');

    $this->assertDatabaseHas('user_answers', [
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $questionId,
        'option_id' => null,
    ]);
});
