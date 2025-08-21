<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     */
    public function index(Request $request)
    {
        $query = Event::query()->withCount('registrations');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('start_date', '<=', $request->get('date_to'));
        }

        // Order by start date
        $query->orderBy('start_date', 'desc');

        $events = $query->paginate(15)->appends($request->query());

        return view('admin.events.index', compact('events'));
    }

    /**
     * Show the form for creating a new event.
     */
    public function create()
    {
        return view('admin.events.create');
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date|after:now',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => ['required', Rule::in(['draft', 'published', 'featured', 'cancelled'])],
        ]);

        // Generate slug from title
        $validated['slug'] = Str::slug($validated['title']);

        // Ensure slug is unique
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Event::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('events', 'public');
        }

        $event = Event::create($validated);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event created successfully.');
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event)
    {
        $event->load(['tickets', 'sessions', 'registrations']);
        return view('admin.events.show', compact('event'));
    }

    /**
     * Show the form for editing the specified event.
     */
    public function edit(Event $event)
    {
        return view('admin.events.edit', compact('event'));
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => ['required', Rule::in(['draft', 'published', 'featured', 'cancelled'])],
        ]);

        // Update slug if title changed
        if ($validated['title'] !== $event->title) {
            $validated['slug'] = Str::slug($validated['title']);

            // Ensure slug is unique (excluding current event)
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Event::where('slug', $validated['slug'])->where('id', '!=', $event->id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($event->featured_image) {
                Storage::disk('public')->delete($event->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')->store('events', 'public');
        }

        $event->update($validated);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event updated successfully.');
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy(Event $event)
    {
        // Delete associated image
        if ($event->featured_image) {
            Storage::disk('public')->delete($event->featured_image);
        }

        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Event deleted successfully.');
    }

    /**
     * Handle bulk actions on events.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:publish,draft,feature,delete',
            'event_ids' => 'required|json',
        ]);

        $eventIds = json_decode($request->event_ids, true);
        $action = $request->action;

        switch ($action) {
            case 'publish':
                Event::whereIn('id', $eventIds)->update(['status' => 'published']);
                $message = 'Events published successfully.';
                break;
            case 'draft':
                Event::whereIn('id', $eventIds)->update(['status' => 'draft']);
                $message = 'Events set as draft successfully.';
                break;
            case 'feature':
                Event::whereIn('id', $eventIds)->update(['status' => 'featured']);
                $message = 'Events featured successfully.';
                break;
            case 'delete':
                $events = Event::whereIn('id', $eventIds)->get();
                foreach ($events as $event) {
                    if ($event->featured_image) {
                        Storage::disk('public')->delete($event->featured_image);
                    }
                }
                Event::whereIn('id', $eventIds)->delete();
                $message = 'Events deleted successfully.';
                break;
        }

        return redirect()->route('admin.events.index')
            ->with('success', $message);
    }

    /**
     * Export events data.
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');

        // This is a placeholder for export functionality
        // In a real implementation, you would use packages like Laravel Excel
        return redirect()->route('admin.events.index')
            ->with('info', 'Export functionality will be implemented in a future update.');
    }

    /**
     * Display events in calendar view.
     */
    public function calendar(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        // Get events for the specified month
        $events = Event::where('status', '!=', 'draft')
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->orderBy('start_date')
            ->get();

        // Get events for calendar navigation (next/prev months)
        $prevMonth = now()->setYear($year)->setMonth($month)->subMonth();
        $nextMonth = now()->setYear($year)->setMonth($month)->addMonth();

        $prevMonthEvents = Event::where('status', '!=', 'draft')
            ->whereYear('start_date', $prevMonth->year)
            ->whereMonth('start_date', $prevMonth->month)
            ->count();

        $nextMonthEvents = Event::where('status', '!=', 'draft')
            ->whereYear('start_date', $nextMonth->year)
            ->whereMonth('start_date', $nextMonth->month)
            ->count();

        return view('events.calendar', compact(
            'events',
            'year',
            'month',
            'prevMonth',
            'nextMonth',
            'prevMonthEvents',
            'nextMonthEvents'
        ));
    }

    /**
     * Display public events listing with filtering and search.
     */
    public function publicIndex(Request $request)
    {
        $query = Event::where('status', '!=', 'draft')
            ->with(['tickets'])
            ->withCount('registrations');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Category/Type filter (if you add a category field later)
        if ($request->filled('type')) {
            // This would require adding a 'type' or 'category' field to events table
            // $query->where('type', $request->get('type'));
        }

        // Date filter
        if ($request->filled('date')) {
            $date = $request->get('date');
            if ($date === 'today') {
                $query->whereDate('start_date', today());
            } elseif ($date === 'week') {
                $query->whereBetween('start_date', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($date === 'month') {
                $query->whereMonth('start_date', now()->month)
                    ->whereYear('start_date', now()->year);
            }
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Order by start date
        $query->orderBy('start_date', 'asc');

        $events = $query->paginate(12)->withQueryString();

        return view('events.index', compact('events'));
    }

    /**
     * Display a single event for public viewing.
     */
    public function publicShow($slug)
    {
        $event = Event::where('slug', $slug)
            ->where('status', '!=', 'draft')
            ->with([
                'tickets' => function ($query) {
                    $query->where('is_active', true)
                        ->where(function ($q) {
                            $q->where('available', '>', 0)
                                ->orWhereNull('available');
                        })
                        ->where(function ($q) {
                            $q->whereNull('sale_start')
                                ->orWhere('sale_start', '<=', now());
                        })
                        ->where(function ($q) {
                            $q->whereNull('sale_end')
                                ->orWhere('sale_end', '>=', now());
                        });
                },
                'sessions.speakers',
                'registrations'
            ])
            ->firstOrFail();

        return view('events.show', compact('event'));
    }
}
