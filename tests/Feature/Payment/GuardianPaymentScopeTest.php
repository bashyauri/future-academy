<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\PaymentService;

it('requires guardians to select a linked student during payment initialization', function (): void {
    $guardian = User::factory()->create([
        'account_type' => 'guardian',
    ]);

    $response = $this->actingAs($guardian)
        ->from(route('payment.pricing'))
        ->post(route('payment.initialize'), [
            'plan' => 'monthly',
            'type' => 'one_time',
        ]);

    $response->assertRedirect(route('payment.pricing'));
    $response->assertSessionHasErrors(['student_id']);
});

it('blocks guardian callback when selected student is missing from session', function (): void {
    $guardian = User::factory()->create([
        'account_type' => 'guardian',
    ]);

    $paymentService = Mockery::mock(PaymentService::class);
    $paymentService->shouldNotReceive('verifyPaystack');
    app()->instance(PaymentService::class, $paymentService);

    $response = $this->actingAs($guardian)
        ->get(route('payment.callback', ['reference' => 'guardian-callback-no-student']));

    $response->assertRedirect('/payment/pricing');
    $response->assertSessionHasErrors(['payment']);

    $this->assertDatabaseCount('subscriptions', 0);
});
