<?php

declare(strict_types=1);

use App\Livewire\Payment\Pricing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('student sees loaded plans without getting stuck loading', function () {
    $student = User::factory()->create([
        'account_type' => 'student',
    ]);

    Livewire::actingAs($student)
        ->test(Pricing::class)
        ->assertSet('loadingPlans', false)
        ->assertSet('isGuardian', false)
        ->assertSet('plans', config('pricing.plans'))
        ->assertSee('Monthly')
        ->assertSee('Yearly');
});
