<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display user's order history.
     */
    public function index()
    {
        $orders = $this->orderService->getUserOrderHistory(Auth::user());

        return view('orders.index', compact('orders'));
    }

    /**
     * Display a specific order.
     */
    public function show(Order $order)
    {
        // Ensure user can only view their own orders
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $orderSummary = $this->orderService->getOrderSummary($order);

        return view('orders.show', compact('order', 'orderSummary'));
    }

    /**
     * Cancel an order.
     */
    public function cancel(Request $request, Order $order)
    {
        // Ensure user can only cancel their own orders
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        // Validate cancellation reason
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        // Check if order can be cancelled
        if ($order->isCompleted() || $order->isCancelled()) {
            return redirect()->back()
                ->with('error', 'This order cannot be cancelled.');
        }

        // Cancel the order
        $success = $this->orderService->cancelOrder($order, $request->reason);

        if ($success) {
            return redirect()->route('orders.index')
                ->with('success', 'Order cancelled successfully.');
        } else {
            return redirect()->back()
                ->with('error', 'Failed to cancel order. Please try again.');
        }
    }

    /**
     * Download order receipt.
     */
    public function receipt(Order $order)
    {
        // Ensure user can only download their own receipts
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        // Only allow receipt download for completed orders
        if (!$order->isCompleted()) {
            return redirect()->back()
                ->with('error', 'Receipt is only available for completed orders.');
        }

        $receiptData = $this->orderService->generateReceiptData($order);

        return view('orders.receipt', compact('order', 'receiptData'));
    }

    /**
     * Get order status (AJAX endpoint).
     */
    public function status(Order $order)
    {
        // Ensure user can only check their own orders
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $orderSummary = $this->orderService->getOrderSummary($order);

        return response()->json([
            'success' => true,
            'order' => $orderSummary,
        ]);
    }

    /**
     * Admin: View all orders.
     */
    public function adminIndex(Request $request)
    {
        // Check admin permission
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $query = Order::with(['user', 'registrations.event', 'registrations.ticket']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by order number or user name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Admin: View specific order.
     */
    public function adminShow(Order $order)
    {
        // Check admin permission
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $orderSummary = $this->orderService->getOrderSummary($order);

        return view('admin.orders.show', compact('order', 'orderSummary'));
    }

    /**
     * Admin: Update order status.
     */
    public function adminUpdateStatus(Request $request, Order $order)
    {
        // Check admin permission
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled,refunded',
            'notes' => 'nullable|string|max:1000',
        ]);

        $success = $this->orderService->updateOrderStatus(
            $order,
            $request->status,
            $request->notes
        );

        if ($success) {
            return redirect()->back()
                ->with('success', 'Order status updated successfully.');
        } else {
            return redirect()->back()
                ->with('error', 'Failed to update order status.');
        }
    }
}
