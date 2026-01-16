<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffAccess
{
    /**
     * Permissions that allow entry to the staff content panel.
     * Staff are content creators who can manage questions, lessons, and quizzes.
     */
    protected array $staffPermissions = [
        // Question management
        'create questions',
        'manage questions',
        'upload questions',
        'import questions',

        // Lesson management
        'view lessons',
        'create lessons',
        'edit lessons',

        // Quiz management
        'view quizzes',
        'create quizzes',
        'edit quizzes',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('filament.auth.login');
        }

        $hasAccess = collect($this->staffPermissions)->contains(fn($perm) => $user->hasPermissionTo($perm));

        if (! $hasAccess) {
            // Redirect to appropriate dashboard instead of showing 403
            return redirect()->route('redirect.dashboard')
                ->with('error', 'You do not have permission to access the staff panel.');
        }

        return $next($request);
    }
}
