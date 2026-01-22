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
            $plan = session('selected_plan');
            $type = session('selected_type');
            $amount = $verify['data']['amount'] / 100;
            $endsAt = $plan === 'monthly' ? now()->addMonth() : now()->addYear();
            $user->trial_ends_at = null;
            $user->save();
            Subscription::create([
                'user_id' => $user->id,
                'plan' => $plan,
                'type' => $type,
                'status' => 'active',
                'reference' => $reference,
                'amount' => $amount,
                'starts_at' => now(),
                'ends_at' => $endsAt,
            ]);
            return redirect('/dashboard')->with('success', 'Payment successful!');
        }
        return redirect('/payment/pricing')->withErrors(['payment' => $verify['message'] ?? 'Payment verification failed.']);
    }
}
