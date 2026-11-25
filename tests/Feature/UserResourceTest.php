<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('verified and unverified user scopes separate users correctly', function () {
    Role::firstOrCreate(['name' => 'super-admin']);

    $verifiedUser = User::factory()->create([
        'email' => 'verified@example.com',
        'email_verified_at' => now(),
    ]);

    $unverifiedUser = User::factory()->create([
        'email' => 'unverified@example.com',
        'email_verified_at' => null,
    ]);

    $verifiedList = User::query()->whereNotNull('email_verified_at')->pluck('id')->all();
    $unverifiedList = User::query()->whereNull('email_verified_at')->pluck('id')->all();

    expect($verifiedList)->toContain($verifiedUser->id)
        ->not->toContain($unverifiedUser->id)
        ->and($unverifiedList)->toContain($unverifiedUser->id)
        ->not->toContain($verifiedUser->id);
});

test('admin can delete unverified spam user', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create([
        'account_type' => 'admin',
    ]);
    $admin->assignRole($adminRole);

    $spamUser = User::factory()->create([
        'email' => 'spambot@example.com',
        'email_verified_at' => null,
        'account_type' => 'student',
    ]);

    $this->actingAs($admin);

    expect(User::find($spamUser->id))->not->toBeNull();

    $spamUser->delete();

    expect(User::find($spamUser->id))->toBeNull();
});
