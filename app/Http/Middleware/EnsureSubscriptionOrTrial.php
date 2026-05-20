<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class EnsureSubscriptionOrTrial
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return $next($request);
        }

        $activeContext = $this->resolveLearningContext($user);

        // Guardians access protected learning routes based on linked-student entitlement.
        if ($activeContext === 'guardian') {
            $linkedStudents = $user->children()->wherePivot('is_active', true)->pluck('users.id');

            if ($linkedStudents->isEmpty()) {
                // No students linked yet, allow access to dashboard to link students
                return $next($request);
            }

            if ($this->routeAllowsGuardianSetupAccess($request)) {
                return $next($request);
            }

            $activeSubscriptions = Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('is_active', true);

            $requestedStudentId = $request->integer('student');

            if ($requestedStudentId > 0) {
                if (! $linkedStudents->contains($requestedStudentId)) {
                    return redirect()->route('parent.dashboard')->with('error', __('You can only track students linked to your account.'));
                }

                if ($this->studentHasEntitlement($requestedStudentId, $user->id)) {
                    return $next($request);
                }

                $hasSubscriptionForRequestedStudent = (clone $activeSubscriptions)
                    ->where('student_id', $requestedStudentId)
                    ->exists();

                if ($hasSubscriptionForRequestedStudent) {
                    return $next($request);
                }

                return redirect()->route('payment.pricing')
                    ->with('trial_upgrade_prompt', __('This linked student does not currently have trial or paid access. Upgrade to unlock premium features.'))
                    ->with('blocked_feature', $this->blockedFeatureLabel($request));
            }

            if ($linkedStudents->contains(fn ($studentId) => $this->studentHasEntitlement((int) $studentId, $user->id))) {
                return $next($request);
            }

            $hasSubscriptionForAnyLinkedStudent = (clone $activeSubscriptions)
                ->whereIn('student_id', $linkedStudents)
                ->exists();

            if ($hasSubscriptionForAnyLinkedStudent) {
                return $next($request);
            }

            // Backward compatibility for old subscriptions without student mapping.
            if ($linkedStudents->count() === 1 && (clone $activeSubscriptions)->whereNull('student_id')->exists()) {
                return $next($request);
            }

            return redirect()->route('payment.pricing')
                ->with('trial_upgrade_prompt', __('None of your linked students currently has trial or paid access. Upgrade to unlock premium features.'))
                ->with('blocked_feature', $this->blockedFeatureLabel($request));
        }

        $isOnTrial = method_exists($user, 'onTrial')
            ? $user->onTrial()
            : (isset($user->trial_ends_at) && $user->trial_ends_at && now()->lt($user->trial_ends_at));

        // Trial users can only access routes that expose free content or account settings.
        if ($activeContext === 'student' && $isOnTrial) {
            if ($this->routeAllowsTrialAccess($request)) {
                return $next($request);
            }

            return redirect()->route('payment.pricing')
                ->with('trial_upgrade_prompt', __('Your 48-hour trial includes only free lessons and quizzes. Upgrade to unlock premium features.'))
                ->with('blocked_feature', $this->blockedFeatureLabel($request));
        }

        if ($user->hasActiveSubscription()) {
            return $next($request);
        }

        // Check if a guardian has paid for this student (simpler query)
        if ($activeContext === 'student') {
            $guardianPaidForStudent = Subscription::where('student_id', $user->id)
                ->where('status', 'active')
                ->where('is_active', true)
                ->exists();

            if ($guardianPaidForStudent) {
                return $next($request);
            }
        }

        return redirect()->route('payment.pricing')->with('error', __('Please subscribe to access all features.'));
    }

    private function routeAllowsTrialAccess(Request $request): bool
    {
        return $request->routeIs([
            'profile.edit',
            'user-password.edit',
            'appearance.edit',
            'two-factor.show',
            'quizzes.index',
            'quizzes.all',
            'quizzes.subject',
            'quiz.take',
            'lessons.subjects',
            'lessons.list',
            'lessons.view',
        ]);
    }

    private function routeAllowsGuardianSetupAccess(Request $request): bool
    {
        return $request->routeIs([
            'lessons.subjects',
            'lessons.list',
        ]);
    }

    private function blockedFeatureLabel(Request $request): string
    {
        $routeName = $request->route()?->getName();

        return match (true) {
            str_starts_with((string) $routeName, 'mock.') => __('Mock Exams'),
            str_starts_with((string) $routeName, 'practice.') => __('Practice Quizzes'),
            $routeName === 'analytics' => __('Analytics Dashboard'),
            default => __('Premium Features'),
        };
    }

    private function studentHasEntitlement(int $studentId, int $guardianId): bool
    {
        $studentOnTrial = User::query()
            ->whereKey($studentId)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->exists();

        if ($studentOnTrial) {
            return true;
        }

        $studentHasActiveSubscription = Subscription::query()
            ->where('user_id', $studentId)
            ->where('status', 'active')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();

        if ($studentHasActiveSubscription) {
            return true;
        }

        return Subscription::query()
            ->where('user_id', $guardianId)
            ->where('status', 'active')
            ->where('is_active', true)
            ->where(function ($query) use ($studentId) {
                $query->where('student_id', $studentId)
                    ->orWhereNull('student_id');
            })
            ->exists();
    }

    private function resolveLearningContext($user): string
    {
        if (method_exists($user, 'resolveActiveRoleContext')) {
            $context = $user->resolveActiveRoleContext();

            return $context ?? 'student';
        }

        $requestedContext = session('active_role_context');
        $canGuardianContext = (method_exists($user, 'hasRole') && $user->hasRole('guardian'))
            || (method_exists($user, 'isParent') && $user->isParent());
        $canStudentContext = (method_exists($user, 'hasRole') && $user->hasRole('student'))
            || (method_exists($user, 'isStudent') && $user->isStudent());

        if ($requestedContext === 'guardian' && $canGuardianContext) {
            return 'guardian';
        }

        if ($requestedContext === 'student' && $canStudentContext) {
            return 'student';
        }

        if ($canGuardianContext && ! $canStudentContext) {
            return 'guardian';
        }

        return 'student';
    }
}
