<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentApiController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get pricing plans
     */
    public function pricing(Request $request): JsonResponse
    {
        $plans = config('pricing.plans');
        
        return response()->json([
            'message' => 'Pricing plans retrieved',
            'data' => [
                'plans' => $plans,
            ]
        ]);
    }

    /**
     * Initialize a Paystack payment
     */
    public function initialize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan' => 'required|in:monthly,yearly',
            'type' => 'required|in:one_time,recurring',
            'plan_code' => 'nullable|string',
            'student_id' => 'nullable|integer|exists:users,id',
        ]);

        $user = $request->user();
        $selectedStudentId = $validated['student_id'] ?? null;

        if ($user->isParent()) {
            if (! $selectedStudentId) {
                throw ValidationException::withMessages([
                    'student_id' => __('Guardians can only pay for a linked student. Select a student before continuing.'),
                ]);
            }

            $isLinkedStudent = $user->children()
                ->where('users.id', $selectedStudentId)
                ->exists();

            if (! $isLinkedStudent) {
                throw ValidationException::withMessages([
                    'student_id' => __('You can only pay for students linked to your account.'),
                ]);
            }
        }

        $planKey = $validated['plan'];
        $isRecurring = $validated['type'] === 'recurring';
        $amount = config("pricing.plans.{$planKey}.amount");

        $planCode = $isRecurring
            ? ($validated['plan_code'] ?? config("services.paystack.plans.{$planKey}"))
            : null;

        if ($isRecurring && ! $planCode) {
            return response()->json([
                'message' => 'Subscription plan not configured.'
            ], 400);
        }

        $reference = $this->paymentService->generateReference();

        $metadata = [
            'user_id' => $user->id,
            'plan' => $planKey,
            'type' => $validated['type'],
        ];

        // Include student_id for API verification later
        if ($selectedStudentId) {
            $metadata['student_id'] = $selectedStudentId;
        }

        // We use the application's base URL / a custom callback that can be intercepted
        // Typically mobile will intercept a deep link or the WebBrowser closes when redirected.
        $init = $this->paymentService->initializePaystack(
            email: $user->email,
            amount: $amount,
            reference: $reference,
            planCode: $planCode,
            metadata: $metadata,
            callbackUrl: config('app.url') . '/api/v1/payment/callback-redirect'
        );

        if (! $init['success']) {
            Log::warning('API Payment initialization failed', [
                'user_id' => $user->id,
                'reference' => $reference,
                'error' => $init['message'],
            ]);

            return response()->json([
                'message' => $init['message'] ?? 'Unable to start payment process.'
            ], 400);
        }

        return response()->json([
            'message' => 'Payment initialized',
            'data' => [
                'authorization_url' => $init['authorization_url'],
                'reference' => $reference,
            ]
        ]);
    }

    /**
     * Verify Paystack callback
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string',
        ]);
        
        $reference = $validated['reference'];
        $user = $request->user();

        // Check if subscription already exists (handled by webhook)
        $existingSub = Subscription::where('paystack_reference', $reference)->first();
        if ($existingSub) {
            return response()->json([
                'message' => 'Payment successful',
                'data' => $existingSub
            ]);
        }

        $verify = $this->paymentService->verifyPaystack($reference);

        if (! $verify['success'] || $verify['data']['status'] !== 'success') {
            Log::warning('API Payment verification failed', [
                'reference' => $reference,
                'message' => $verify['message'],
            ]);

            return response()->json([
                'message' => $verify['message'] ?? 'Payment was not successful.'
            ], 400);
        }

        $data = $verify['data'];
        
        // Ensure metadata exists
        $metadata = $data['metadata'] ?? [];
        $plan = $metadata['plan'] ?? ($data['plan']['plan_code'] ?? 'custom');
        $type = $metadata['type'] ?? 'one_time';
        $studentId = $metadata['student_id'] ?? null;

        // Extract subscription code
        $subscriptionCode = null;
        if ($type === 'recurring') {
            if (! empty($data['subscription']['subscription_code'])) {
                $subscriptionCode = $data['subscription']['subscription_code'];
            } else {
                $customerCode = $data['customer']['customer_code'] ?? null;
                if ($customerCode) {
                    $subResult = $this->paymentService->fetchSubscriptionByCustomer($customerCode);
                    if ($subResult['success'] && ! empty($subResult['data']['subscription_code'])) {
                        $subscriptionCode = $subResult['data']['subscription_code'];
                    }
                }
            }
        }

        $authCode = $data['authorization']['authorization_code'] ?? null;
        $nextPaymentDate = null;
        if ($type === 'recurring' && $subscriptionCode) {
            $subDetails = $this->paymentService->fetchSubscription($subscriptionCode);
            if ($subDetails['success'] && ! empty($subDetails['data']['next_payment_date'])) {
                $nextPaymentDate = Carbon::parse($subDetails['data']['next_payment_date']);
            }
        }

        $endsAt = $nextPaymentDate ? clone $nextPaymentDate : now()->addMonth();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'student_id' => $studentId,
            'type' => $type,
            'status' => 'active',
            'is_active' => true,
            'plan' => $plan,
            'plan_code' => $data['plan']['plan_code'] ?? null,
            'paystack_id' => $data['id'] ?? null,
            'paystack_reference' => $reference,
            'subscription_code' => $subscriptionCode,
            'authorization_code' => $authCode,
            'amount' => $data['amount'] / 100,
            'currency' => $data['currency'],
            'starts_at' => now(),
            'ends_at' => $endsAt,
            'next_billing_date' => $nextPaymentDate,
        ]);

        return response()->json([
            'message' => 'Payment successful',
            'data' => $subscription
        ]);
    }
    
    /**
     * Simple HTML redirect for WebBrowser
     */
    public function callbackRedirect(Request $request)
    {
        $reference = $request->query('reference');
        // This HTML simply tells the app to intercept the URL and close the web browser.
        return response(
            "<html><body><h2>Payment Processing</h2><script>window.location.replace('futureacademy://payment/callback?reference=' + " . json_encode($reference) . ");</script></body></html>"
        );
    }
}
