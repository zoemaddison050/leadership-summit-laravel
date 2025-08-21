<?php

namespace App\Http\Controllers;

use App\Models\Speaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpeakerController extends Controller
{
    /**
     * Display a listing of speakers for admin.
     */
    public function index()
    {
        $speakers = Speaker::withCount('sessions')->paginate(15);
        return view('admin.speakers.index', compact('speakers'));
    }

    /**
     * Show the form for creating a new speaker.
     */
    public function create()
    {
        return view('admin.speakers.create');
    }

    /**
     * Store a newly created speaker in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'required|string',
            'position' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = Str::uuid() . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs('speakers', $filename, 'public');
            $validated['photo'] = $path;
        }

        Speaker::create($validated);

        return redirect()->route('admin.speakers.index')
            ->with('success', 'Speaker created successfully.');
    }

    /**
     * Display the specified speaker.
     */
    public function show(Speaker $speaker)
    {
        $speaker->load('sessions.event');
        return view('admin.speakers.show', compact('speaker'));
    }

    /**
     * Show the form for editing the specified speaker.
     */
    public function edit(Speaker $speaker)
    {
        return view('admin.speakers.edit', compact('speaker'));
    }

    /**
     * Update the specified speaker in storage.
     */
    public function update(Request $request, Speaker $speaker)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'required|string',
            'position' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($speaker->photo && Storage::disk('public')->exists($speaker->photo)) {
                Storage::disk('public')->delete($speaker->photo);
            }

            $photo = $request->file('photo');
            $filename = Str::uuid() . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs('speakers', $filename, 'public');
            $validated['photo'] = $path;
        }

        $speaker->update($validated);

        return redirect()->route('admin.speakers.index')
            ->with('success', 'Speaker updated successfully.');
    }

    /**
     * Remove the specified speaker from storage.
     */
    public function destroy(Speaker $speaker)
    {
        // Delete photo if exists
        if ($speaker->photo && Storage::disk('public')->exists($speaker->photo)) {
            Storage::disk('public')->delete($speaker->photo);
        }

        $speaker->delete();

        return redirect()->route('admin.speakers.index')
            ->with('success', 'Speaker deleted successfully.');
    }

    /**
     * Display a listing of speakers for public view.
     */
    public function publicIndex(Request $request)
    {
        $query = Speaker::withCount('sessions');

        // Handle search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('bio', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        // Handle category filtering (basic implementation)
        if ($request->filled('category')) {
            $category = $request->category;
            $query->where(function ($q) use ($category) {
                switch ($category) {
                    case 'ceo':
                        $q->where('position', 'like', '%CEO%')
                            ->orWhere('position', 'like', '%Chief Executive%')
                            ->orWhere('position', 'like', '%President%');
                        break;
                    case 'entrepreneur':
                        $q->where('position', 'like', '%Founder%')
                            ->orWhere('position', 'like', '%Entrepreneur%');
                        break;
                    case 'academic':
                        $q->where('position', 'like', '%Professor%')
                            ->orWhere('position', 'like', '%Dr.%')
                            ->orWhere('position', 'like', '%Academic%');
                        break;
                    case 'consultant':
                        $q->where('position', 'like', '%Consultant%')
                            ->orWhere('position', 'like', '%Advisor%');
                        break;
                }
            });
        }

        $speakers = $query->paginate(12);

        // Get featured speakers (speakers with most sessions for now)
        $featuredSpeakers = Speaker::withCount('sessions')
            ->orderBy('sessions_count', 'desc')
            ->limit(4)
            ->get();

        return view('speakers.index', compact('speakers', 'featuredSpeakers'));
    }

    /**
     * Display the specified speaker for public view.
     */
    public function publicShow(Speaker $speaker)
    {
        $speaker->load('sessions.event');
        return view('speakers.show', compact('speaker'));
    }

    /**
     * Handle bulk actions for speakers.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete',
            'speakers' => 'required|array',
            'speakers.*' => 'exists:speakers,id',
        ]);

        $speakers = Speaker::whereIn('id', $request->speakers)->get();

        switch ($request->action) {
            case 'delete':
                foreach ($speakers as $speaker) {
                    // Delete photo if exists
                    if ($speaker->photo && Storage::disk('public')->exists($speaker->photo)) {
                        Storage::disk('public')->delete($speaker->photo);
                    }
                    $speaker->delete();
                }
                $message = count($speakers) . ' speaker(s) deleted successfully.';
                break;
        }

        return redirect()->route('admin.speakers.index')
            ->with('success', $message);
    }
}
