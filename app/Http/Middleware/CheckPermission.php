<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check if user has a role
        if (!$user->role) {
            abort(403, 'Access denied. No role assigned.');
        }

        // Check if user's role has the required permission
        $permissions = $user->role->permissions ?? [];

        if (!in_array($permission, $permissions)) {
            abort(403, 'Access denied. Insufficient permissions.');
        }

        return $next($request);
    }
}
