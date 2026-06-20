<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;

uses(RefreshDatabase::class);

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'device_name' => 'Test Device',
    ]);

    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('message')
            ->has('token')
            ->has('user')
            ->where('message', 'Login successful')
        );

    expect($response->json('token'))->not->toBeEmpty();
});

test('login fails with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
        'device_name' => 'Test Device',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('login fails with inactive account', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'is_active' => false,
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'device_name' => 'Test Device',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('login validation requires email', function () {
    $response = $this->postJson('/api/v1/login', [
        'password' => 'password123',
        'device_name' => 'Test Device',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('login validation requires password', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'device_name' => 'Test Device',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('login validation requires device_name', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['device_name']);
});

test('authenticated user can access profile endpoint', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/user');

    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('user')
            ->where('user.id', $user->id)
            ->where('user.email', $user->email)
        );
});

test('unauthenticated user cannot access profile endpoint', function () {
    $response = $this->getJson('/api/v1/user');

    $response->assertStatus(401);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/logout');

    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Logged out successfully')
        );

    // Token should be deleted
    expect($user->tokens()->count())->toBe(0);
});

test('user cannot access protected routes after logout', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $token = $user->createToken('Test Device')->plainTextToken;

    // Logout
    $this->withToken($token)
        ->postJson('/api/v1/logout')
        ->assertStatus(200);

    // Try to access protected route
    $response = $this->withToken($token)
        ->getJson('/api/v1/user');

    $response->assertStatus(401);
});

test('invalid token cannot access protected routes', function () {
    $response = $this->withToken('invalid-token')
        ->getJson('/api/v1/user');

    $response->assertStatus(401);
});
