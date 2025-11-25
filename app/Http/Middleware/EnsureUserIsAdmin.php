<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            // Filament Authenticate middleware handles redirect; this is a safeguard.
            return redirect()->route('filament.auth.login');
        }

        if (! auth()->user()->hasAnyRole(['super-admin', 'admin'])) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        return $next($request);
    }
}
