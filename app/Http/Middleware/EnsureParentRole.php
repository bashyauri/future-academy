<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentRole
{
    /**
     * Middleware to ensure user has parent/guardian role.
     * Best practice: Use Spatie roles for consistent RBAC.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Check both account_type and Spatie role for redundancy
        if (!$user || (!$user->isParent() && !$user->hasRole('guardian'))) {
            abort(403, 'Only parents/guardians can access this resource.');
        }

        return $next($request);
    }
}
