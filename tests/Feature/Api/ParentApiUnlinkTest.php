<?php

declare(strict_types=1);

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! Schema::hasTable('parent_student')) {
        Schema::create('parent_student', function ($table): void {
            $table->id();
            $table->foreignId('parent_id');
            $table->foreignId('student_id');
            $table->boolean('is_active')->default(true);
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();
        });
    }
    if (! Schema::hasTable('enrollments')) {
        Schema::create('enrollments', function ($table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('subject_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
});

it('allows guardian to unlink student via API and resets subscription link', function (): void {
    $guardian = User::factory()->create([
        'account_type' => 'guardian',
    ]);

    $student = User::factory()->create([
        'account_type' => 'student',
    ]);

    $guardian->children()->syncWithoutDetaching([
        $student->id => [
            'is_active' => true,
            'linked_at' => now(),
        ],
    ]);

    $sub = Subscription::create([
        'user_id' => $guardian->id,
        'student_id' => $student->id,
        'plan' => 'monthly',
        'type' => 'recurring',
        'status' => 'active',
        'is_active' => true,
        'amount' => 5000,
        'reference' => 'unlink-test-sub',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);

    Sanctum::actingAs($guardian);

    $response = $this->postJson("/api/parent/students/{$student->id}/unlink");

    $response->assertSuccessful();
    $response->assertJson([
        'message' => "Student {$student->name} has been unlinked successfully.",
    ]);

    expect($guardian->children()->where('users.id', $student->id)->exists())->toBeFalse();

    $sub->refresh();
    expect($sub->student_id)->toBeNull();
});
