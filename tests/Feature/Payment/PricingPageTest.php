<?php

declare(strict_types=1);

use App\Livewire\Payment\Pricing;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\mock;

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

test('payment initialize returns json authorization url for ajax fetch requests', function () {
    $student = User::factory()->create([
        'account_type' => 'student',
    ]);

    mock(PaymentService::class, function ($mock) {
        $mock->shouldReceive('generateReference')
            ->once()
            ->andReturn('REF_TEST_123');

        $mock->shouldReceive('initializePaystack')
            ->once()
            ->andReturn([
                'success' => true,
                'authorization_url' => 'https://checkout.paystack.com/test123',
            ]);
    });

    $response = $this->actingAs($student)->postJson('/payment/initialize', [
        'plan' => 'monthly',
        'type' => 'one_time',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.authorization_url', 'https://checkout.paystack.com/test123')
        ->assertJsonPath('data.reference', 'REF_TEST_123');
});
