<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PaymentSessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for expired payment session data
        $this->cleanupExpiredPaymentSession($request);

        // Check for expired registration data
        $this->cleanupExpiredRegistrationData($request);

        return $next($request);
    }

    /**
     * Clean up expired payment session data.
     */
    protected function cleanupExpiredPaymentSession(Request $request): void
    {
        $paymentSessionData = session('payment_session_data');

        if ($paymentSessionData && isset($paymentSessionData['payment_expires_at'])) {
            if (now()->gt($paymentSessionData['payment_expires_at'])) {
                Log::info('Cleaning up expired payment session', [
                    'ip' => $request->ip(),
                    'route' => $request->route()?->getName(),
                    'expired_at' => $paymentSessionData['payment_expires_at'],
                    'minutes_expired' => now()->diffInMinutes($paymentSessionData['payment_expires_at'])
                ]);

                session()->forget('payment_session_data');
            }
        }
    }

    /**
     * Clean up expired registration data.
     */
    protected function cleanupExpiredRegistrationData(Request $request): void
    {
        $registrationData = session('registration_data');

        if ($registrationData && isset($registrationData['expires_at'])) {
            if (now()->gt($registrationData['expires_at'])) {
                Log::info('Cleaning up expired registration session', [
                    'ip' => $request->ip(),
                    'route' => $request->route()?->getName(),
                    'expired_at' => $registrationData['expires_at'],
                    'minutes_expired' => now()->diffInMinutes($registrationData['expires_at'])
                ]);

                session()->forget('registration_data');
            }
        }

        // Also clean up old ticket selection data for backward compatibility
        $ticketSelection = session('ticket_selection');

        if ($ticketSelection && isset($ticketSelection['expires_at'])) {
            if (now()->gt($ticketSelection['expires_at'])) {
                Log::info('Cleaning up expired ticket selection session', [
                    'ip' => $request->ip(),
                    'route' => $request->route()?->getName(),
                    'expired_at' => $ticketSelection['expires_at'],
                    'minutes_expired' => now()->diffInMinutes($ticketSelection['expires_at'])
                ]);

                session()->forget('ticket_selection');
            }
        }
    }
}
