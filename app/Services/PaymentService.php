<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Create a new payment record.
     */
    public function createPayment(Registration $registration, string $paymentMethod, array $data = []): Payment
    {
        return Payment::create([
            'registration_id' => $registration->id,
            'payment_method' => $paymentMethod,
            'amount' => $registration->ticket->price,
            'currency' => 'USD',
            'status' => Payment::STATUS_PENDING,
            'crypto_currency' => $data['crypto_currency'] ?? null,
            'crypto_address' => $data['crypto_address'] ?? null,
            'crypto_amount' => $data['crypto_amount'] ?? null,
        ]);
    }

    /**
     * Process card payment (placeholder for actual payment gateway integration).
     */
    public function processCardPayment(Payment $payment, array $cardData): array
    {
        try {
            // This is a placeholder implementation
            // In a real application, you would integrate with a payment gateway like Stripe, PayPal, etc.

            Log::info('Processing card payment', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ]);

            // Simulate payment processing
            $success = $this->simulateCardPayment($cardData);

            if ($success) {
                $transactionId = 'txn_' . uniqid();

                DB::beginTransaction();

                // Update payment status
                $payment->markAsCompleted($transactionId);

                // Update registration status
                $payment->registration->update([
                    'status' => 'confirmed',
                    'payment_status' => 'completed',
                ]);

                DB::commit();

                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'message' => 'Payment processed successfully',
                ];
            } else {
                $payment->markAsFailed('Card payment failed');

                return [
                    'success' => false,
                    'message' => 'Payment failed. Please check your card details and try again.',
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Card payment processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'message' => 'Payment processing failed. Please try again.',
            ];
        }
    }

    /**
     * Process cryptocurrency payment.
     */
    public function processCryptoPayment(Payment $payment, string $cryptoCurrency): array
    {
        try {
            Log::info('Processing crypto payment', [
                'payment_id' => $payment->id,
                'crypto_currency' => $cryptoCurrency,
                'amount' => $payment->amount,
            ]);

            // Generate crypto payment details
            $cryptoData = $this->generateCryptoPaymentData($payment, $cryptoCurrency);

            // Update payment with crypto details
            $payment->update([
                'crypto_currency' => $cryptoCurrency,
                'crypto_address' => $cryptoData['address'],
                'crypto_amount' => $cryptoData['amount'],
                'status' => Payment::STATUS_PROCESSING,
            ]);

            return [
                'success' => true,
                'crypto_address' => $cryptoData['address'],
                'crypto_amount' => $cryptoData['amount'],
                'crypto_currency' => $cryptoCurrency,
                'qr_code' => $cryptoData['qr_code'],
                'message' => 'Please send the exact amount to the provided address',
            ];
        } catch (\Exception $e) {
            Log::error('Crypto payment processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'message' => 'Crypto payment setup failed. Please try again.',
            ];
        }
    }

    /**
     * Verify cryptocurrency payment (would be called by webhook or cron job).
     */
    public function verifyCryptoPayment(Payment $payment): bool
    {
        try {
            // This is a placeholder implementation
            // In a real application, you would check the blockchain or use a service like BlockCypher

            Log::info('Verifying crypto payment', [
                'payment_id' => $payment->id,
                'crypto_address' => $payment->crypto_address,
            ]);

            // Simulate verification (in real implementation, check blockchain)
            $verified = $this->simulateCryptoVerification($payment);

            if ($verified) {
                DB::beginTransaction();

                $payment->markAsCompleted('crypto_' . uniqid());

                $payment->registration->update([
                    'status' => 'confirmed',
                    'payment_status' => 'completed',
                ]);

                DB::commit();

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Crypto payment verification failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get payment status.
     */
    public function getPaymentStatus(Payment $payment): array
    {
        return [
            'status' => $payment->status,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_method' => $payment->payment_method,
            'transaction_id' => $payment->transaction_id,
            'paid_at' => $payment->paid_at,
            'crypto_currency' => $payment->crypto_currency,
            'crypto_address' => $payment->crypto_address,
            'crypto_amount' => $payment->crypto_amount,
        ];
    }

    /**
     * Simulate card payment processing (for demo purposes).
     */
    private function simulateCardPayment(array $cardData): bool
    {
        // Simulate some basic validation
        if (empty($cardData['card_number']) || empty($cardData['expiry']) || empty($cardData['cvc'])) {
            return false;
        }

        // Simulate random success/failure for demo
        return rand(1, 10) > 2; // 80% success rate
    }

    /**
     * Generate cryptocurrency payment data.
     */
    private function generateCryptoPaymentData(Payment $payment, string $cryptoCurrency): array
    {
        // This is a placeholder implementation
        // In a real application, you would generate actual crypto addresses and calculate exchange rates

        $addresses = [
            'bitcoin' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
            'ethereum' => '0x742d35Cc6634C0532925a3b8D4C9db96590b4c5d',
            'litecoin' => 'LdP8Qox1VAhCzLJNqrr74YovaWYyNBUWvL',
        ];

        $exchangeRates = [
            'bitcoin' => 45000,
            'ethereum' => 3000,
            'litecoin' => 150,
        ];

        $address = $addresses[$cryptoCurrency] ?? $addresses['bitcoin'];
        $rate = $exchangeRates[$cryptoCurrency] ?? $exchangeRates['bitcoin'];
        $cryptoAmount = round($payment->amount / $rate, 8);

        return [
            'address' => $address,
            'amount' => $cryptoAmount,
            'qr_code' => $this->generateQRCode($address, $cryptoAmount, $cryptoCurrency),
        ];
    }

    /**
     * Generate QR code for crypto payment.
     */
    private function generateQRCode(string $address, float $amount, string $currency): string
    {
        // This is a placeholder implementation
        // In a real application, you would generate actual QR codes
        return "data:image/svg+xml;base64," . base64_encode(
            '<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
                <rect width="200" height="200" fill="white"/>
                <text x="100" y="100" text-anchor="middle" fill="black">QR Code</text>
                <text x="100" y="120" text-anchor="middle" fill="black" font-size="12">' . strtoupper($currency) . '</text>
            </svg>'
        );
    }

    /**
     * Simulate crypto payment verification.
     */
    private function simulateCryptoVerification(Payment $payment): bool
    {
        // Simulate blockchain verification
        // In real implementation, check if the exact amount was received at the address
        return rand(1, 10) > 3; // 70% success rate for demo
    }
}
