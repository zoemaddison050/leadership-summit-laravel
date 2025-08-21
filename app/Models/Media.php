<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'file_name',
        'mime_type',
        'path',
        'size',
        'disk',
        'metadata',
        'alt_text',
        'description',
        'uploaded_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who uploaded this media.
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the full URL to the media file.
     */
    public function getUrlAttribute()
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get the full path to the media file.
     */
    public function getFullPathAttribute()
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    /**
     * Check if the media is an image.
     */
    public function isImage()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if the media is a video.
     */
    public function isVideo()
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if the media is an audio file.
     */
    public function isAudio()
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    /**
     * Check if the media is a document.
     */
    public function isDocument()
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
        ]);
    }

    /**
     * Get human readable file size.
     */
    public function getHumanSizeAttribute()
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the file extension.
     */
    public function getExtensionAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Scope to filter by media type.
     */
    public function scopeOfType($query, $type)
    {
        switch ($type) {
            case 'image':
                return $query->where('mime_type', 'like', 'image/%');
            case 'video':
                return $query->where('mime_type', 'like', 'video/%');
            case 'audio':
                return $query->where('mime_type', 'like', 'audio/%');
            case 'document':
                return $query->whereIn('mime_type', [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'text/plain',
                    'text/csv',
                ]);
            default:
                return $query;
        }
    }

    /**
     * Scope to search media by name or description.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('file_name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('alt_text', 'like', "%{$search}%");
        });
    }

    /**
     * Delete the media file when the model is deleted.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($media) {
            if (Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }
        });
    }
}
