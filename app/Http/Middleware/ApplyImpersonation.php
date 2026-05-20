<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApplyImpersonation
{
    /**
     * Apply admin impersonation from session for support troubleshooting.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            return $next($request);
        }

        $impersonatorId = $request->session()->get('impersonator_id');
        $impersonatedUserId = $request->session()->get('impersonated_user_id');

        if (! $impersonatorId || ! $impersonatedUserId) {
            return $next($request);
        }

        $impersonator = User::find($impersonatorId);
        $impersonatedUser = User::find($impersonatedUserId);

        if (
            ! $impersonator
            || ! $impersonatedUser
            || $impersonator->id === $impersonatedUser->id
            || ! $impersonator->hasAnyRole(['admin', 'super-admin'])
        ) {
            $request->session()->forget([
                'impersonator_id',
                'impersonated_user_id',
                'impersonated_user_email',
                'impersonated_user_name',
            ]);

            return $next($request);
        }

        Auth::onceUsingId($impersonatedUser->id);

        return $next($request);
    }
}
