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
     * Enable/Create a Paystack subscription for the authenticated user
     * Uses saved card authorization to create new subscription (best practice)
     */
    public function enableSubscription(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->subscriptions()
            ->where('is_active', false)
            ->where('type', 'recurring')
            ->whereNotNull('authorization_code')
            ->whereNotNull('plan_code')
            ->latest()
            ->first();

        if (!$subscription) {
            return back()->withErrors(['subscription' => 'No inactive subscription found to enable.']);
        }

        $planCode = $subscription->plan_code;
        $authCode = $subscription->authorization_code;

        if (!$planCode || !$authCode) {
            return back()->withErrors(['subscription' => 'Plan code or authorization token missing.']);
        }

        // Get customer code from Paystack
        $customerCode = null;
        $result = $this->paymentService->fetchActiveSubscriptionByEmail($user->email);
        if ($result['success'] && !empty($result['data']['customer']['customer_code'])) {
            $customerCode = $result['data']['customer']['customer_code'];
        }

        if (!$customerCode) {
            return back()->withErrors(['subscription' => 'Customer information not found. Please contact support.']);
        }

        // Create new subscription using saved card (generates proper SUB_xxx code)
        $result = $this->paymentService->createSubscription($customerCode, $planCode, $authCode);

        if ($result['success']) {
            $newSubCode = $result['data']['subscription_code'] ?? null;
            $nextPayment = $result['data']['next_payment_date'] ?? null;

            $subscription->update([
                'subscription_code' => $newSubCode,
                'status'    => 'active',
                'is_active' => true,
                'cancelled_at' => null,
                'starts_at' => now(),
                'ends_at' => $nextPayment ? Carbon::parse($nextPayment) : now()->addMonth(),
                'next_billing_date' => $nextPayment ? Carbon::parse($nextPayment) : null,
            ]);

            return back()->with('success', $result['message'] ?? 'Subscription activated successfully.');
        }

        Log::warning('Subscription creation failed', [
            'user_id'   => $user->id,
            'plan_code'  => $planCode,
            'error'     => $result['message'],
        ]);

        return back()->withErrors(['subscription' => $result['message'] ?? 'Failed to activate subscription. Please try again or contact support.']);
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
            'student_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validated['student_id'] ?? null) {
            Log::info('Payment initialize with student_id', [
                'user_id' => Auth::id(),
                'student_id' => $validated['student_id'],
            ]);
        } else {
            Log::info('Payment initialize without student_id', ['user_id' => Auth::id()]);
        }

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $planKey     = $validated['plan'];
        $isRecurring = $validated['type'] === 'recurring';
        $amount      = config("pricing.plans.{$planKey}.amount");

        // Get plan code for recurring payments (prefer submitted value, fallback to config)
        $planCode = $isRecurring
            ? ($validated['plan_code'] ?? config("services.paystack.plans.{$planKey}"))
            : null;

        if ($isRecurring && !$planCode) {
            return back()->withErrors(['payment' => 'Subscription plan not configured in config/services.php']);
        }

        if ($isRecurring && $planCode) {
            $planDebug = $this->paymentService->fetchPlanDetails($planCode);
            Log::info('Paystack plan debug', [
                'plan_code' => $planCode,
                'success'   => $planDebug['success'],
                'data'      => $planDebug['data'],
                'message'   => $planDebug['message'],
            ]);
        }

        $reference = $this->paymentService->generateReference();

        $init = $this->paymentService->initializePaystack(
            email: $user->email,
            amount: $amount,
            reference: $reference,
            planCode: $planCode,
            metadata: [
                'user_id' => $user->id,
                'plan'    => $planKey,
                'type'    => $validated['type'],
            ]
        );

        if (!$init['success']) {
            if ($isRecurring && $planCode) {
                $planDebug = $this->paymentService->fetchPlanDetails($planCode);
                Log::error('Paystack plan debug (initialize failed)', [
                    'plan_code' => $planCode,
                    'success'   => $planDebug['success'],
                    'data'      => $planDebug['data'],
                    'message'   => $planDebug['message'],
                ]);
            }
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
            'selected_plan_code' => $planCode,
            'selected_student_id' => $validated['student_id'] ?? null,
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
        $planCodeFromSession = session('selected_plan_code');
        $studentIdFromSession = session('selected_student_id');

        Log::info('Payment callback session data', [
            'user_id' => $user->id,
            'student_id_from_session' => $studentIdFromSession,
            'plan' => $planFromSession,
            'type' => $typeFromSession,
            'reference' => $reference,
        ]);

        $plan = $planFromSession ?? ($data['plan']['plan_code'] ?? 'custom');
        $type = $typeFromSession ?? 'one_time';

        // Extract subscription code only for recurring payments
        $subscriptionCode = null;
        if ($type === 'recurring') {
            if (!empty($data['subscription']['subscription_code'])) {
                $subscriptionCode = $data['subscription']['subscription_code'];
            } else {
                // Fallback: Query Paystack API for subscription_code using customer code
                $customerCode = $data['customer']['customer_code'] ?? null;
                if ($customerCode) {
                    Log::info('Attempting to fetch subscription_code from Paystack API', [
                        'customer_code' => $customerCode,
                        'reference' => $reference,
                    ]);

                    $subResult = $this->paymentService->fetchSubscriptionByCustomer($customerCode);
                    if ($subResult['success'] && !empty($subResult['data']['subscription_code'])) {
                        $subscriptionCode = $subResult['data']['subscription_code'];
                        Log::info('Successfully fetched subscription_code from API', [
                            'subscription_code' => $subscriptionCode,
                        ]);
                    } else {
                        Log::warning('Could not fetch subscription_code from API', [
                            'customer_code' => $customerCode,
                            'result' => $subResult,
                        ]);
                    }
                }
            }
        }
        // For one-time payments or if no subscription_code found, use reference as identifier
        if (!$subscriptionCode) {
            $subscriptionCode = $reference;
        }

        // Extract authorization code upfront
        $authorizationCode = $data['authorization']['authorization_code'] ?? null;
        $customerCode = $data['customer']['customer_code'] ?? null;

        DB::transaction(function () use ($user, $reference, $plan, $type, $amount, $data, $subscriptionCode, $authorizationCode, $planCodeFromSession, $customerCode, $studentIdFromSession) {
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
            $endsAt = $this->calculateEndsAt($type, $plan, $interval, $nextPaymentDate);

            Log::info('Unified ends_at for subscription', [
                'plan' => $plan,
                'type' => $type,
                'interval' => $interval,
                'next_payment_date' => $nextPaymentDate,
                'ends_at' => $endsAt,
            ]);

            // Prepare subscription data (save both plan name and Paystack plan code)
            $planCode = $data['plan']['plan_code'] ?? $planCodeFromSession;
            $subscriptionData = [
                'user_id'           => $user->id,
                'student_id'        => $studentIdFromSession, // Link to specific student for guardians
                'plan'              => $plan, // human-readable (monthly/yearly)
                'plan_code'         => $planCode, // Paystack code (PLN_xxx)
                'subscription_code' => $subscriptionCode, // Include subscription_code here
                'reference'         => $reference,
                'type'              => $type,
                'status'            => 'active',
                'is_active'         => true,
                'amount'            => $amount,
                'starts_at'         => now(),
                'ends_at'           => $endsAt,
            ];

            // Store authorization code if present
            if ($authorizationCode) {
                $subscriptionData['authorization_code'] = $authorizationCode;
            }

            // Idempotent: update or create
            $subscription = Subscription::updateOrCreate(
                ['reference' => $reference],
                $subscriptionData
            );

            // For recurring subscriptions, dispatch job to fetch real subscription_code from Paystack
            // Paystack creates subscriptions a few seconds after payment, not immediately
            if ($type === 'recurring' && !str_starts_with($subscriptionCode, 'SUB_') && $customerCode) {
                dispatch(function () use ($subscription, $customerCode) {
                    // Wait for Paystack to create subscription (typically 5-10 seconds)
                    sleep(10);

                    $paymentService = app(\App\Services\PaymentService::class);
                    $result = $paymentService->fetchSubscriptionByCustomer($customerCode);

                    if ($result['success'] && !empty($result['data']['subscription_code'])) {
                        $oldCode = $subscription->subscription_code;
                        $subscription->update([
                            'subscription_code' => $result['data']['subscription_code'],
                        ]);

                        Log::info('Subscription code updated from Paystack API', [
                            'subscription_id' => $subscription->id,
                            'old_code' => $oldCode,
                            'new_code' => $result['data']['subscription_code'],
                        ]);
                    } else {
                        Log::warning('Could not update subscription code after payment', [
                            'subscription_id' => $subscription->id,
                            'customer_code' => $customerCode,
                            'result' => $result,
                        ]);
                    }
                })->afterResponse();
            }
        });

        Log::info('Payment callback processed successfully', [
            'user_id'   => $user->id,
            'reference' => $reference,
            'plan'      => $plan,
            'type'      => $type,
            'amount'    => $amount,
        ]);

        // Clean up session
        session()->forget(['paystack_reference', 'selected_plan', 'selected_type', 'selected_student_id']);

        return redirect('/dashboard')->with('success', 'Payment successful! Your subscription is now active.');
    }

    /**
     * Cancel the authenticated user's active subscription
     */
    public function cancelSubscription(Request $request)
    {
        $user = Auth::user();

        // Allow cancelling specific subscription by ID (for per-student management)
        $subscriptionId = $request->input('subscription_id');

        $query = $user->subscriptions()
            ->where('is_active', true)
            ->where('type', 'recurring') // Only recurring can be cancelled
            ->where(function($q) {
                $q->whereNotNull('subscription_code')
                  ->orWhereNotNull('plan_code');
            });

        if ($subscriptionId) {
            $query->where('id', $subscriptionId);
        }

        $subscription = $query->latest()->first();

        if (!$subscription) {
            return back()->withErrors(['subscription' => 'No active recurring subscription found. One-time payments cannot be cancelled and will expire naturally.']);
        }

        $subCode = $subscription->subscription_code ?? $subscription->plan_code;
        $authCode = $subscription->authorization_code ?? null;

        Log::info('Cancel subscription attempt', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'subscription_code' => $subscription->subscription_code,
            'plan_code' => $subscription->plan_code,
            'sub_code_used' => $subCode,
            'has_auth_code' => !empty($authCode),
            'subscription_type' => $subscription->type,
            'subscription_status' => $subscription->status,
            'is_active' => $subscription->is_active,
            'all_subscription_data' => $subscription->toArray(),
        ]);

        // For recurring/upgrade/cancel, always require and use authorization_code if available
        if (!$authCode && $subscription->type === 'recurring') {
            Log::warning('Cancel subscription - missing auth code', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
            ]);
            return back()->withErrors(['subscription' => 'Card authorization token is required for this action. Please re-subscribe or contact support.']);
        }

        $result = $this->paymentService->cancelSubscription($subCode, $authCode);

        if ($result['success']) {
            $subscription->update([
                'status'       => 'cancelled',
                'is_active'    => false,
                'cancelled_at' => now(),
            ]);

            Log::info('Subscription cancelled successfully', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'sub_code' => $subCode,
            ]);

            return back()->with('success', $result['message'] ?? 'Subscription cancelled successfully. It will remain active until the current period ends.');
        }

        Log::warning('Subscription cancellation failed', [
            'user_id'   => $user->id,
            'subscription_id' => $subscription->id,
            'sub_code'  => $subCode,
            'has_auth_code' => !empty($authCode),
            'error'     => $result['message'],
            'full_response' => $result,
        ]);

        return back()->withErrors(['subscription' => $result['message'] ?? 'Failed to cancel subscription. Please try again or contact support.']);
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
}
