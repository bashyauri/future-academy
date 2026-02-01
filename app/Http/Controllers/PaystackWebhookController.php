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
        // Log incoming webhook request
        Log::channel('webhook')->info('========== WEBHOOK RECEIVED ==========', [
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'raw_payload' => $request->getContent(),
        ]);

        try {
            // 1. Verify signature (must be first)
            $signature = $request->header('x-paystack-signature');
            $payload   = $request->getContent();
            $secret    = config('services.paystack.secret_key');

            Log::channel('webhook')->debug('Signature verification', [
                'has_signature' => !empty($signature),
                'secret_configured' => !empty($secret),
            ]);

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
                'reference'         => $data['reference'] ?? 'n/a',
                'subscription_code' => $data['subscription']['subscription_code'] ?? 'n/a',
                'customer_email'    => $data['customer']['email'] ?? 'n/a',
                'customer_code'     => $data['customer']['customer_code'] ?? 'n/a',
                'amount'            => isset($data['amount']) ? ($data['amount'] / 100) : 'n/a',
                'plan_code'         => $data['plan']['plan_code'] ?? 'n/a',
                'full_data'         => $data,
            ]);

            // 2. Handle failure/cancellation events early (no DB write needed)
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
            Log::channel('webhook')->error('❌ WEBHOOK ERROR', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            // Still return 200 to prevent Paystack retries for application errors
            return response('Webhook error logged', 200);
        }
    }

    /**
     * Handle charge.success (initial payment or renewal)
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
            Log::channel('webhook')->error('❌ charge.success missing required fields', [
                'has_email' => !empty($email),
                'has_reference' => !empty($reference),
                'data' => $data
            ]);
            return false;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            Log::channel('webhook')->error('❌ User not found for charge.success', ['email' => $email]);
            return false;
        }

        Log::channel('webhook')->info('✅ User found', ['user_id' => $user->id, 'email' => $email]);

        $subData   = $data['subscription'] ?? [];
        $subCode   = $subData['subscription_code'] ?? null;
        $nextDate  = $subData['next_payment_date'] ?? null;
        $planCode  = $data['plan']['plan_code'] ?? 'custom';
        $interval  = $data['plan']['interval'] ?? 'monthly';
        $type      = $data['plan']['type'] ?? 'recurring';

        // Extract amount consistently (Paystack sends in kobo)
        $amount = 0;
        if (isset($data['amount'])) {
            $amount = $data['amount'] / 100;
        } elseif (isset($data['subscription']['amount'])) {
            $amount = $data['subscription']['amount'] / 100;
        }

        $authorizationCode = $data['authorization']['authorization_code'] ?? null;
        $emailToken = $data['subscription']['email_token'] ?? null;

        try {
            DB::transaction(function () use ($user, $reference, $subCode, $planCode, $interval, $amount, $nextDate, $type, $authorizationCode, $emailToken) {
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

                $endsAt = $this->calculateEndsAt($type, $planCode, $interval, $nextDate);

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
                'type'              => $type,
            ];
            if ($authorizationCode) {
                $fields['authorization_code'] = $authorizationCode;
            }
            if ($emailToken) {
                $fields['email_token'] = $emailToken;
            }

                Log::channel('webhook')->info('💾 Upserting subscription', [
                    'reference' => $reference,
                    'subscription_code' => $subCode ?? $reference,
                    'user_id' => $user->id,
                ]);

                // First, try to find by reference (handles case where callback created with reference as code)
                $existingByReference = Subscription::where('user_id', $user->id)
                    ->where('reference', $reference)
                    ->first();

                if ($existingByReference) {
                    // Update existing subscription with real subscription_code from webhook
                    Log::channel('webhook')->info('📝 Found existing subscription by reference, updating with real code', [
                        'subscription_id' => $existingByReference->id,
                        'old_code' => $existingByReference->subscription_code,
                        'new_code' => $subCode,
                    ]);
                    $existingByReference->update($fields);
                    $subscription = $existingByReference;
                } else {
                    // Otherwise create new subscription (e.g., webhook arrived before callback)
                    Log::channel('webhook')->info('➕ No existing subscription found by reference, creating new');
                    $subscription = Subscription::updateOrCreate(
                        ['reference' => $reference],
                        $fields
                    );
                }

                Log::channel('webhook')->info('✅ Subscription upserted', [
                    'subscription_id' => $subscription->id,
                    'subscription_code' => $subscription->subscription_code,
                    'was_recently_created' => $subscription->wasRecentlyCreated ?? false,
                ]);
            });

            Log::channel('webhook')->info('✅ Transaction committed successfully');

        } catch (\Exception $e) {
            Log::channel('webhook')->error('❌ Error in charge.success handler', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
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

        if (!empty($data['subscription']['subscription_code'])) {
            $subCode = $data['subscription']['subscription_code'];
        } elseif (!empty($data['reference'])) {
            $subCode = $data['reference'];
        } elseif (!empty($data['plan']['plan_code'])) {
            $subCode = $data['plan']['plan_code'];
        } else {
            $subCode = null;
        }
        $email   = $data['customer']['email'] ?? null;

        Log::channel('webhook')->info('📋 Extracted subscription data', [
            'subscription_code' => $subCode,
            'email' => $email,
        ]);

        if (!$subCode || !$email) {
            Log::channel('webhook')->error('❌ subscription.create missing required fields', [
                'has_subCode' => !empty($subCode),
                'has_email' => !empty($email),
                'data' => $data
            ]);
            return false;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            Log::channel('webhook')->error('❌ User not found for subscription.create', ['email' => $email]);
            return false;
        }

        Log::channel('webhook')->info('✅ User found', ['user_id' => $user->id]);

        $nextDate  = $data['subscription']['next_payment_date'] ?? null;
        $planCode  = $data['plan']['plan_code'] ?? 'custom';
        $interval  = $data['plan']['interval'] ?? 'monthly';
        $planName  = $interval === 'monthly' ? 'monthly' : ($interval === 'yearly' ? 'yearly' : 'custom');
        $type      = $data['plan']['type'] ?? 'recurring';
        // Extract amount (Paystack sends in kobo, so divide by 100)
        $amount = 0;
        if (isset($data['amount'])) {
            $amount = $data['amount'] / 100;
        } elseif (isset($data['subscription']['amount'])) {
            $amount = $data['subscription']['amount'] / 100;
        }
        $authorizationCode = $data['authorization']['authorization_code'] ?? null;
        $emailToken = $data['subscription']['email_token'] ?? null;

        // Calculate ends_at using unified logic
        $endsAt = $this->calculateEndsAt($type, $planName, $interval, $nextDate);

        $fields = [
            'user_id'           => $user->id,
            'plan'              => $planName, // human-readable (monthly/yearly/custom)
            'plan_code'         => $planCode, // Paystack code (PLN_xxx)
            'subscription_code' => $subCode,
            'reference'         => $data['reference'] ?? $subCode ?? '',
            'amount'            => $amount,
            'status'            => 'active',
            'is_active'         => true,
            'starts_at'         => now(),
            'ends_at'           => $endsAt,
            'next_billing_date' => $nextDate ? Carbon::parse($nextDate)->utc() : null,
            'type'              => $type,
        ];
        if ($authorizationCode) {
            $fields['authorization_code'] = $authorizationCode;
        }
        if ($emailToken) {
            $fields['email_token'] = $emailToken;
        }

        try {
            // First try to find existing subscription by reference (might have FA-xxx subscription_code)
            $reference = $data['reference'] ?? null;
            $existingSubscription = null;

            Log::channel('webhook')->info('🔍 Looking for existing subscription by reference', ['reference' => $reference]);

            if ($reference) {
                $existingSubscription = Subscription::where('reference', $reference)
                    ->where('user_id', $user->id)
                    ->first();
            }

            // If found by reference, update it with the real SUB_xxx code
            if ($existingSubscription) {
                Log::channel('webhook')->info('📝 Found existing subscription by reference', [
                    'subscription_id' => $existingSubscription->id,
                    'old_subscription_code' => $existingSubscription->subscription_code,
                    'new_subscription_code' => $subCode,
                ]);

                $existingSubscription->update($fields);

                Log::channel('webhook')->info('✅ Subscription updated with real SUB code from webhook', [
                    'subscription_id' => $existingSubscription->id,
                    'old_code' => $existingSubscription->subscription_code,
                    'new_code' => $subCode,
                    'reference' => $reference,
                ]);
            } else {
                Log::channel('webhook')->info('➕ No existing subscription found, creating new one');

                // Otherwise, create or update by subscription_code
                $subscription = Subscription::updateOrCreate(
                    ['subscription_code' => $subCode],
                    $fields
                );

                Log::channel('webhook')->info('✅ New subscription created from webhook', [
                    'subscription_id' => $subscription->id,
                    'subscription_code' => $subCode,
                    'user_id'           => $user->id,
                    'was_recently_created' => $subscription->wasRecentlyCreated,
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::channel('webhook')->error('❌ Error in subscription.create handler', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Handle subscription marked as non-renewing
     */
    private function handleSubscriptionNotRenew(array $data): bool
    {
        Log::channel('webhook')->info('⏸️ Processing subscription.not_renew');

        if (!empty($data['subscription']['subscription_code'])) {
            $subCode = $data['subscription']['subscription_code'];
        } elseif (!empty($data['reference'])) {
            $subCode = $data['reference'];
        } elseif (!empty($data['plan']['plan_code'])) {
            $subCode = $data['plan']['plan_code'];
        } else {
            $subCode = null;
        }

        if (!$subCode) {
            Log::channel('webhook')->error('❌ subscription.not_renew missing subscription code', ['data' => $data]);
            return false;
        }

        try {
            $updated = Subscription::where('subscription_code', $subCode)->update([
                'status'       => 'non_renewing',
                'is_active'    => true, // still active until ends_at
                'cancelled_at' => now(),
            ]);

            Log::channel('webhook')->info('✅ Subscription set to non-renewing', [
                'subscription_code' => $subCode,
                'rows_updated' => $updated,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::channel('webhook')->error('❌ Error in subscription.not_renew handler', [
                'message' => $e->getMessage(),
                'subscription_code' => $subCode,
            ]);
            return false;
        }
    }

    /**
     * Handle subscription fully disabled
     */
    private function handleSubscriptionDisable(array $data): bool
    {
        Log::channel('webhook')->info('🛑 Processing subscription.disable');

        if (!empty($data['subscription']['subscription_code'])) {
            $subCode = $data['subscription']['subscription_code'];
        } elseif (!empty($data['reference'])) {
            $subCode = $data['reference'];
        } elseif (!empty($data['plan']['plan_code'])) {
            $subCode = $data['plan']['plan_code'];
        } else {
            $subCode = null;
        }

        if (!$subCode) {
            Log::channel('webhook')->error('❌ subscription.disable missing subscription code', ['data' => $data]);
            return false;
        }

        try {
            $updated = Subscription::where('subscription_code', $subCode)->update([
                'status'       => 'cancelled',
                'is_active'    => false,
                'cancelled_at' => now(),
            ]);

            Log::channel('webhook')->info('✅ Subscription disabled', [
                'subscription_code' => $subCode,
                'rows_updated' => $updated,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::channel('webhook')->error('❌ Error in subscription.disable handler', [
                'message' => $e->getMessage(),
                'subscription_code' => $subCode,
            ]);
            return false;
        }
    }

    /**
     * Handle payment failure or related events
     */
    private function handleFailureEvent(array $data): void
    {
        Log::channel('webhook')->warning('⚠️ Processing failure event');

        $email = $data['customer']['email']
            ?? $data['subscription']['customer']['email']
            ?? null;

        $reference = $data['reference'] ?? null;
        $amount    = isset($data['amount']) ? $data['amount'] / 100 : 0;

        Log::channel('webhook')->info('📝 Failure event data', [
            'email' => $email,
            'reference' => $reference,
            'amount' => $amount,
        ]);

        if ($email && $reference) {
            $user = User::where('email', $email)->first();
            if ($user) {
                try {
                    $user->notify(new PaymentFailed($reference, $amount));
                    Log::channel('webhook')->info('✅ Payment failure notification sent', [
                        'email'     => $email,
                        'reference' => $reference,
                        'amount'    => $amount,
                    ]);
                } catch (\Exception $e) {
                    Log::channel('webhook')->error('❌ Failed to send payment failure notification', [
                        'message' => $e->getMessage(),
                        'email' => $email,
                    ]);
                }
            } else {
                Log::channel('webhook')->warning('⚠️ User not found for failure event', ['email' => $email]);
            }
        } else {
            Log::channel('webhook')->warning('⚠️ Missing email or reference for failure event');
        }
    }

    // ────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────

    /**
     * Calculate fallback expiry when Paystack doesn't provide next_payment_date
     */
    private function calculateFallbackEndsAt(string $interval): Carbon
    {
        return match (strtolower($interval)) {
            'yearly'  => now()->addYear(),
            'monthly' => now()->addMonth(),
            default   => now()->addMonth(),
        };
    }

    /**
     * Calculate ends_at consistently for both recurring and one-time subscriptions
     * Prefers Paystack's next_payment_date if it matches the expected interval
     */
    private function calculateEndsAt(?string $type, ?string $plan, ?string $interval, ?string $nextPaymentDate): ?Carbon
    {
        if ($type === 'recurring') {
            if ($plan === 'monthly' || $interval === 'monthly') {
                // Use Paystack's date only if it's about a month ahead
                if ($nextPaymentDate && Carbon::parse($nextPaymentDate)->diffInDays(now()) >= 28 && Carbon::parse($nextPaymentDate)->diffInDays(now()) <= 32) {
                    return Carbon::parse($nextPaymentDate)->utc();
                }
                return now()->addMonth();
            } elseif ($plan === 'yearly' || $interval === 'yearly') {
                // Use Paystack's date only if it's about a year ahead
                if ($nextPaymentDate && Carbon::parse($nextPaymentDate)->diffInDays(now()) >= 360 && Carbon::parse($nextPaymentDate)->diffInDays(now()) <= 370) {
                    return Carbon::parse($nextPaymentDate)->utc();
                }
                return now()->addYear();
            }
        } else {
            // One-time payments
            if ($plan === 'monthly') {
                return now()->addMonth();
            } elseif ($plan === 'yearly') {
                return now()->addYear();
            }
        }

        return now()->addMonth(); // Default fallback
    }

    /**
     * Parse Paystack date string safely
     */
    private function parsePaystackDate(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date)->utc() : null;
    }
}
