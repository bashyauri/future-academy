<?php

use App\Models\ExamType;
use App\Models\Stream;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('onboarding bootstrap returns streams exam types and subjects without authentication', function () {
    $subject = Subject::query()->create([
        'name' => 'Mathematics',
        'code' => 'MTH-001',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $stream = Stream::query()->create([
        'name' => 'Science',
        'description' => 'Science students',
        'icon' => 'science',
        'default_subjects' => [$subject->id],
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $examType = ExamType::query()->create([
        'name' => 'JAMB',
        'description' => 'Joint Admissions and Matriculation Board',
        'exam_format' => 'computer_based_test',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->getJson('/api/v1/onboarding/bootstrap')
        ->assertSuccessful()
        ->assertJsonPath('data.streams.0.id', $stream->id)
        ->assertJsonPath('data.streams.0.default_subject_ids.0', $subject->id)
        ->assertJsonPath('data.subjects.0.id', $subject->id)
        ->assertJsonPath('data.exam_types.0.id', $examType->id);
});