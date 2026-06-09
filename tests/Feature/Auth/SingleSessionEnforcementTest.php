<?php

use App\Models\User;

test('older session is logged out when user has a different active session id', function () {
    $user = User::factory()->create([
        'current_session_id' => 'another-session-id',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect('/login')
        ->assertSessionHas('status', 'Your account was signed in on another device.');

    $this->assertGuest();
});

test('active session remains authenticated when current session id matches', function () {
    $this->withSession(['single_session_test' => true]);

    $sessionId = app('session')->getId();

    $user = User::factory()->create([
        'current_session_id' => $sessionId,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();

    $this->assertAuthenticatedAs($user);
});
