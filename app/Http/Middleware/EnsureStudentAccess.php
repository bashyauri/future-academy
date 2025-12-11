<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentAccess
{
    protected array $studentRoles = ['student'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('filament.student.auth.login');
        }

        if ($user->hasRole($this->studentRoles)) {
            return $next($request);
        }

        abort(403, 'Student access required.');
    }
}
