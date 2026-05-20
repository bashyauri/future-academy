<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentRole
{
    /**
     * Middleware to ensure user is a student.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $canUseStudentContext = $user && (method_exists($user, 'canUseStudentContext')
            ? $user->canUseStudentContext()
            : ((method_exists($user, 'hasRole') && $user->hasRole('student')) || (method_exists($user, 'isStudent') && $user->isStudent())));

        if (! $canUseStudentContext) {
            return redirect()->route('dashboard');
        }

        $canUseGuardianContext = method_exists($user, 'canUseGuardianContext')
            ? $user->canUseGuardianContext()
            : ((method_exists($user, 'hasRole') && $user->hasRole('guardian')) || (method_exists($user, 'isParent') && $user->isParent()));

        if ($canUseGuardianContext && session('active_role_context') === 'guardian') {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
