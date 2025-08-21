<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role): Response
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

        // Check if user has the required role
        if ($user->role->name !== $role) {
            abort(403, 'Access denied. Insufficient permissions.');
        }

        return $next($request);
    }
}
