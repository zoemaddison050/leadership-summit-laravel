<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Create a new order for a registration.
     */
    public function createOrderForRegistration(Registration $registration, array $billingDetails = []): Order
    {
        try {
            DB::beginTransaction();

            // Create order
            $order = Order::create([
                'user_id' => $registration->user_id,
                'status' => Order::STATUS_PENDING,
                'subtotal' => $registration->ticket->price,
                'tax_amount' => $this->calculateTax($registration->ticket->price),
                'total_amount' => $registration->ticket->price + $this->calculateTax($registration->ticket->price),
                'currency' => 'USD',
                'billing_details' => $billingDetails,
            ]);

            // Associate registration with order
            $registration->update(['order_id' => $order->id]);

            DB::commit();

            Log::info('Order created for registration', [
                'order_id' => $order->id,
                'registration_id' => $registration->id,
                'total_amount' => $order->total_amount,
            ]);

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create order for registration', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create an order for multiple registrations.
     */
    public function createOrderForMultipleRegistrations(array $registrations, User $user, array $billingDetails = []): Order
    {
        try {
            DB::beginTransaction();

            $subtotal = collect($registrations)->sum(function ($registration) {
                return $registration->ticket->price;
            });

            $taxAmount = $this->calculateTax($subtotal);
            $totalAmount = $subtotal + $taxAmount;

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'status' => Order::STATUS_PENDING,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => 'USD',
                'billing_details' => $billingDetails,
            ]);

            // Associate registrations with order
            foreach ($registrations as $registration) {
                $registration->update(['order_id' => $order->id]);
            }

            DB::commit();

            Log::info('Order created for multiple registrations', [
                'order_id' => $order->id,
                'registration_count' => count($registrations),
                'total_amount' => $order->total_amount,
            ]);

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create order for multiple registrations', [
                'user_id' => $user->id,
                'registration_count' => count($registrations),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Order $order, string $status, ?string $notes = null): bool
    {
        try {
            $oldStatus = $order->status;

            $updateData = ['status' => $status];

            if ($status === Order::STATUS_COMPLETED) {
                $updateData['completed_at'] = now();

                // Update all registrations to confirmed
                $order->registrations()->update([
                    'status' => 'confirmed',
                    'payment_status' => 'completed',
                ]);
            } elseif ($status === Order::STATUS_CANCELLED) {
                $updateData['cancelled_at'] = now();

                // Update all registrations to cancelled
                $order->registrations()->update([
                    'status' => 'cancelled',
                ]);

                // Return ticket availability
                foreach ($order->registrations as $registration) {
                    $registration->ticket->increment('available');
                }
            }

            if ($notes) {
                $updateData['notes'] = $notes;
            }

            $order->update($updateData);

            Log::info('Order status updated', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'notes' => $notes,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update order status', [
                'order_id' => $order->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get order summary.
     */
    public function getOrderSummary(Order $order): array
    {
        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'subtotal' => $order->subtotal,
            'tax_amount' => $order->tax_amount,
            'discount_amount' => $order->discount_amount,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'created_at' => $order->created_at,
            'completed_at' => $order->completed_at,
            'cancelled_at' => $order->cancelled_at,
            'registrations' => $order->registrations->map(function ($registration) {
                return [
                    'id' => $registration->id,
                    'event_title' => $registration->event->title,
                    'ticket_name' => $registration->ticket->name,
                    'ticket_price' => $registration->ticket->price,
                    'status' => $registration->status,
                    'payment_status' => $registration->payment_status,
                ];
            }),
            'billing_details' => $order->billing_details,
        ];
    }

    /**
     * Get user's order history.
     */
    public function getUserOrderHistory(User $user, int $perPage = 10)
    {
        return $user->orders()
            ->with(['registrations.event', 'registrations.ticket'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Cancel order.
     */
    public function cancelOrder(Order $order, ?string $reason = null): bool
    {
        if ($order->isCancelled() || $order->isCompleted()) {
            return false;
        }

        return $this->updateOrderStatus($order, Order::STATUS_CANCELLED, $reason);
    }

    /**
     * Process order completion.
     */
    public function completeOrder(Order $order): bool
    {
        if ($order->isCompleted()) {
            return true;
        }

        // Check if all payments are completed
        $allPaymentsCompleted = $order->registrations->every(function ($registration) {
            return $registration->payment_status === 'completed';
        });

        if (!$allPaymentsCompleted) {
            return false;
        }

        return $this->updateOrderStatus($order, Order::STATUS_COMPLETED);
    }

    /**
     * Apply discount to order.
     */
    public function applyDiscount(Order $order, float $discountAmount, ?string $discountCode = null): bool
    {
        try {
            $newTotal = $order->subtotal + $order->tax_amount - $discountAmount;

            if ($newTotal < 0) {
                $discountAmount = $order->subtotal + $order->tax_amount;
                $newTotal = 0;
            }

            $order->update([
                'discount_amount' => $discountAmount,
                'total_amount' => $newTotal,
                'notes' => $discountCode ? "Discount code applied: {$discountCode}" : 'Manual discount applied',
            ]);

            Log::info('Discount applied to order', [
                'order_id' => $order->id,
                'discount_amount' => $discountAmount,
                'discount_code' => $discountCode,
                'new_total' => $newTotal,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to apply discount to order', [
                'order_id' => $order->id,
                'discount_amount' => $discountAmount,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Calculate tax amount.
     */
    private function calculateTax(float $amount): float
    {
        // 8% tax rate (this should be configurable)
        return round($amount * 0.08, 2);
    }

    /**
     * Generate order receipt data.
     */
    public function generateReceiptData(Order $order): array
    {
        return [
            'order' => $this->getOrderSummary($order),
            'customer' => [
                'name' => $order->user->name,
                'email' => $order->user->email,
            ],
            'billing_details' => $order->billing_details,
            'items' => $order->registrations->map(function ($registration) {
                return [
                    'description' => $registration->event->title . ' - ' . $registration->ticket->name,
                    'quantity' => 1,
                    'unit_price' => $registration->ticket->price,
                    'total_price' => $registration->ticket->price,
                ];
            }),
            'generated_at' => now(),
        ];
    }
}
