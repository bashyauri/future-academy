<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherAccess
{
    protected array $teacherRoles = ['teacher', 'uploader', 'admin', 'super-admin'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('filament.teacher.auth.login');
        }

        if ($user->hasRole($this->teacherRoles)) {
            return $next($request);
        }

        abort(403, 'Teacher access required.');
    }
}
