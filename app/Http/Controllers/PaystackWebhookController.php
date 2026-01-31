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
        // 1. Always verify the signature first
        $signature = $request->header('x-paystack-signature');
        $payload   = $request->getContent();
        $secret    = config('services.paystack.secret_key');

        if (!$signature || !hash_equals(
            hash_hmac('sha512', $payload, $secret),
            $signature
        )) {
            Log::warning('Invalid Paystack webhook signature', [
                'signature' => $signature,
                'ip' => $request->ip(),
            ]);
            return response('Invalid signature', 401);
        }

        $event = $request->input('event');
        $data  = $request->input('data', []);

        Log::info('Paystack webhook received', [
            'event' => $event,
            'reference' => $data['reference'] ?? 'n/a',
            'subscription_code' => $data['subscription']['subscription_code'] ?? 'n/a',
        ]);

        // ────────────────────────────────────────────────
        // Handle failure / cancellation events early
        // ────────────────────────────────────────────────
        if (in_array($event, [
            'charge.failed',
            'invoice.payment_failed',
            'subscription.disable',
            'subscription.expiring_cards',
        ])) {
            $this->handleFailureEvent($data);
            return response('Webhook processed', 200);
        }

        // ────────────────────────────────────────────────
        // Main subscription lifecycle events
        // ────────────────────────────────────────────────
        $handled = match ($event) {
            'charge.success'          => $this->handleChargeSuccess($data),
            'subscription.create'     => $this->handleSubscriptionCreate($data),
            'subscription.not_renew'  => $this->handleSubscriptionNotRenew($data),
            'subscription.disable'    => $this->handleSubscriptionDisable($data),
            // 'invoice.create'       => optional: send reminder
            default                   => false,
        };

        if ($handled === false) {
            Log::info('Unhandled Paystack event', ['event' => $event]);
        }

        return response('Webhook received', 200);
    }

    private function handleChargeSuccess(array $data): bool
    {
        $email = $data['customer']['email'] ?? null;
        $reference = $data['reference'] ?? null;

        if (!$email || !$reference) {
            Log::warning('charge.success missing email or reference', $data);
            return false;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            Log::warning('User not found for charge.success', ['email' => $email]);
            return false;
        }

        $subscriptionData = $data['subscription'] ?? null;
        $subCode = $subscriptionData['subscription_code'] ?? null;

        // This could be initial payment or renewal
        DB::transaction(function () use ($user, $reference, $subCode, $data) {
            // Deactivate old subscriptions (only if no sub_code or same sub_code)
            if (!$subCode) {
                Subscription::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired', 'is_active' => false]);
            }

            $amount = ($data['amount'] ?? 0) / 100;
            $plan = $data['plan']['interval'] ?? 'monthly';

            $nextBillingRaw = $data['subscription']['next_payment_date'] ?? null;
            $nextBillingDate = $nextBillingRaw ? \Illuminate\Support\Carbon::parse($nextBillingRaw)->format('Y-m-d H:i:s') : null;
            Subscription::updateOrCreate(
                [
                    'reference' => $reference,
                    'subscription_code' => $subCode ?? $reference, // fallback
                ],
                [
                    'user_id'       => $user->id,
                    'plan'          => $plan,
                    'plan_code'     => $data['plan']['plan_code'] ?? 'custom',
                    'subscription_code' => $subCode,
                    'reference'     => $reference,
                    'amount'        => $amount,
                    'status'        => 'active',
                    'is_active'     => true,
                    'starts_at'     => now(),
                    'ends_at'       => $this->getEndsAtFromPaystack($data),
                    'next_billing_date' => $nextBillingDate,
                ]
            );
        });

        return true;
    }

    private function handleSubscriptionCreate(array $data): bool
    {
        $subCode = $data['subscription']['subscription_code'] ?? null;
        $email   = $data['customer']['email'] ?? null;

        if (!$subCode || !$email) {
            return false;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return false;
        }

        Subscription::updateOrCreate(
            ['subscription_code' => $subCode],
            [
                'user_id'       => $user->id,
                'plan_code'     => $data['plan']['plan_code'] ?? 'custom',
                'subscription_code' => $subCode,
                'status'        => 'active',
                'is_active'     => true,
                'starts_at'     => now(),
                'ends_at'       => $this->parsePaystackDate($data['subscription']['next_payment_date'] ?? null),
                'next_billing_date' => $data['subscription']['next_payment_date'] ?? null,
            ]
        );

        return true;
    }

    private function handleSubscriptionNotRenew(array $data): bool
    {
        $subCode = $data['subscription']['subscription_code'] ?? null;
        if (!$subCode) return false;

        Subscription::where('subscription_code', $subCode)
            ->update([
                'status' => 'non_renewing',
                'is_active' => true, // still active until end date
                'cancelled_at' => now(),
            ]);

        return true;
    }

    private function handleSubscriptionDisable(array $data): bool
    {
        $subCode = $data['subscription']['subscription_code'] ?? null;
        if (!$subCode) return false;

        Subscription::where('subscription_code', $subCode)
            ->update([
                'status' => 'cancelled',
                'is_active' => false,
                'cancelled_at' => now(),
            ]);

        return true;
    }

    private function handleFailureEvent(array $data): void
    {
        $email = $data['customer']['email'] ?? $data['subscription']['customer']['email'] ?? null;
        $reference = $data['reference'] ?? null;
        $amount = isset($data['amount']) ? $data['amount'] / 100 : 0;

        if ($email && $reference) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->notify(new PaymentFailed($reference, $amount));
            }
        }
    }

    // Helpers ────────────────────────────────────────────────

    private function getEndsAtFromPaystack(array $data)
    {
        // Prefer next_payment_date if available (most reliable)
        if ($next = $data['subscription']['next_payment_date'] ?? null) {
            return $this->parsePaystackDate($next);
        }

        // Fallback - only for one-time or when missing
        $interval = $data['plan']['interval'] ?? 'monthly';
        return match ($interval) {
            'monthly' => now()->addMonth(),
            'yearly'  => now()->addYear(),
            default   => now()->addMonth(),
        };
    }

    private function parsePaystackDate(?string $date): ?\Illuminate\Support\Carbon
    {
        return $date ? \Illuminate\Support\Carbon::parse($date)->utc() : null;
    }
}
