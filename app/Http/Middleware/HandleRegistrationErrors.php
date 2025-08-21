<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HandleRegistrationErrors
{
    /**
     * Handle an incoming request and catch registration-specific errors.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (\Exception $e) {
            // Log the error with context
            Log::error('Registration error caught by middleware', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_data' => [
                    'has_registration_data' => $request->session()->has('registration_data'),
                    'has_ticket_selection' => $request->session()->has('ticket_selection'),
                ]
            ]);

            // Handle specific registration errors
            if ($this->isRegistrationRoute($request)) {
                return $this->handleRegistrationError($request, $e);
            }

            // Re-throw for other error handlers
            throw $e;
        }
    }

    /**
     * Check if the current route is registration-related.
     *
     * @param Request $request
     * @return bool
     */
    private function isRegistrationRoute(Request $request): bool
    {
        $registrationRoutes = [
            'events.register.form',
            'events.register.process',
            'payment.crypto.init',
            'payment.crypto.show',
            'payment.confirm',
            'payment.process'
        ];

        $currentRoute = $request->route()?->getName();

        return in_array($currentRoute, $registrationRoutes) ||
            str_contains($request->path(), 'register') ||
            str_contains($request->path(), 'payment');
    }

    /**
     * Handle registration-specific errors with user-friendly responses.
     *
     * @param Request $request
     * @param \Exception $e
     * @return Response
     */
    private function handleRegistrationError(Request $request, \Exception $e): Response
    {
        // Clear potentially corrupted session data
        $request->session()->forget(['registration_data', 'ticket_selection']);

        // Determine appropriate error message and redirect
        $errorMessage = $this->getErrorMessage($e);
        $redirectRoute = $this->getRedirectRoute($request);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'redirect_url' => route($redirectRoute)
            ], 500);
        }

        return redirect()->route($redirectRoute)
            ->with('error', $errorMessage)
            ->with('registration_error', true);
    }

    /**
     * Get user-friendly error message based on exception type.
     *
     * @param \Exception $e
     * @return string
     */
    private function getErrorMessage(\Exception $e): string
    {
        // Database connection errors
        if (
            str_contains($e->getMessage(), 'database') ||
            str_contains($e->getMessage(), 'connection')
        ) {
            return 'We\'re experiencing technical difficulties. Please try again in a few minutes or contact support if the problem persists.';
        }

        // Validation errors
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return 'Please check your information and try again. Make sure all required fields are filled out correctly.';
        }

        // Session/timeout errors
        if (
            str_contains($e->getMessage(), 'session') ||
            str_contains($e->getMessage(), 'expired')
        ) {
            return 'Your session has expired for security reasons. Please start your registration again - it only takes a few minutes.';
        }

        // Lock/duplicate errors
        if (
            str_contains($e->getMessage(), 'lock') ||
            str_contains($e->getMessage(), 'duplicate')
        ) {
            return 'This registration is currently being processed or already exists. Please wait a few minutes and try again, or contact support for assistance.';
        }

        // Payment errors
        if (str_contains($e->getMessage(), 'payment')) {
            return 'There was an issue processing your payment information. Please try again or contact support for assistance.';
        }

        // Generic error for unknown issues
        return 'An unexpected error occurred during registration. Please try again or contact our support team if the problem continues.';
    }

    /**
     * Determine the appropriate redirect route based on the request.
     *
     * @param Request $request
     * @return string
     */
    private function getRedirectRoute(Request $request): string
    {
        // Try to extract event from route parameters
        $event = $request->route('event');

        if ($event) {
            return 'events.show';
        }

        // Check if we can get event from session data
        $registrationData = $request->session()->get('registration_data');
        if ($registrationData && isset($registrationData['event_id'])) {
            try {
                $event = \App\Models\Event::find($registrationData['event_id']);
                if ($event) {
                    return 'events.show';
                }
            } catch (\Exception $e) {
                Log::warning('Could not find event from session data', [
                    'event_id' => $registrationData['event_id']
                ]);
            }
        }

        // Default fallback
        return 'events.index';
    }
}
