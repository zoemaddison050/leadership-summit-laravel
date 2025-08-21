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

            return response()->json([
                'message' => 'Too many payment attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                'retry_after' => $seconds
            ], 429);
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
