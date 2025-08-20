<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index()
    {
        $stats = [
            'total_registrations' => Registration::count(),
            'confirmed_registrations' => Registration::where('registration_status', 'confirmed')->count(),
            'pending_registrations' => Registration::where('registration_status', 'pending')->count(),
            'total_revenue' => Registration::where('payment_status', 'paid')->sum('total_amount'),
            'total_events' => Event::count(),
            'active_events' => Event::where('status', 'active')->count(),
            'total_users' => User::count(),
            'recent_registrations' => Registration::with(['event'])->latest()->take(10)->get()
        ];

        return view('admin.reports.index', compact('stats'));
    }

    public function registrations(Request $request)
    {
        $query = Registration::with(['event']);

        // Date range filter
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Event filter
        if ($request->has('event_id') && $request->event_id) {
            $query->where('event_id', $request->event_id);
        }

        $registrations = $query->orderBy('created_at', 'desc')->paginate(50);

        // Summary statistics
        $summary = [
            'total_count' => $query->count(),
            'confirmed_count' => (clone $query)->where('registration_status', 'confirmed')->count(),
            'pending_count' => (clone $query)->where('registration_status', 'pending')->count(),
            'total_revenue' => (clone $query)->where('payment_status', 'paid')->sum('total_amount'),
        ];

        $events = Event::all();

        return view('admin.reports.registrations', compact('registrations', 'summary', 'events'));
    }

    public function payments(Request $request)
    {
        $query = Registration::whereNotNull('total_amount');

        // Date range filter
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Payment status filter
        if ($request->has('payment_status') && $request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        $payments = $query->with(['event'])->orderBy('created_at', 'desc')->paginate(50);

        // Payment summary
        $summary = [
            'total_amount' => (clone $query)->sum('total_amount'),
            'paid_amount' => (clone $query)->where('payment_status', 'paid')->sum('total_amount'),
            'pending_amount' => (clone $query)->where('payment_status', 'pending')->sum('total_amount'),
            'failed_amount' => (clone $query)->where('payment_status', 'failed')->sum('total_amount'),
        ];

        return view('admin.reports.payments', compact('payments', 'summary'));
    }

    public function events(Request $request)
    {
        $events = Event::withCount(['registrations'])
            ->withSum('registrations', 'total_amount')
            ->orderBy('start_date', 'desc')
            ->paginate(20);

        return view('admin.reports.events', compact('events'));
    }
}
