<?php

declare(strict_types=1);

use App\Livewire\Dashboard\ParentIndex;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

it('allows guardian to create and link a student from parent dashboard', function (): void {
    $guardian = User::factory()->create([
        'account_type' => 'guardian',
    ]);

    Livewire::actingAs($guardian)
        ->test(ParentIndex::class)
        ->set('newStudentName', 'Test Student')
        ->set('newStudentEmail', 'new-student@example.com')
        ->call('createStudent')
        ->assertHasNoErrors();

    $student = User::query()->where('email', 'new-student@example.com')->first();

    expect($student)->not->toBeNull();
    expect($student->account_type)->toBe('student');

    $isLinked = $guardian->children()->where('users.id', $student->id)->exists();

    expect($isLinked)->toBeTrue();
});

it('shows mixed paid and unpaid student dashboard actions for guardians', function (): void {
    $guardian = User::factory()->create([
        'account_type' => 'guardian',
    ]);

    $paidStudent = User::factory()->create([
        'account_type' => 'student',
    ]);

    $unpaidStudent = User::factory()->create([
        'account_type' => 'student',
    ]);

    $guardian->children()->syncWithoutDetaching([
        $paidStudent->id => [
            'is_active' => true,
            'linked_at' => now(),
        ],
        $unpaidStudent->id => [
            'is_active' => true,
            'linked_at' => now(),
        ],
    ]);

    Subscription::create([
        'user_id' => $guardian->id,
        'student_id' => $paidStudent->id,
        'plan' => 'monthly',
        'type' => 'recurring',
        'status' => 'active',
        'is_active' => true,
        'amount' => 5000,
        'reference' => 'smoke-test-ref',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);

    Livewire::actingAs($guardian)
        ->test(ParentIndex::class)
        ->assertSee('Track Progress')
        ->assertSee('Unlock Progress')
        ->assertSee('View Performance')
        ->assertSee('Unlock Performance');
});

it('does not unlock all linked students when guardian has only self-scoped subscription', function (): void {
    $guardian = User::factory()->create([
        'account_type' => 'guardian',
    ]);

    $firstStudent = User::factory()->create([
        'account_type' => 'student',
    ]);

    $secondStudent = User::factory()->create([
        'account_type' => 'student',
    ]);

    $guardian->children()->syncWithoutDetaching([
        $firstStudent->id => [
            'is_active' => true,
            'linked_at' => now(),
        ],
        $secondStudent->id => [
            'is_active' => true,
            'linked_at' => now(),
        ],
    ]);

    Subscription::create([
        'user_id' => $guardian->id,
        'student_id' => null,
        'plan' => 'monthly',
        'type' => 'recurring',
        'status' => 'active',
        'is_active' => true,
        'amount' => 5000,
        'reference' => 'guardian-self-scope',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);

    Livewire::actingAs($guardian)
        ->test(ParentIndex::class)
        ->assertSee('Unlock Progress')
        ->assertSee('Unlock Performance')
        ->assertDontSee('Track Progress')
        ->assertDontSee('View Performance');
});
