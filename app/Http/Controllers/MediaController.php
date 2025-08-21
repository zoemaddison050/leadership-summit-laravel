<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class MediaController extends Controller
{
    /**
     * Display a listing of media files.
     */
    public function index(Request $request)
    {
        $query = Media::with('uploader')->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by uploader
        if ($request->filled('uploader')) {
            $query->where('uploaded_by', $request->uploader);
        }

        $media = $query->paginate(24);
        $types = ['image', 'video', 'audio', 'document'];

        return view('admin.media.index', compact('media', 'types'));
    }

    /**
     * Show the form for uploading new media.
     */
    public function create()
    {
        return view('admin.media.create');
    }

    /**
     * Store newly uploaded media files.
     */
    public function store(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max
            'alt_text.*' => 'nullable|string|max:255',
            'description.*' => 'nullable|string|max:1000',
        ]);

        $uploadedFiles = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $index => $file) {
                $originalName = $file->getClientOriginalName();
                $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('media', $fileName, 'public');

                $media = Media::create([
                    'name' => pathinfo($originalName, PATHINFO_FILENAME),
                    'file_name' => $originalName,
                    'mime_type' => $file->getMimeType(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'disk' => 'public',
                    'alt_text' => $request->input("alt_text.{$index}"),
                    'description' => $request->input("description.{$index}"),
                    'uploaded_by' => auth()->id(),
                ]);

                // Generate thumbnails for images
                if ($media->isImage()) {
                    $this->generateThumbnails($media);
                }

                $uploadedFiles[] = $media;
            }
        }

        if (count($uploadedFiles) === 1) {
            return redirect()->route('admin.media.show', $uploadedFiles[0])
                ->with('success', 'File uploaded successfully.');
        }

        return redirect()->route('admin.media.index')
            ->with('success', count($uploadedFiles) . ' files uploaded successfully.');
    }

    /**
     * Display the specified media file.
     */
    public function show(Media $media)
    {
        return view('admin.media.show', compact('media'));
    }

    /**
     * Show the form for editing the specified media.
     */
    public function edit(Media $media)
    {
        return view('admin.media.edit', compact('media'));
    }

    /**
     * Update the specified media file.
     */
    public function update(Request $request, Media $media)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $media->update($validated);

        return redirect()->route('admin.media.show', $media)
            ->with('success', 'Media updated successfully.');
    }

    /**
     * Remove the specified media file.
     */
    public function destroy(Media $media)
    {
        $media->delete();

        return redirect()->route('admin.media.index')
            ->with('success', 'Media deleted successfully.');
    }

    /**
     * Handle bulk actions on media files.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete',
            'media_ids' => 'required|array',
            'media_ids.*' => 'exists:media,id',
        ]);

        $mediaItems = Media::whereIn('id', $request->media_ids)->get();

        switch ($request->action) {
            case 'delete':
                foreach ($mediaItems as $media) {
                    $media->delete();
                }
                $message = count($mediaItems) . ' media files deleted successfully.';
                break;
        }

        return redirect()->route('admin.media.index')
            ->with('success', $message);
    }

    /**
     * API endpoint for media selection modal.
     */
    public function select(Request $request)
    {
        $query = Media::orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $media = $query->paginate(12);

        if ($request->ajax()) {
            return response()->json([
                'media' => $media->items(),
                'pagination' => [
                    'current_page' => $media->currentPage(),
                    'last_page' => $media->lastPage(),
                    'has_more' => $media->hasMorePages(),
                ]
            ]);
        }

        return view('admin.media.select', compact('media'));
    }

    /**
     * Generate thumbnails for image files.
     */
    private function generateThumbnails(Media $media)
    {
        if (!$media->isImage()) {
            return;
        }

        $thumbnailSizes = [
            'thumb' => [150, 150],
            'medium' => [300, 300],
            'large' => [800, 600],
        ];

        $metadata = $media->metadata ?? [];

        foreach ($thumbnailSizes as $size => $dimensions) {
            try {
                $image = Image::make(Storage::disk($media->disk)->path($media->path));
                $image->fit($dimensions[0], $dimensions[1], function ($constraint) {
                    $constraint->upsize();
                });

                $thumbnailPath = 'media/thumbnails/' . $size . '_' . basename($media->path);
                $image->save(Storage::disk($media->disk)->path($thumbnailPath));

                $metadata['thumbnails'][$size] = $thumbnailPath;
            } catch (\Exception $e) {
                // Log error but don't fail the upload
                \Log::warning("Failed to generate {$size} thumbnail for media {$media->id}: " . $e->getMessage());
            }
        }

        $media->update(['metadata' => $metadata]);
    }
}
