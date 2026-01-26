<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\PaymentFailed;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Handle failed or cancelled payments
        $event = $request->input('event');
        $data = $request->input('data', []);
    if (in_array($event, ['charge.failed', 'charge.cancelled', 'invoice.failed', 'subscription.disable'])) {
        $email = $data['customer']['email'] ?? null;
        $reference = $data['reference'] ?? null;
        $amount = isset($data['amount']) ? $data['amount'] / 100 : 0;
        if ($email && $reference) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->notify(new PaymentFailed($reference, $amount));
            }
        }
        return response('Webhook received', 200);
    }

    // Validate Paystack signature
    $signature = $request->header('x-paystack-signature');
    $payload = $request->getContent();
    $secret = config('services.paystack.secret_key');
    if (!$signature || !hash_equals(hash_hmac('sha512', $payload, $secret), $signature)) {
        Log::warning('Invalid Paystack webhook signature.', ['signature' => $signature]);
        return response('Invalid signature', 400);
    }

        $event = $request->input('event');
        $data = $request->input('data', []);
        Log::info('Paystack webhook received', ['event' => $event, 'data' => $data]);

        if ($event === 'charge.success' && ($data['status'] ?? null) === 'success') {
            $email = $data['customer']['email'] ?? null;
            if (!$email) {
                Log::warning('Paystack webhook: No customer email in payload.', ['data' => $data]);
                return response('No customer email', 400);
            }
            $user = User::where('email', $email)->first();
            if (!$user) {
                Log::warning('Paystack webhook: No user found for email.', ['email' => $email]);
                return response('User not found', 404);
            }
            $reference = $data['reference'] ?? null;
            if (!$reference) {
                Log::warning('Paystack webhook: No reference in payload.', ['data' => $data]);
                return response('No reference', 400);
            }
            $plan = $data['plan']['plan_code'] ?? 'custom';
            $type = $data['plan']['interval'] ?? 'one_time';
            $amount = isset($data['amount']) ? $data['amount'] / 100 : 0;
            $startsAt = now();
            $endsAt = $type === 'monthly' ? now()->addMonth() : ($type === 'yearly' ? now()->addYear() : now()->addMonth());

            // Use a transaction for atomicity
            DB::transaction(function () use ($user, $reference, $plan, $type, $amount, $startsAt, $endsAt) {
                // Mark all previous subscriptions as inactive and is_active = false
                Subscription::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive', 'is_active' => false]);

                // Create or update the new active subscription and set is_active = true
                $subscription = Subscription::updateOrCreate(
                    ['reference' => $reference],
                    [
                        'user_id' => $user->id,
                        'plan' => $plan,
                        'type' => $type,
                        'status' => 'active',
                        'is_active' => true,
                        'amount' => $amount,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                    ]
                );
                Log::info('Paystack subscription updated/created', ['subscription_id' => $subscription->id]);
            });
        }

        // Optionally handle other Paystack events (e.g., subscription.disable, invoice.failed)

        return response('Webhook received', 200);
    }
}
