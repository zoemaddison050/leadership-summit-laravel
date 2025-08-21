<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets for an event.
     */
    public function index(Event $event)
    {
        $tickets = $event->tickets()->withCount('registrations')->get();
        return view('admin.tickets.index', compact('event', 'tickets'));
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create(Event $event)
    {
        return view('admin.tickets.create', compact('event'));
    }

    /**
     * Store a newly created ticket in storage.
     */
    public function store(Request $request, Event $event)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $validated['event_id'] = $event->id;
        $validated['available'] = $validated['capacity'] ?? null;

        $ticket = Ticket::create($validated);

        return redirect()->route('admin.tickets.index', $event)
            ->with('success', 'Ticket type created successfully.');
    }

    /**
     * Display the specified ticket.
     */
    public function show(Event $event, Ticket $ticket)
    {
        $ticket->load(['registrations.user']);
        return view('admin.tickets.show', compact('event', 'ticket'));
    }

    /**
     * Show the form for editing the specified ticket.
     */
    public function edit(Event $event, Ticket $ticket)
    {
        return view('admin.tickets.edit', compact('event', 'ticket'));
    }

    /**
     * Update the specified ticket in storage.
     */
    public function update(Request $request, Event $event, Ticket $ticket)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'capacity' => 'nullable|integer|min:1',
        ]);

        // If capacity is being changed, adjust available count
        if (isset($validated['capacity']) && $validated['capacity'] !== $ticket->capacity) {
            $sold = $ticket->registrations()->count();
            $validated['available'] = max(0, $validated['capacity'] - $sold);
        }

        $ticket->update($validated);

        return redirect()->route('admin.tickets.index', $event)
            ->with('success', 'Ticket type updated successfully.');
    }

    /**
     * Remove the specified ticket from storage.
     */
    public function destroy(Event $event, Ticket $ticket)
    {
        // Check if ticket has registrations
        if ($ticket->registrations()->count() > 0) {
            return redirect()->route('admin.tickets.index', $event)
                ->with('error', 'Cannot delete ticket type with existing registrations.');
        }

        $ticket->delete();

        return redirect()->route('admin.tickets.index', $event)
            ->with('success', 'Ticket type deleted successfully.');
    }

    /**
     * Update ticket availability.
     */
    public function updateAvailability(Request $request, Event $event, Ticket $ticket)
    {
        $validated = $request->validate([
            'available' => 'required|integer|min:0',
        ]);

        // Ensure available doesn't exceed capacity if capacity is set
        if ($ticket->capacity && $validated['available'] > $ticket->capacity) {
            return response()->json([
                'error' => 'Available tickets cannot exceed capacity.'
            ], 422);
        }

        $ticket->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ticket availability updated successfully.',
            'available' => $ticket->available
        ]);
    }

    /**
     * Get ticket selection interface for public registration.
     */
    public function selection(Event $event)
    {
        $tickets = $event->tickets()
            ->where(function ($query) {
                $query->whereNull('available')
                    ->orWhere('available', '>', 0);
            })
            ->get();

        return view('tickets.selection', compact('event', 'tickets'));
    }
}
