<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_description',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });

        static::updating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    /**
     * Scope a query to only include published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include draft pages.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include archived pages.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Check if the page is published.
     */
    public function isPublished()
    {
        return $this->status === 'published';
    }

    /**
     * Check if the page is draft.
     */
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the page is archived.
     */
    public function isArchived()
    {
        return $this->status === 'archived';
    }

    /**
     * Get the page's public URL.
     */
    public function getPublicUrlAttribute()
    {
        if ($this->isPublished()) {
            return route('pages.show', $this->slug);
        }
        return null;
    }

    /**
     * Get a truncated version of the content.
     */
    public function getExcerptAttribute()
    {
        return $this->excerpt(150);
    }

    /**
     * Get a truncated version of the content with custom length.
     */
    public function excerpt($length = 150)
    {
        return Str::limit(strip_tags($this->content ?? ''), $length);
    }
}
