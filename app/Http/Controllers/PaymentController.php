<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentService;
use App\Models\Subscription;
use Carbon\Carbon;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Show the pricing page
     */
    public function showPricing()
    {
        return view('payment.pricing');
    }

    /**
     * Initialize a Paystack payment (one-time or subscription)
     */
    public function initialize(Request $request)
    {

        $validated = $request->validate([
            'plan' => 'required|in:monthly,yearly',
            'type' => 'required|in:one_time,recurring',
            'plan_code' => 'nullable|string',
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }


        $planKey     = $validated['plan'];
        $isRecurring = $validated['type'] === 'recurring';
        $amount      = $planKey === 'monthly' ? 2000 : 12000;

        // Get plan code for recurring payments (prefer submitted value, fallback to config)
        $planCode = $isRecurring
            ? ($validated['plan_code'] ?? config("services.paystack.plans.{$planKey}"))
            : null;

        if ($isRecurring && !$planCode) {
            return back()->withErrors(['payment' => 'Subscription plan not configured in config/services.php']);
        }

        $reference = $this->paymentService->generateReference();

        $init = $this->paymentService->initializePaystack(
            email: $user->email,
            amount: $isRecurring ? null : $amount, // amount ignored by Paystack for plans
            reference: $reference,
            planCode: $planCode,
            metadata: [
                'user_id' => $user->id,
                'plan'    => $planKey,
                'type'    => $validated['type'],
            ]
        );

        if (!$init['success']) {
            Log::warning('Payment initialization failed', [
                'user_id'   => $user->id,
                'reference' => $reference,
                'error'     => $init['message'],
                'response'  => $init['full_response'] ?? null,
            ]);

            return back()->withErrors(['payment' => $init['message'] ?? 'Unable to start payment process.']);
        }

        // Store minimal session data for callback
        session([
            'paystack_reference' => $reference,
            'selected_plan'      => $planKey,
            'selected_type'      => $validated['type'],
        ]);

        return redirect($init['authorization_url']);
    }

    /**
     * Handle Paystack callback after payment
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference') ?? session('paystack_reference');

        if (!$reference) {
            Log::warning('Callback received without reference');
            return redirect('/payment/pricing')->withErrors(['payment' => 'Invalid or missing payment reference.']);
        }

        $verify = $this->paymentService->verifyPaystack($reference);

        if (!$verify['success'] || $verify['data']['status'] !== 'success') {
            Log::warning('Payment verification failed', [
                'reference' => $reference,
                'status'    => $verify['data']['status'] ?? 'unknown',
                'message'   => $verify['message'],
            ]);

            return redirect('/payment/pricing')->withErrors(['payment' => $verify['message'] ?? 'Payment was not successful.']);
        }

        $user = Auth::user();
        if (!$user) {
            return redirect('/payment/pricing')->withErrors(['payment' => 'Session expired. Please log in again.']);
        }

        $data   = $verify['data'];
        $amount = $data['amount'] / 100;

        $planFromSession = session('selected_plan');
        $typeFromSession = session('selected_type');

        $plan = $planFromSession ?? ($data['plan']['plan_code'] ?? 'custom');
        $type = $typeFromSession ?? 'one_time';

        // For recurring payments, trust Paystack's subscription data
        if (!empty($data['subscription']['subscription_code'])) {
            $subscriptionData['subscription_code'] = $data['subscription']['subscription_code'];
        } elseif (!empty($data['reference'])) {
            $subscriptionData['subscription_code'] = $data['reference'];
        } elseif (!empty($data['plan']['plan_code'])) {
            $subscriptionData['subscription_code'] = $data['plan']['plan_code'];
        }

        DB::transaction(function () use ($user, $reference, $plan, $type, $amount, $data) {
            // Clear any active trial
            if ($user->trial_ends_at) {
                $user->trial_ends_at = null;
                $user->save();
            }

            // Deactivate all previous active subscriptions
            Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update([
                    'status'    => 'inactive',
                    'is_active' => false,
                ]);


            // Unify ends_at logic for recurring plans
            $nextPaymentDate = $data['subscription']['next_payment_date'] ?? null;
            $interval = $data['plan']['interval'] ?? null;
            $endsAt = null;
            if ($type === 'recurring') {
                if ($plan === 'monthly' || $interval === 'monthly') {
                    // Use Paystack's date only if it's about a month ahead
                    if ($nextPaymentDate && Carbon::parse($nextPaymentDate)->diffInDays(now()) >= 28 && Carbon::parse($nextPaymentDate)->diffInDays(now()) <= 32) {
                        $endsAt = Carbon::parse($nextPaymentDate);
                    } else {
                        $endsAt = now()->addMonth();
                    }
                } elseif ($plan === 'yearly' || $interval === 'yearly') {
                    // Use Paystack's date only if it's about a year ahead
                    if ($nextPaymentDate && Carbon::parse($nextPaymentDate)->diffInDays(now()) >= 360 && Carbon::parse($nextPaymentDate)->diffInDays(now()) <= 370) {
                        $endsAt = Carbon::parse($nextPaymentDate);
                    } else {
                        $endsAt = now()->addYear();
                    }
                }
            } else {
                // One-time payments
                $endsAt = ($plan === 'monthly') ? now()->addMonth() : now()->addYear();
            }
            \Log::info('Unified ends_at for subscription', [
                'plan' => $plan,
                'type' => $type,
                'interval' => $interval,
                'next_payment_date' => $nextPaymentDate,
                'ends_at' => $endsAt,
            ]);

            // Prepare subscription data (save both plan name and Paystack plan code)
            $planCode = $data['plan']['plan_code'] ?? null;
            $subscriptionData = [
                'user_id'   => $user->id,
                'plan'      => $plan, // human-readable (monthly/yearly)
                'plan_code' => $planCode, // Paystack code (PLN_xxx)
                'reference' => $reference,
                'type'      => $type,
                'status'    => 'active',
                'is_active' => true,
                'amount'    => $amount,
                'starts_at' => now(),
                'ends_at'   => $endsAt,
            ];

            // Store Paystack subscription code if present
            if (!empty($data['subscription']['subscription_code'])) {
                $subscriptionData['subscription_code'] = $data['subscription']['subscription_code'];
            } elseif (!empty($data['reference'])) {
                $subscriptionData['subscription_code'] = $data['reference'];
            } elseif (!empty($data['plan']['plan_code'])) {
                $subscriptionData['subscription_code'] = $data['plan']['plan_code'];
            }

            // Store Paystack authorization_code if present
            if (!empty($data['authorization']['authorization_code'])) {
                $subscriptionData['authorization_code'] = $data['authorization']['authorization_code'];
            }

            // Idempotent: update or create
            Subscription::updateOrCreate(
                ['reference' => $reference],
                $subscriptionData
            );
        });

        Log::info('Payment callback processed successfully', [
            'user_id'   => $user->id,
            'reference' => $reference,
            'plan'      => $plan,
            'type'      => $type,
            'amount'    => $amount,
        ]);

        // Clean up session
        session()->forget(['paystack_reference', 'selected_plan', 'selected_type']);

        return redirect('/dashboard')->with('success', 'Payment successful! Your subscription is now active.');
    }

    /**
     * Cancel the authenticated user's active subscription
     */
    public function cancelSubscription(Request $request)
    {
        $user = Auth::user();

        $subscription = $user->subscriptions()
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNotNull('subscription_code')
                      ->orWhereNotNull('plan_code');
            })
            ->latest()
            ->first();

        if (!$subscription) {
            return back()->withErrors(['subscription' => 'No active subscription found. Please check that your subscription is active and has a valid plan or subscription code.']);
        }

        $subCode = $subscription->subscription_code ?? $subscription->plan_code;
        $authCode = $subscription->authorization_code ?? null;
        $result = $this->paymentService->cancelSubscription($subCode, $authCode);

        if ($result['success']) {
            $subscription->update([
                'status'       => 'cancelled',
                'is_active'    => false,
                'cancelled_at' => now(),
            ]);

            return back()->with('success', $result['message'] ?? 'Subscription cancelled successfully. It will remain active until the current period ends.');
        }

        Log::warning('Subscription cancellation failed', [
            'user_id'   => $user->id,
            'sub_code'  => $subscription->subscription_code,
            'error'     => $result['message'],
        ]);

        return back()->withErrors(['subscription' => $result['message'] ?? 'Failed to cancel subscription. Please try again or contact support.']);
    }
}
