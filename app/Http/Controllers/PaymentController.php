<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $amount = $request->plan === 'monthly' ? 2000 : 12000;
        $reference = $this->paymentService->generateReference();
        $init = $this->paymentService->initializePaystack($user->email, $amount, $reference);
        if ($init['success']) {
            session(['paystack_reference' => $reference, 'selected_plan' => $request->plan, 'selected_type' => $request->type]);
            return redirect($init['authorization_url']);
        }
        return back()->withErrors(['payment' => $init['message'] ?? 'Unable to initialize payment.']);
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference', session('paystack_reference'));
        $verify = $this->paymentService->verifyPaystack($reference);
        if ($verify['success']) {
            $user = Auth::user();
            $plan = session('selected_plan') ?? ($verify['data']['plan'] ?? 'custom');
            $type = session('selected_type') ?? ($verify['data']['plan_type'] ?? 'one_time');
            $amount = $verify['data']['amount'] / 100;
            $endsAt = $plan === 'monthly' ? now()->addMonth() : now()->addYear();

            // Validate required fields
            $errors = [];
            if (!$reference) $errors[] = 'Missing payment reference.';
            if (!$plan) $errors[] = 'Missing subscription plan.';
            if (!$type) $errors[] = 'Missing subscription type.';
            if (!$amount || $amount <= 0) $errors[] = 'Invalid payment amount.';
            if (!$user) $errors[] = 'User not authenticated.';

            if (count($errors)) {
                \Log::error('Payment callback validation failed', [
                    'errors' => $errors,
                    'reference' => $reference,
                    'plan' => $plan,
                    'type' => $type,
                    'amount' => $amount,
                    'user_id' => $user ? $user->id : null,
                ]);
                return redirect('/payment/pricing')->withErrors(['payment' => 'Payment failed: ' . implode(' ', $errors)]);
            }

            try {
                $user->trial_ends_at = null;
                $user->save();

                // Deactivate previous subscriptions
                Subscription::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive', 'is_active' => false]);

                // Create or update the new subscription (idempotent)
                Subscription::updateOrCreate(
                    ['reference' => $reference],
                    [
                        'user_id' => $user->id,
                        'plan' => $plan,
                        'type' => $type,
                        'status' => 'active',
                        'is_active' => true,
                        'amount' => $amount,
                        'starts_at' => now(),
                        'ends_at' => $endsAt,
                    ]
                );
                \Log::info('Payment callback: subscription updated/created', [
                    'reference' => $reference,
                    'user_id' => $user->id,
                    'plan' => $plan,
                    'type' => $type,
                    'amount' => $amount,
                ]);
                return redirect('/dashboard')->with('success', 'Payment successful!');
            } catch (\Exception $e) {
                \Log::error('Payment callback exception', [
                    'exception' => $e->getMessage(),
                    'reference' => $reference,
                    'plan' => $plan,
                    'type' => $type,
                    'amount' => $amount,
                    'user_id' => $user ? $user->id : null,
                ]);
                return redirect('/payment/pricing')->withErrors(['payment' => 'Payment processing error. Please contact support.']);
            }
        }
        \Log::warning('Payment verification failed', [
            'reference' => $reference,
            'verify' => $verify,
        ]);
        return redirect('/payment/pricing')->withErrors(['payment' => $verify['message'] ?? 'Payment verification failed.']);
    }
}
