<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentAccess
{
    protected array $parentRoles = ['guardian', 'parent'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('filament.parent.auth.login');
        }

        if ($user->hasRole($this->parentRoles)) {
            return $next($request);
        }

        abort(403, 'Parent access required.');
    }
}
