<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\Fluent\AssertableJson;

uses(RefreshDatabase::class);

test('student can register from the mobile api', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Amina Yusuf',
        'email' => 'amina@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'account_type' => 'student',
        'device_name' => 'Test Device',
    ]);

    $response->assertCreated()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Registration successful')
            ->has('token')
            ->where('user.name', 'Amina Yusuf')
            ->where('user.email', 'amina@example.com')
            ->where('user.account_type', 'student')
            ->where('user.has_completed_onboarding', false)
        );

    $user = User::where('email', 'amina@example.com')->firstOrFail();

    expect($response->json('token'))->not->toBeEmpty()
        ->and($user->isStudent())->toBeTrue()
        ->and($user->trial_ends_at)->not->toBeNull()
        ->and($user->tokens)->toHaveCount(1);

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('guardian can register from the mobile api without trial access', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Mrs Bello',
        'email' => 'guardian@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'account_type' => 'guardian',
        'device_name' => 'Test Device',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.account_type', 'guardian');

    $user = User::where('email', 'guardian@example.com')->firstOrFail();

    expect($user->isParent())->toBeTrue()
        ->and($user->trial_ends_at)->toBeNull();
});

test('mobile registration rejects duplicate emails', function () {
    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'account_type' => 'student',
        'device_name' => 'Test Device',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('mobile registration validates account type and password confirmation', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Invalid User',
        'email' => 'invalid@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different-password',
        'account_type' => 'teacher',
        'device_name' => 'Test Device',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password', 'account_type']);
});
