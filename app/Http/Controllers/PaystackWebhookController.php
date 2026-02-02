<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\PaymentFailed;
use Carbon\Carbon;

class PaystackWebhookController extends Controller
{
    /**
     * Handle incoming Paystack webhook events.
     */
    public function handle(Request $request)
    {
        Log::channel('webhook')->info('========== WEBHOOK RECEIVED ==========', [
            'timestamp'  => now()->toDateTimeString(),
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers'    => $request->headers->all(),
            'raw_payload'=> substr($request->getContent(), 0, 500), // limited for safety
        ]);

        try {
            // 1. Verify signature (critical - must be first)
            $signature = $request->header('x-paystack-signature');
            $payload   = $request->getContent();
            $secret    = config('services.paystack.secret_key');

            if (!$signature || !hash_equals(hash_hmac('sha512', $payload, $secret), $signature)) {
                Log::channel('webhook')->error('❌ Invalid Paystack webhook signature', [
                    'signature' => $signature,
                    'ip'        => $request->ip(),
                    'payload'   => substr($payload, 0, 200),
                ]);
                return response('Invalid signature', 401);
            }

            Log::channel('webhook')->info('✅ Signature verified successfully');

            $event = $request->input('event');
            $data  = $request->input('data', []);

            Log::channel('webhook')->info('📥 Webhook Event Details', [
                'event'             => $event,
                'reference'         => $data['reference']         ?? 'n/a',
                'subscription_code' => $data['subscription']['subscription_code'] ?? 'n/a',
                'customer_email'    => $data['customer']['email'] ?? 'n/a',
                'amount'            => isset($data['amount']) ? ($data['amount'] / 100) : 'n/a',
                'plan_code'         => $data['plan']['plan_code'] ?? 'n/a',
            ]);

            // 2. Handle failure/cancellation events early
            if (in_array($event, [
                'charge.failed',
                'invoice.payment_failed',
                'subscription.disable',
                'subscription.expiring_cards',
            ])) {
                Log::channel('webhook')->info('🔔 Processing failure event: ' . $event);
                $this->handleFailureEvent($data);
                Log::channel('webhook')->info('✅ Failure event processed');
                return response('Webhook processed', 200);
            }

            // 3. Handle main subscription lifecycle events
            Log::channel('webhook')->info('🔄 Processing event: ' . $event);

            $handled = match ($event) {
                'charge.success'         => $this->handleChargeSuccess($data),
                'subscription.create'    => $this->handleSubscriptionCreate($data),
                'subscription.not_renew' => $this->handleSubscriptionNotRenew($data),
                'subscription.disable'   => $this->handleSubscriptionDisable($data),
                default                  => false,
            };

            if ($handled === false) {
                Log::channel('webhook')->warning('⚠️ Unhandled Paystack webhook event', ['event' => $event]);
            } else {
                Log::channel('webhook')->info('✅ Event processed successfully: ' . $event);
            }

            Log::channel('webhook')->info('========== WEBHOOK COMPLETED ==========');
            return response('Webhook received', 200);
        } catch (\Exception $e) {
            Log::channel('webhook')->error('❌ WEBHOOK CRITICAL ERROR', [
                'message'     => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
                'trace'       => $e->getTraceAsString(),
                'request_data'=> $request->all(),
            ]);

            // Always return 200 to Paystack to stop retries
            return response('Webhook error logged', 200);
        }
    }

    /**
     * Handle successful charge (initial or recurring payment)
     */
    private function handleChargeSuccess(array $data): bool
    {
        Log::channel('webhook')->info('💰 Processing charge.success');

        if (($data['status'] ?? null) !== 'success') {
            Log::channel('webhook')->warning('⚠️ Charge status is not success', ['status' => $data['status'] ?? null]);
            return false;
        }

        $email     = $data['customer']['email'] ?? null;
        $reference = $data['reference'] ?? null;

        if (!$email || !$reference) {
            Log::channel('webhook')->error('❌ charge.success missing required fields', ['data' => $data]);
            return false;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            Log::channel('webhook')->error('❌ User not found for charge.success', ['email' => $email]);
            return false;
        }

        $subData   = $data['subscription'] ?? [];
        $subCode   = $subData['subscription_code'] ?? null;
        $nextDate  = $subData['next_payment_date'] ?? null;
        $planCode  = $data['plan']['plan_code'] ?? 'custom';
        $interval  = $data['plan']['interval'] ?? 'monthly';
        $amount    = ($data['amount'] ?? 0) / 100;

        try {
            DB::transaction(function () use ($user, $reference, $subCode, $planCode, $interval, $amount, $nextDate) {
                Log::channel('webhook')->info('💾 Starting DB transaction for charge.success');

                // Deactivate previous active subscriptions (skip current if renewal)
                $deactivated = Subscription::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->when($subCode, fn($q) => $q->where('subscription_code', '!=', $subCode))
                    ->update([
                        'status'    => 'inactive',
                        'is_active' => false,
                    ]);

                Log::channel('webhook')->info('📝 Deactivated previous subscriptions', ['count' => $deactivated]);

                $endsAt = $nextDate
                    ? Carbon::parse($nextDate)->utc()
                    : $this->calculateFallbackEndsAt($interval);

                $fields = [
                    'user_id'           => $user->id,
                    'plan'              => $planCode,
                    'plan_code'         => $planCode,
                    'subscription_code' => $subCode,
                    'reference'         => $reference,
                    'amount'            => $amount,
                    'status'            => 'active',
                    'is_active'         => true,
                    'starts_at'         => now(),
                    'ends_at'           => $endsAt,
                    'next_billing_date' => $nextDate ? Carbon::parse($nextDate)->utc() : null,
                ];

                // Idempotent: update or create
                $subscription = Subscription::updateOrCreate(
                    ['reference' => $reference],
                    $fields
                );

                Log::channel('webhook')->info('✅ Subscription upserted', [
                    'subscription_id'   => $subscription->id,
                    'subscription_code' => $subscription->subscription_code,
                    'was_recently_created' => $subscription->wasRecentlyCreated ?? false,
                ]);
            });

            Log::channel('webhook')->info('✅ Transaction committed successfully');
        } catch (\Exception $e) {
            Log::channel('webhook')->error('❌ Error in charge.success handler', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return false;
        }

        Log::channel('webhook')->info('✅ Subscription activated via charge.success', [
            'user_id'   => $user->id,
            'reference' => $reference,
            'sub_code'  => $subCode,
        ]);

        return true;
    }

    /**
     * Handle new subscription creation event
     */
    private function handleSubscriptionCreate(array $data): bool
    {
        Log::channel('webhook')->info('🎉 Processing subscription.create');

        $subCode = $data['subscription']['subscription_code'] ?? null;
        if (!$subCode) {
            Log::channel('webhook')->warning('subscription.create missing subscription_code — skipping', ['data' => $data]);
            return false;
        }

        $email = $data['customer']['email'] ?? null;
        if (!$email) {
            Log::channel('webhook')->error('subscription.create missing email', ['data' => $data]);
            return false;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            Log::channel('webhook')->error('User not found for subscription.create', ['email' => $email]);
            return false;
        }

        $nextDate  = $data['subscription']['next_payment_date'] ?? null;
        $planCode  = $data['plan']['plan_code'] ?? 'custom';
        $interval  = $data['plan']['interval'] ?? 'monthly';

        $amount = 0;
        if (isset($data['amount'])) {
            $amount = $data['amount'] / 100;
        } elseif (isset($data['subscription']['amount'])) {
            $amount = $data['subscription']['amount'] / 100;
        }

        $endsAt = $nextDate
            ? Carbon::parse($nextDate)->utc()
            : $this->calculateFallbackEndsAt($interval);

        Subscription::updateOrCreate(
            ['subscription_code' => $subCode],
            [
                'user_id'           => $user->id,
                'plan'              => $planCode,
                'plan_code'         => $planCode,
                'subscription_code' => $subCode,
                'amount'            => $amount,
                'status'            => 'active',
                'is_active'         => true,
                'starts_at'         => now(),
                'ends_at'           => $endsAt,
                'next_billing_date' => $nextDate ? Carbon::parse($nextDate)->utc() : null,
            ]
        );

        Log::channel('webhook')->info('✅ New subscription created from webhook', [
            'subscription_code' => $subCode,
            'user_id'           => $user->id,
        ]);

        return true;
    }

    // ────────────────────────────────────────────────
    // Other event handlers (unchanged but with logging)
    // ────────────────────────────────────────────────

    private function handleSubscriptionNotRenew(array $data): bool
    {
        Log::channel('webhook')->info('⏸️ Processing subscription.not_renew');

        $subCode = $data['subscription']['subscription_code'] ?? null;
        if (!$subCode) {
            Log::channel('webhook')->warning('subscription.not_renew missing subscription_code — skipping');
            return false;
        }

        Subscription::where('subscription_code', $subCode)->update([
            'status'       => 'non_renewing',
            'is_active'    => true,
            'cancelled_at' => now(),
        ]);

        Log::channel('webhook')->info('✅ Subscription set to non-renewing', ['subscription_code' => $subCode]);

        return true;
    }

    private function handleSubscriptionDisable(array $data): bool
    {
        Log::channel('webhook')->info('🛑 Processing subscription.disable');

        $subCode = $data['subscription']['subscription_code'] ?? null;
        if (!$subCode) {
            Log::channel('webhook')->warning('subscription.disable missing subscription_code — skipping');
            return false;
        }

        Subscription::where('subscription_code', $subCode)->update([
            'status'       => 'cancelled',
            'is_active'    => false,
            'cancelled_at' => now(),
        ]);

        Log::channel('webhook')->info('✅ Subscription disabled', ['subscription_code' => $subCode]);

        return true;
    }

    private function handleFailureEvent(array $data): void
    {
        Log::channel('webhook')->warning('⚠️ Processing failure event');

        $email = $data['customer']['email']
            ?? $data['subscription']['customer']['email']
            ?? null;

        $reference = $data['reference'] ?? null;
        $amount    = isset($data['amount']) ? $data['amount'] / 100 : 0;

        if ($email && $reference) {
            $user = User::where('email', $email)->first();
            if ($user) {
                try {
                    $user->notify(new PaymentFailed($reference, $amount));
                    Log::channel('webhook')->info('✅ Payment failure notification sent', [
                        'email'     => $email,
                        'reference' => $reference,
                    ]);
                } catch (\Exception $e) {
                    Log::channel('webhook')->error('❌ Failed to send payment failure notification', [
                        'message' => $e->getMessage(),
                        'email'   => $email,
                    ]);
                }
            }
        }
    }

    // ────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────

    private function calculateFallbackEndsAt(string $interval): Carbon
    {
        return match (strtolower($interval)) {
            'yearly'  => now()->addYear(),
            'monthly' => now()->addMonth(),
            default   => now()->addMonth(),
        };
    }
}
