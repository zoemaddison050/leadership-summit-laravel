<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $query = Registration::with(['event', 'user', 'paymentTransactions']);

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status') && $request->payment_status !== '') {
            if ($request->payment_status === 'completed') {
                $query->whereNotNull('payment_completed_at');
            } elseif ($request->payment_status === 'pending') {
                $query->whereNull('payment_completed_at')
                    ->whereNotNull('payment_method');
            } elseif ($request->payment_status === 'refunded') {
                $query->whereNotNull('refunded_at');
            } elseif ($request->payment_status === 'not_started') {
                $query->whereNull('payment_method');
            } else {
                // Legacy payment status filtering
                $query->where('payment_status', $request->payment_status);
            }
        }

        // Filter by payment method
        if ($request->has('payment_method') && $request->payment_method !== '') {
            $query->byPaymentMethod($request->payment_method);
        }

        // Filter by payment provider
        if ($request->has('payment_provider') && $request->payment_provider !== '') {
            $query->byPaymentProvider($request->payment_provider);
        }

        // Search by attendee name, email, or transaction ID
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('attendee_name', 'like', "%{$search}%")
                    ->orWhere('attendee_email', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }

        $registrations = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.registrations.index', compact('registrations'));
    }

    public function show(Registration $registration)
    {
        $registration->load(['event', 'user']);
        return view('admin.registrations.show', compact('registration'));
    }

    public function updateStatus(Request $request, Registration $registration)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,declined'
        ]);

        $registration->update([
            'registration_status' => $request->status,
            'confirmed_at' => $request->status === 'confirmed' ? now() : null,
            'confirmed_by' => $request->status === 'confirmed' ? auth()->id() : null,
            'declined_at' => $request->status === 'declined' ? now() : null,
            'declined_by' => $request->status === 'declined' ? auth()->id() : null,
        ]);

        return redirect()->back()->with('success', 'Registration status updated successfully.');
    }

    public function destroy(Registration $registration)
    {
        $registration->delete();
        return redirect()->route('admin.registrations.index')->with('success', 'Registration deleted successfully.');
    }
}
