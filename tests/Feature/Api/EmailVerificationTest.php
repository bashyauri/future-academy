<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\MobileVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

describe('mobile email verification', function () {

    it('sends a verification notification on registration', function () {
        $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => 'student',
            'device_name' => 'Test Phone',
        ])->assertCreated();

        Notification::assertSentTo(
            User::where('email', 'test@example.com')->first(),
            MobileVerifyEmail::class,
        );
    });

    it('verifies the email with a valid signed URL', function () {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.email.verify',
            now()->addHour(),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($url)->assertOk()->assertSee('Email Verified');

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    });

    it('does not verify with a tampered hash', function () {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.email.verify',
            now()->addHour(),
            ['id' => $user->id, 'hash' => 'badbadhash'],
        );

        $this->get($url)->assertStatus(400)->assertSee('invalid or has expired');

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });

    it('does not verify with an expired signed URL', function () {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.email.verify',
            now()->subHour(),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($url)->assertStatus(400)->assertSee('invalid or has expired');

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });

    it('returns success even if the email was already verified', function () {
        $user = User::factory()->create(); // verified by default

        $url = URL::temporarySignedRoute(
            'api.email.verify',
            now()->addHour(),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($url)->assertOk()->assertSee('Email Verified');
    });

    it('can resend the verification notification', function () {
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/email/verification-notification')
            ->assertOk()
            ->assertJson(['message' => 'Verification link sent.']);

        Notification::assertSentTo($user, MobileVerifyEmail::class);
    });

    it('returns already verified message when email is already verified', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/email/verification-notification')
            ->assertOk()
            ->assertJson(['message' => 'Email already verified.']);
    });

});
