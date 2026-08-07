<?php

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can complete onboarding without email verification', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $subject = Subject::query()->create([
        'name' => 'Mathematics',
        'code' => 'MTH-001',
        'is_active' => true,
    ]);

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/onboarding', [
        'stream' => 'manual',
        'exam_types' => [1],
        'subjects' => [$subject->id],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Onboarding completed successfully.');

    expect($user->fresh()->has_completed_onboarding)->toBeTrue();
    expect($user->enrollments()->count())->toBe(1);
});