<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\Event;
use App\Models\Speaker;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * Display a listing of sessions for admin.
     */
    public function index()
    {
        $sessions = Session::with(['event', 'speakers'])
            ->orderBy('start_time', 'desc')
            ->paginate(15);

        return view('admin.sessions.index', compact('sessions'));
    }

    /**
     * Show the form for creating a new session.
     */
    public function create()
    {
        $events = Event::orderBy('title')->get();
        $speakers = Speaker::orderBy('name')->get();

        return view('admin.sessions.create', compact('events', 'speakers'));
    }

    /**
     * Store a newly created session in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'speakers' => 'nullable|array',
            'speakers.*' => 'exists:speakers,id',
            'category' => 'nullable|string|max:100',
        ]);

        $session = Session::create($validated);

        // Attach speakers if provided
        if ($request->filled('speakers')) {
            $session->speakers()->attach($request->speakers);
        }

        return redirect()->route('admin.sessions.index')
            ->with('success', 'Session created successfully.');
    }

    /**
     * Display the specified session.
     */
    public function show(Session $session)
    {
        $session->load(['event', 'speakers']);
        return view('admin.sessions.show', compact('session'));
    }

    /**
     * Show the form for editing the specified session.
     */
    public function edit(Session $session)
    {
        $events = Event::orderBy('title')->get();
        $speakers = Speaker::orderBy('name')->get();
        $session->load('speakers');

        return view('admin.sessions.edit', compact('session', 'events', 'speakers'));
    }

    /**
     * Update the specified session in storage.
     */
    public function update(Request $request, Session $session)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'speakers' => 'nullable|array',
            'speakers.*' => 'exists:speakers,id',
            'category' => 'nullable|string|max:100',
        ]);

        $session->update($validated);

        // Sync speakers
        if ($request->has('speakers')) {
            $session->speakers()->sync($request->speakers ?? []);
        }

        return redirect()->route('admin.sessions.index')
            ->with('success', 'Session updated successfully.');
    }

    /**
     * Remove the specified session from storage.
     */
    public function destroy(Session $session)
    {
        $session->speakers()->detach();
        $session->delete();

        return redirect()->route('admin.sessions.index')
            ->with('success', 'Session deleted successfully.');
    }

    /**
     * Handle bulk actions for sessions.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete',
            'sessions' => 'required|array',
            'sessions.*' => 'exists:event_sessions,id',
        ]);

        $sessions = Session::whereIn('id', $request->sessions)->get();

        switch ($request->action) {
            case 'delete':
                foreach ($sessions as $session) {
                    $session->speakers()->detach();
                    $session->delete();
                }
                $message = count($sessions) . ' session(s) deleted successfully.';
                break;
        }

        return redirect()->route('admin.sessions.index')
            ->with('success', $message);
    }

    /**
     * Display sessions by category for filtering.
     */
    public function byCategory(Request $request)
    {
        $category = $request->get('category');
        $query = Session::with(['event', 'speakers']);

        if ($category) {
            $query->where('category', $category);
        }

        $sessions = $query->orderBy('start_time', 'desc')->paginate(15);

        return view('admin.sessions.index', compact('sessions', 'category'));
    }

    /**
     * Display sessions for a specific event.
     */
    public function byEvent(Event $event)
    {
        $sessions = $event->sessions()
            ->with('speakers')
            ->orderBy('start_time')
            ->paginate(15);

        return view('admin.sessions.index', compact('sessions', 'event'));
    }

    /**
     * Display public sessions listing.
     */
    public function publicIndex(Request $request)
    {
        $query = Session::with(['event', 'speakers'])
            ->whereHas('event', function ($q) {
                $q->where('status', 'published');
            });

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by event
        if ($request->filled('event')) {
            $query->where('event_id', $request->event);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $sessions = $query->orderBy('start_time')->paginate(12);
        $events = Event::where('status', 'published')->orderBy('title')->get();
        $categories = Session::distinct()->pluck('category')->filter();

        return view('sessions.index', compact('sessions', 'events', 'categories'));
    }

    /**
     * Display the specified session for public view.
     */
    public function publicShow(Session $session)
    {
        $session->load(['event', 'speakers']);

        // Check if the session's event is published
        if (!$session->event || $session->event->status !== 'published') {
            abort(404);
        }

        return view('sessions.show', compact('session'));
    }

    /**
     * Display the agenda page with sessions organized by time and event.
     */
    public function agenda()
    {
        $sessions = Session::with(['event', 'speakers'])
            ->whereHas('event', function ($q) {
                $q->where('status', 'published');
            })
            ->whereNotNull('start_time')
            ->orderBy('start_time')
            ->get();

        $events = Event::where('status', 'published')
            ->whereHas('sessions')
            ->orderBy('start_date')
            ->get();

        return view('agenda', compact('sessions', 'events'));
    }
}
