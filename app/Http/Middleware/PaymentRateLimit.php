<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class PaymentRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '10', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            // Check if this is a payment confirmation route that should show UI
            $routeName = $request->route()->getName();
            
            // For payment confirmation and processing routes, redirect to event page with error
            if (in_array($routeName, ['payment.confirm', 'payment.process'])) {
                $event = $request->route('event');
                return redirect()->route('events.show', $event)
                    ->with('error', "Too many payment attempts. Please wait {$minutes} minutes before trying again for security reasons.");
            }

            // Check if request expects JSON response
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Too many payment attempts. Please try again in ' . $minutes . ' minutes.',
                    'retry_after' => $seconds
                ], 429);
            }

            // For regular web requests, redirect back with error
            return redirect()->back()
                ->with('error', "Too many payment attempts. Please wait {$minutes} minutes before trying again for security reasons.");
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $response;
    }

    /**
     * Resolve the rate limiting signature for the request.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // Use IP address and user agent for anonymous users
        // Add user ID if authenticated for more specific rate limiting
        $signature = $request->ip() . '|' . $request->userAgent();

        if ($request->user()) {
            $signature .= '|user:' . $request->user()->id;
        }

        return 'payment_rate_limit:' . sha1($signature);
    }
}
