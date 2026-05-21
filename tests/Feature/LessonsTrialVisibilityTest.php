<?php

use App\Livewire\Lessons\LessonsList;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\Subscription;
use App\Models\Topic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

it('shows all lessons and marks paid lessons as locked for trial students', function () {
    $trialStudent = User::factory()->create([
        'account_type' => 'student',
        'trial_ends_at' => CarbonImmutable::now()->addDay(),
    ]);

    $subject = Subject::create([
        'name' => 'Mathematics',
        'slug' => 'mathematics',
    ]);

    $topic = Topic::create([
        'subject_id' => $subject->id,
        'name' => 'Algebra',
        'slug' => 'algebra',
    ]);

    $freeLesson = Lesson::create([
        'title' => 'Free Algebra Lesson',
        'subject_id' => $subject->id,
        'topic_id' => $topic->id,
        'status' => 'published',
        'is_free' => true,
    ]);

    $paidLesson = Lesson::create([
        'title' => 'Premium Algebra Lesson',
        'subject_id' => $subject->id,
        'topic_id' => $topic->id,
        'status' => 'published',
        'is_free' => false,
    ]);

    $this->actingAs($trialStudent);

    Livewire::test(LessonsList::class, ['subject' => $subject->id])
        ->assertSee($freeLesson->title)
        ->assertSee($paidLesson->title)
        ->assertSee('Locked');
});

it('does not mark paid lessons as locked for subscribed students', function () {
    $subscribedStudent = User::factory()->create([
        'account_type' => 'student',
        'trial_ends_at' => CarbonImmutable::now()->subDay(),
    ]);

    Subscription::create([
        'user_id' => $subscribedStudent->id,
        'plan' => 'monthly',
        'status' => 'active',
        'is_active' => true,
        'starts_at' => now(),
        'ends_at' => now()->addWeek(),
        'type' => 'recurring',
    ]);

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
    ]);

    $topic = Topic::create([
        'subject_id' => $subject->id,
        'name' => 'Mechanics',
        'slug' => 'mechanics',
    ]);

    $paidLesson = Lesson::create([
        'title' => 'Premium Mechanics Lesson',
        'subject_id' => $subject->id,
        'topic_id' => $topic->id,
        'status' => 'published',
        'is_free' => false,
    ]);

    $this->actingAs($subscribedStudent);

    Livewire::test(LessonsList::class, ['subject' => $subject->id])
        ->assertSee($paidLesson->title)
        ->assertDontSee('Locked');
});
