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

        if (!$user || !$user->isStudent()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
