<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSubscriptionOrTrial
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Guardians MUST have active subscription (no trial allowed)
        if ($user->isParent()) {
            if ($user->hasActiveSubscription()) {
                return $next($request);
            }
            return redirect()->route('pricing')->with('error', __('Parents must purchase a subscription to manage students.'));
        }

        // Students/Teachers/Uploaders can use trial or active subscription
        if ($user->onTrial() || $user->hasActiveSubscription()) {
            return $next($request);
        }

        return redirect()->route('payment.pricing')->with('error', __('Please subscribe or start a trial to access all features.'));
    }
}
