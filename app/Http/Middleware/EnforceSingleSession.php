<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSingleSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $sessionId = (string) $request->session()->getId();
        $storedSessionId = (string) ($user->current_session_id ?? '');
        $syncPending = (bool) $request->session()->get('single_session_sync_pending', false);

        if ($storedSessionId === '') {
            $user->forceFill([
                'current_session_id' => $sessionId,
            ])->save();

            $request->session()->forget('single_session_sync_pending');

            return $next($request);
        }

        if ($syncPending && ! hash_equals($storedSessionId, $sessionId)) {
            $user->forceFill([
                'current_session_id' => $sessionId,
            ])->save();

            $request->session()->forget('single_session_sync_pending');

            return $next($request);
        }

        $request->session()->forget('single_session_sync_pending');

        if (! hash_equals($storedSessionId, $sessionId)) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login', ['forced_logout' => 1])
                ->with('status', __('Your account was signed in on another device.'));
        }

        return $next($request);
    }
}
