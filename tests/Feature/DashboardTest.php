<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')->assertStatus(200);
});

test('super-admin switching role context redirects directly to parent or student dashboard instead of admin', function () {
    $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
    $guardianRole = Role::firstOrCreate(['name' => 'guardian']);
    $studentRole = Role::firstOrCreate(['name' => 'student']);

    $user1 = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $user1->assignRole([$superAdminRole, $guardianRole, $studentRole]);

    $this->actingAs($user1)
        ->post(route('role-context.switch'), ['context' => 'guardian'])
        ->assertRedirect(route('parent.dashboard'));

    $user2 = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $user2->assignRole([$superAdminRole, $guardianRole, $studentRole]);

    $this->actingAs($user2)
        ->post(route('role-context.switch'), ['context' => 'student'])
        ->assertRedirect(route('student.dashboard'));
});
