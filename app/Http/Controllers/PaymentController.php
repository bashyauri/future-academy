<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentService;
use App\Models\Subscription;
use App\Models\User;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function showPricing()
    {
        return view('payment.pricing');
    }

public function initialize(Request $request)
{
    $request->validate([
        'plan' => 'required|in:monthly,yearly',
        'type' => 'required|in:one_time,recurring',
    ]);

    $user = Auth::user();
    if (!$user) {
        return redirect()->route('login');
    }

    $planKey = $request->plan; // 'monthly' or 'yearly'
    $isRecurring = $request->type === 'recurring';

    // Get plan codes from config/services.php (which uses .env for production)
    $planCodes = [
        'monthly' => config('services.paystack.plans.monthly'), // e.g. 'PLN_xxxxxxxx'
        'yearly'  => config('services.paystack.plans.yearly'),  // e.g. 'PLN_yyyyyyyy'
    ];

    $planCode = $isRecurring ? ($planCodes[$planKey] ?? null) : null;
    $amount   = $planKey === 'monthly' ? 2000 : 12000; // NGN

    if ($isRecurring && !$planCode) {

        return back()->withErrors(['payment' => 'Subscription plan not configured.']);
    }

    $reference = $this->paymentService->generateReference();

    $init = $this->paymentService->initializePaystack(
        $user->email,
        $amount,
        $reference,
        $planCode, // Only for recurring
        [
            'user_id' => $user->id,
            'plan'    => $planKey,
            'type'    => $request->type
        ]
    );

    if ($init['success']) {
        session([
            'paystack_reference' => $reference,
            'selected_plan'      => $planKey,
            'selected_type'      => $request->type,
        ]);

        return redirect($init['authorization_url']);
    }

    return back()->withErrors(['payment' => $init['message'] ?? 'Payment initialization failed.']);
}

    public function callback(Request $request)
    {
        $reference = $request->query('reference') ?? session('paystack_reference');

        if (!$reference) {
            Log::warning('Callback: Missing reference');
            return redirect('/payment/pricing')->withErrors(['payment' => 'Invalid payment reference.']);
        }

        $verify = $this->paymentService->verifyPaystack($reference);

        if (!$verify['success'] || $verify['data']['status'] !== 'success') {
            Log::warning('Payment verification failed', ['reference' => $reference, 'verify' => $verify]);
            return redirect('/payment/pricing')->withErrors(['payment' => $verify['message'] ?? 'Payment failed or not successful.']);
        }

        $user = Auth::user();
        if (!$user) {
            return redirect('/payment/pricing')->withErrors(['payment' => 'Session expired. Please log in again.']);
        }

        $data       = $verify['data'];
        $amount     = $data['amount'] / 100;
        $planFromSession = session('selected_plan');
        $typeFromSession = session('selected_type');

        // Prefer verify data if available, fallback to session
        $plan = $planFromSession ?? ($data['plan']['plan_code'] ?? 'custom');
        $type = $typeFromSession ?? 'one_time'; // safer default

        // For recurring: try to get from subscription object if present
        if (isset($data['subscription']['subscription_code'])) {
            $type = 'recurring';
            // Could fetch next_payment_date via API if needed, but webhook handles best
        }

        DB::transaction(function () use ($user, $reference, $plan, $type, $amount) {
            // Clear trial if active
            if ($user->trial_ends_at) {
                $user->trial_ends_at = null;
                $user->save();
            }

            // Deactivate previous active subs
            Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'inactive', 'is_active' => false]);

            // Create/update subscription
            $endsAt = $type === 'monthly' ? now()->addMonth() : now()->addYear();

            Subscription::updateOrCreate(
                ['reference' => $reference],
                [
                    'user_id'   => $user->id,
                    'plan'      => $plan,
                    'type'      => $type,
                    'status'    => 'active',
                    'is_active' => true,
                    'amount'    => $amount,
                    'starts_at' => now(),
                    'ends_at'   => $endsAt,
                ]
            );
        });

        Log::info('Callback: Subscription activated', [
            'reference' => $reference,
            'user_id'   => $user->id,
            'plan'      => $plan,
            'type'      => $type,
            'amount'    => $amount,
        ]);

        // Clean session
        session()->forget(['paystack_reference', 'selected_plan', 'selected_type']);

        return redirect('/dashboard')->with('success', 'Payment successful! Your subscription is now active.');
    }
}
