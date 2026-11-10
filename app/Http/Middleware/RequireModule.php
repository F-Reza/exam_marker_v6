<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->canAccessModule($module)) {
            return $next($request);
        }

        $roleRestricted = in_array($module, ['admin', 'student-portal', 'teacher-workspace'], true)
            || ($user->isStudent() && in_array($module, ['students', 'student-import', 'bulk', 'analytics', 'communications', 'parent-email', 'parent-sms', 'team', 'result-edit', 'result-finalise', 'result-recheck'], true))
            || ($module === 'team' && !$user->isCoaching());

        $message = $roleRestricted
            ? 'This area is not available for your account role.'
            : 'This feature is not included in your current plan. Compare the available plans below.';

        if ($request->isMethod('GET') && !$request->expectsJson()) {
            return redirect()->route($roleRestricted ? 'dashboard' : 'billing.index')->with('error', $message);
        }

        abort(403, $message);
    }
}
