<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */

    public function handle(Request $request, Closure $next, $role)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $user = auth()->user();

        if ($user->role === 'admin') {
            return $next($request);
        }

        $allowedRoles = array_filter(array_map('trim', explode(',', (string) $role)));

        if (
            $user->role === 'referral_officer'
            && in_array('social_worker', $allowedRoles, true)
            && $user->canAccessSocialWorkerModule()
        ) {
            return $next($request);
        }

        if (! in_array($user->role, $allowedRoles, true)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
