<?php

declare(strict_types=1);

use App\Livewire\Practice\PracticeHome;
use App\Models\QuizAttempt;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('shows single-subject jamb attempts in resume visibility rules', function (): void {
    $component = app(PracticeHome::class);

    $method = new ReflectionMethod($component, 'shouldDisplayResumeAttempt');
    $method->setAccessible(true);

    $singleSubjectJambAttempt = new QuizAttempt;
    $singleSubjectJambAttempt->question_order = [101, 102, 103];
    $singleSubjectJambAttempt->time_taken_seconds = 0;
    $singleSubjectJambAttempt->started_at = Carbon::now()->subMinutes(5);

    $multiSubjectJambAttempt = new QuizAttempt;
    $multiSubjectJambAttempt->question_order = [
        10 => [1, 2],
        11 => [3, 4],
        12 => [5, 6],
        13 => [7, 8],
    ];
    $multiSubjectJambAttempt->time_taken_seconds = 0;
    $multiSubjectJambAttempt->started_at = Carbon::now()->subMinutes(5);

    expect($method->invoke($component, $singleSubjectJambAttempt))->toBeTrue()
        ->and($method->invoke($component, $multiSubjectJambAttempt))->toBeFalse();
});
