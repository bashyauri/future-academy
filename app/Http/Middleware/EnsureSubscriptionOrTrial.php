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

        // Guardians MUST have active subscription for each linked student (no trial allowed)
        if ($user->isParent()) {
            $linkedStudents = $user->children()->wherePivot('is_active', true)->pluck('users.id');

            if ($linkedStudents->isEmpty()) {
                // No students linked yet, allow access to dashboard to link students
                return $next($request);
            }

            // Check if guardian has active subscription for ALL linked students
            $studentsWithSubscription = \App\Models\Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('is_active', true)
                ->whereIn('student_id', $linkedStudents)
                ->pluck('student_id');

            $studentsWithoutSubscription = $linkedStudents->diff($studentsWithSubscription);

            if ($studentsWithoutSubscription->isNotEmpty()) {
                return redirect()->route('parent.dashboard')->with('error', __('Please purchase a subscription for all linked students to continue.'));
            }

            return $next($request);
        }

        // Students/Teachers/Uploaders can use trial or active subscription
        // For students: Also check if any guardian has paid for them
        if ($user->onTrial() || $user->hasActiveSubscription()) {
            return $next($request);
        }

        // Check if a guardian has paid for this student (simpler query)
        if ($user->isStudent()) {
            $guardianPaidForStudent = \App\Models\Subscription::where('student_id', $user->id)
                ->where('status', 'active')
                ->where('is_active', true)
                ->exists();

            if ($guardianPaidForStudent) {
                return $next($request);
            }
        }

        return redirect()->route('payment.pricing')->with('error', __('Please subscribe or start a trial to access all features.'));
    }
}
