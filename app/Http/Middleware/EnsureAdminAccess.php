<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    /**
     * Permissions that qualify a user as an admin-level user for the admin panel.
     */
    // protected array $adminPermissions = [
    //     'manage users',
    //     'manage academics',
    //     'approve questions',
    //     'delete questions',
    // ];
    protected array $adminPermissions = [
        'manage users',
        'manage academics',
        'approve questions',
        'delete questions',
        'create questions',
        'manage questions',
        'upload questions',
        'import questions',
        // Quiz access grants panel entry for authorized staff
        'view quizzes',
        'create quizzes',
        'edit quizzes',
        'delete quizzes',
        'publish quizzes',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Grant access if user is super-admin or admin
        if ($user->hasRole(['super-admin', 'admin'])) {
            return $next($request);
        }

        // Grant if user has ANY of the qualifying admin perms
        $hasAccess = collect($this->adminPermissions)->contains(fn($perm) => $user->hasPermissionTo($perm));

        if (! $hasAccess) {
            abort(403, 'Admin access required.');
        }

        return $next($request);
    }
}
