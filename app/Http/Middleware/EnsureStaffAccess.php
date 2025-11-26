<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffAccess
{
    /**
     * Permissions that allow entry to the staff content panel.
     */
    protected array $staffPermissions = [
        'create questions',
        'manage questions',
        'upload questions',
        'import questions',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('filament.auth.login');
        }

        $hasAccess = collect($this->staffPermissions)->contains(fn($perm) => $user->hasPermissionTo($perm));

        if (! $hasAccess) {
            abort(403, 'Staff access required.');
        }

        return $next($request);
    }
}
