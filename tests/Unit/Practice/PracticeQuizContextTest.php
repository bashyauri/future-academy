<?php

declare(strict_types=1);

use App\Livewire\Practice\PracticeQuiz;
use App\Models\QuizAttempt;
use Tests\TestCase;

uses(TestCase::class);

it('matches only attempts with the same exam type, subject and year', function (): void {
    $component = app(PracticeQuiz::class);
    $component->exam_type = 1;
    $component->subject = 15;
    $component->year = 2024;

    $method = new ReflectionMethod($component, 'attemptMatchesSelectedContext');
    $method->setAccessible(true);

    $matching = new QuizAttempt;
    $matching->exam_type_id = 1;
    $matching->subject_id = 15;
    $matching->exam_year = 2024;

    $wrongSubject = new QuizAttempt;
    $wrongSubject->exam_type_id = 1;
    $wrongSubject->subject_id = 5;
    $wrongSubject->exam_year = 2024;

    expect($method->invoke($component, $matching))->toBeTrue()
        ->and($method->invoke($component, $wrongSubject))->toBeFalse();
});
