<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Speaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SpeakerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $speakers = Speaker::withCount('sessions')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.speakers.index', compact('speakers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.speakers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'required|string',
            'position' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('speakers', 'public');
        }

        Speaker::create($data);

        return redirect()->route('admin.speakers.index')
            ->with('success', 'Speaker created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Speaker $speaker)
    {
        $speaker->load('sessions');
        return view('admin.speakers.show', compact('speaker'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Speaker $speaker)
    {
        return view('admin.speakers.edit', compact('speaker'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Speaker $speaker)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'required|string',
            'position' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($speaker->photo) {
                Storage::disk('public')->delete($speaker->photo);
            }
            $data['photo'] = $request->file('photo')->store('speakers', 'public');
        }

        $speaker->update($data);

        return redirect()->route('admin.speakers.index')
            ->with('success', 'Speaker updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Speaker $speaker)
    {
        // Delete photo
        if ($speaker->photo) {
            Storage::disk('public')->delete($speaker->photo);
        }

        $speaker->delete();

        return redirect()->route('admin.speakers.index')
            ->with('success', 'Speaker deleted successfully.');
    }

    /**
     * Handle bulk actions on speakers.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'speaker_ids' => 'required|array',
            'speaker_ids.*' => 'exists:speakers,id'
        ]);

        $speakerIds = $request->speaker_ids;
        $action = $request->action;

        switch ($action) {
            case 'delete':
                $speakers = Speaker::whereIn('id', $speakerIds)->get();
                foreach ($speakers as $speaker) {
                    if ($speaker->photo) {
                        Storage::disk('public')->delete($speaker->photo);
                    }
                }
                Speaker::whereIn('id', $speakerIds)->delete();
                $message = 'Selected speakers deleted successfully.';
                break;
            case 'activate':
                Speaker::whereIn('id', $speakerIds)->update(['is_active' => true]);
                $message = 'Selected speakers activated successfully.';
                break;
            case 'deactivate':
                Speaker::whereIn('id', $speakerIds)->update(['is_active' => false]);
                $message = 'Selected speakers deactivated successfully.';
                break;
        }

        return redirect()->route('admin.speakers.index')
            ->with('success', $message);
    }
}
