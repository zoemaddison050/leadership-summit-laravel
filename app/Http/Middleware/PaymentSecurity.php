<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PaymentSecurity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log payment security check
        Log::info('Payment security middleware triggered', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->route()->getName(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString()
        ]);

        // Validate HTTPS for payment requests
        if (config('payment_security.require_https.enabled') && !$request->secure()) {
            Log::warning('Insecure payment request blocked', [
                'ip' => $request->ip(),
                'route' => $request->route()->getName(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'message' => 'Payment requests must use HTTPS for security.'
            ], 400);
        }

        // Validate required headers for payment requests
        if (!$request->hasHeader('User-Agent')) {
            Log::warning('Payment request without User-Agent blocked', [
                'ip' => $request->ip(),
                'route' => $request->route()->getName()
            ]);

            return response()->json([
                'message' => 'Invalid request headers.'
            ], 400);
        }

        // Check for suspicious patterns in User-Agent
        $userAgent = $request->userAgent();
        $suspiciousPatterns = config('payment_security.blocked_user_agents', []);

        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                Log::warning('Suspicious User-Agent in payment request', [
                    'ip' => $request->ip(),
                    'user_agent' => $userAgent,
                    'route' => $request->route()->getName()
                ]);

                return response()->json([
                    'message' => 'Request blocked for security reasons.'
                ], 403);
            }
        }

        // Validate payment amount if present in request
        if ($request->has('amount') || $request->has('total_amount')) {
            $amount = $request->input('amount', $request->input('total_amount'));

            if (!$this->validatePaymentAmount($amount)) {
                Log::warning('Invalid payment amount detected', [
                    'ip' => $request->ip(),
                    'amount' => $amount,
                    'route' => $request->route()->getName()
                ]);

                return response()->json([
                    'message' => 'Invalid payment amount.'
                ], 400);
            }
        }

        // Validate currency if present
        if ($request->has('currency')) {
            $currency = $request->input('currency');

            if (!$this->validateCurrency($currency)) {
                Log::warning('Invalid currency detected', [
                    'ip' => $request->ip(),
                    'currency' => $currency,
                    'route' => $request->route()->getName()
                ]);

                return response()->json([
                    'message' => 'Invalid currency code.'
                ], 400);
            }
        }

        return $next($request);
    }

    /**
     * Validate payment amount.
     */
    protected function validatePaymentAmount($amount): bool
    {
        // Check if amount is numeric
        if (!is_numeric($amount)) {
            return false;
        }

        $amount = (float) $amount;

        // Check for reasonable bounds
        $minAmount = config('payment_security.amount_validation.min_amount', 0.01);
        $maxAmount = config('payment_security.amount_validation.max_amount', 100000.00);

        if ($amount < $minAmount || $amount > $maxAmount) {
            return false;
        }

        // Check for too many decimal places
        $maxDecimals = config('payment_security.amount_validation.max_decimal_places', 2);
        if (strpos((string) $amount, '.') !== false) {
            $decimals = strlen(substr(strrchr((string) $amount, '.'), 1));
            if ($decimals > $maxDecimals) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate currency code.
     */
    protected function validateCurrency(string $currency): bool
    {
        $allowedCurrencies = config('payment_security.allowed_currencies', ['USD']);

        return in_array(strtoupper($currency), $allowedCurrencies);
    }
}
