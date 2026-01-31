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
        // 1. Verify signature (must be first)
        $signature = $request->header('x-paystack-signature');
        $payload   = $request->getContent();
        $secret    = config('services.paystack.secret_key');

        if (!$signature || !hash_equals(hash_hmac('sha512', $payload, $secret), $signature)) {
            Log::warning('Invalid Paystack webhook signature', [
                'signature' => $signature,
                'ip'        => $request->ip(),
                'payload'   => substr($payload, 0, 200), // partial for debugging
            ]);
            return response('Invalid signature', 401);
        }

        $event = $request->input('event');
        $data  = $request->input('data', []);

        Log::info('Paystack webhook received', [
            'event'             => $event,
            'reference'         => $data['reference'] ?? 'n/a',
            'subscription_code' => $data['subscription']['subscription_code'] ?? 'n/a',
        ]);

        // 2. Handle failure/cancellation events early (no DB write needed)
        if (in_array($event, [
            'charge.failed',
            'invoice.payment_failed',
            'subscription.disable',
            'subscription.expiring_cards',
        ])) {
            $this->handleFailureEvent($data);
            return response('Webhook processed', 200);
        }

        // 3. Handle main subscription lifecycle events
        $handled = match ($event) {
            'charge.success'         => $this->handleChargeSuccess($data),
            'subscription.create'    => $this->handleSubscriptionCreate($data),
            'subscription.not_renew' => $this->handleSubscriptionNotRenew($data),
            'subscription.disable'   => $this->handleSubscriptionDisable($data),
            default                  => false,
        };

        if ($handled === false) {
            Log::info('Unhandled Paystack webhook event', ['event' => $event]);
        }

        return response('Webhook received', 200);
    }

    /**
     * Handle charge.success (initial payment or renewal)
     */
    private function handleChargeSuccess(array $data): bool
    {
        if (($data['status'] ?? null) !== 'success') {
            return false;
        }

        $email     = $data['customer']['email'] ?? null;
        $reference = $data['reference'] ?? null;

        if (!$email || !$reference) {
            Log::warning('charge.success missing required fields', ['data' => $data]);
            return false;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            Log::warning('User not found for charge.success', ['email' => $email]);
            return false;
        }

        $subData   = $data['subscription'] ?? [];
        $subCode   = $subData['subscription_code'] ?? null;
        $nextDate  = $subData['next_payment_date'] ?? null;
        $planCode  = $data['plan']['plan_code'] ?? 'custom';
        $interval  = $data['plan']['interval'] ?? 'monthly';

        $amount = ($data['amount'] ?? 0) / 100;

        DB::transaction(function () use ($user, $reference, $subCode, $planCode, $interval, $amount, $nextDate) {
            // Deactivate previous active subscriptions (skip current if renewal)
            Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->when($subCode, fn($q) => $q->where('subscription_code', '!=', $subCode))
                ->update([
                    'status'    => 'inactive',
                    'is_active' => false,
                ]);

            $endsAt = $nextDate
                ? Carbon::parse($nextDate)->utc()
                : $this->calculateFallbackEndsAt($interval);

            Subscription::updateOrCreate(
                [
                    'reference'         => $reference,
                    'subscription_code' => $subCode ?? $reference,
                ],
                [
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
                ]
            );
        });

        Log::info('Subscription activated via charge.success', [
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

        if (!$subCode || !$email) {
            Log::warning('subscription.create missing required fields', $data);
            return false;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            Log::warning('User not found for subscription.create', ['email' => $email]);
            return false;
        }

        $nextDate  = $data['subscription']['next_payment_date'] ?? null;
        $planCode  = $data['plan']['plan_code'] ?? 'custom';
        $interval  = $data['plan']['interval'] ?? 'monthly';
        $planName  = $interval === 'monthly' ? 'monthly' : ($interval === 'yearly' ? 'yearly' : 'custom');

        // Unify ends_at logic for recurring plans
        $endsAt = null;
        if ($interval === 'monthly') {
            if ($nextDate && Carbon::parse($nextDate)->diffInDays(now()) >= 28 && Carbon::parse($nextDate)->diffInDays(now()) <= 32) {
                $endsAt = Carbon::parse($nextDate)->utc();
            } else {
                $endsAt = now()->addMonth();
            }
        } elseif ($interval === 'yearly') {
            if ($nextDate && Carbon::parse($nextDate)->diffInDays(now()) >= 360 && Carbon::parse($nextDate)->diffInDays(now()) <= 370) {
                $endsAt = Carbon::parse($nextDate)->utc();
            } else {
                $endsAt = now()->addYear();
            }
        } else {
            // fallback for custom/one-time
            $endsAt = now()->addMonth();
        }
        \Log::info('Unified ends_at for webhook subscription', [
            'plan_code' => $planCode,
            'interval' => $interval,
            'next_payment_date' => $nextDate,
            'ends_at' => $endsAt,
        ]);

        Subscription::updateOrCreate(
            ['subscription_code' => $subCode],
            [
                'user_id'           => $user->id,
                'plan'              => $planName, // human-readable (monthly/yearly/custom)
                'plan_code'         => $planCode, // Paystack code (PLN_xxx)
                'subscription_code' => $subCode,
                'status'            => 'active',
                'is_active'         => true,
                'starts_at'         => now(),
                'ends_at'           => $endsAt,
                'next_billing_date' => $nextDate ? Carbon::parse($nextDate)->utc() : null,
            ]
        );

        Log::info('New subscription created', [
            'subscription_code' => $subCode,
            'user_id'           => $user->id,
        ]);

        return true;
    }

    /**
     * Handle subscription marked as non-renewing
     */
    private function handleSubscriptionNotRenew(array $data): bool
    {
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
            return false;
        }

        Subscription::where('subscription_code', $subCode)->update([
            'status'       => 'non_renewing',
            'is_active'    => true, // still active until ends_at
            'cancelled_at' => now(),
        ]);

        Log::info('Subscription set to non-renewing', ['subscription_code' => $subCode]);

        return true;
    }

    /**
     * Handle subscription fully disabled
     */
    private function handleSubscriptionDisable(array $data): bool
    {
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
            return false;
        }

        Subscription::where('subscription_code', $subCode)->update([
            'status'       => 'cancelled',
            'is_active'    => false,
            'cancelled_at' => now(),
        ]);

        Log::info('Subscription disabled', ['subscription_code' => $subCode]);

        return true;
    }

    /**
     * Handle payment failure or related events
     */
    private function handleFailureEvent(array $data): void
    {
        $email = $data['customer']['email']
            ?? $data['subscription']['customer']['email']
            ?? null;

        $reference = $data['reference'] ?? null;
        $amount    = isset($data['amount']) ? $data['amount'] / 100 : 0;

        if ($email && $reference) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->notify(new PaymentFailed($reference, $amount));
                Log::info('Payment failure notification sent', [
                    'email'     => $email,
                    'reference' => $reference,
                    'amount'    => $amount,
                ]);
            }
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
     * Parse Paystack date string safely
     */
    private function parsePaystackDate(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date)->utc() : null;
    }
}
