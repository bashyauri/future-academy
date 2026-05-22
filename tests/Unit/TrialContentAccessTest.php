<?php

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Tests\TestCase;

uses(TestCase::class);

it('allows trial users to access only free lessons and quizzes', function () {
    $trialUser = User::factory()->make([
        'account_type' => 'student',
        'trial_ends_at' => CarbonImmutable::now()->addHours(48),
    ]);

    $freeLesson = new Lesson(['is_free' => true, 'status' => 'published']);
    $paidLesson = new Lesson(['is_free' => false, 'status' => 'published']);

    $freeQuiz = new Quiz(['is_free' => true, 'is_active' => true, 'status' => 'published']);
    $lessonFreeQuiz = new Quiz(['is_free' => false, 'is_active' => true, 'status' => 'published', 'lesson_id' => 1]);
    $lessonFreeQuiz->setRelation('lesson', $freeLesson);

    $paidQuiz = new Quiz(['is_free' => false, 'is_active' => true, 'status' => 'published']);

    expect($trialUser->onTrial())->toBeTrue()
        ->and($freeLesson->canUserAccess($trialUser))->toBeTrue()
        ->and($paidLesson->canUserAccess($trialUser))->toBeFalse()
        ->and($freeQuiz->canUserAccess($trialUser))->toBeTrue()
        ->and($lessonFreeQuiz->canUserAccess($trialUser))->toBeTrue()
        ->and($paidQuiz->canUserAccess($trialUser))->toBeFalse();
});

it('allows subscribed users to access paid lessons and quizzes', function () {
    $subscribedUser = User::factory()->make([
        'account_type' => 'student',
        'trial_ends_at' => null,
    ]);

    $subscribedUser->setRelation('currentSubscription', new Subscription([
        'status' => 'active',
        'ends_at' => CarbonImmutable::now()->addDays(7),
    ]));

    $paidLesson = new Lesson(['is_free' => false, 'status' => 'published']);
    $paidQuiz = new Quiz(['is_free' => false, 'is_active' => true, 'status' => 'published']);

    expect($subscribedUser->hasActiveSubscription())->toBeTrue()
        ->and($paidLesson->canUserAccess($subscribedUser))->toBeTrue()
        ->and($paidQuiz->canUserAccess($subscribedUser))->toBeTrue();
});

it('does not treat guardians as trial users', function () {
    $guardian = User::factory()->make([
        'account_type' => 'guardian',
        'trial_ends_at' => CarbonImmutable::now()->addHours(48),
    ]);

    expect($guardian->onTrial())->toBeFalse();
});

it('does not treat a student-linked guardian subscription as the guardian\'s own access', function () {
    $guardian = User::factory()->make([
        'account_type' => 'guardian',
        'trial_ends_at' => null,
    ]);

    $guardian->setRelation('currentSubscription', new Subscription([
        'status' => 'active',
        'student_id' => 99,
        'ends_at' => CarbonImmutable::now()->addDays(7),
    ]));

    expect($guardian->hasActiveSubscription())->toBeFalse();
});
