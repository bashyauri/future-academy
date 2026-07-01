<?php

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can get enrolled active subjects', function () {
    $user = User::factory()->create();

    $activeSubject = Subject::query()->create([
        'name' => 'Mathematics',
        'code' => 'MTH-001',
        'is_active' => true,
    ]);

    $inactiveSubject = Subject::query()->create([
        'name' => 'Government',
        'code' => 'GOV-001',
        'is_active' => false,
    ]);

    $user->enrollments()->create([
        'subject_id' => $activeSubject->id,
        'enrolled_by' => $user->id,
        'is_active' => true,
        'enrolled_at' => now(),
    ]);

    $user->enrollments()->create([
        'subject_id' => $inactiveSubject->id,
        'enrolled_by' => $user->id,
        'is_active' => true,
        'enrolled_at' => now(),
    ]);

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/subjects');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'message',
            'data' => [
                '*' => ['id', 'name', 'code', 'slug', 'icon', 'color', 'is_active'],
            ],
        ])
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $activeSubject->id);
});

test('subjects endpoint falls back to selected_subjects when enrollments do not exist', function () {
    $user = User::factory()->create();

    $activeSubject = Subject::query()->create([
        'name' => 'English Language',
        'code' => 'ENG-001',
        'is_active' => true,
    ]);

    $inactiveSubject = Subject::query()->create([
        'name' => 'Civic Education',
        'code' => 'CVE-001',
        'is_active' => false,
    ]);

    $user->update([
        'selected_subjects' => [$activeSubject->id, $inactiveSubject->id],
    ]);

    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/subjects');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $activeSubject->id);
});

test('invalid token cannot access enrolled subjects endpoint', function () {
    $this->withToken('invalid-token')
        ->getJson('/api/v1/subjects')
        ->assertUnauthorized();
});

test('authenticated user can access subject download endpoint without subscription', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Test Device')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/subjects/999999/download')
        ->assertNotFound();
});
