<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Lesson extends Model
{
    protected $fillable = [
        'title',
        'description',
        'content',
        'video_url',
        'video_type',
        'thumbnail',
        'subject_id',
        'topic_id',
        'order',
        'duration_minutes',
        'is_free',
        'status',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'published_at' => 'datetime',
        'duration_minutes' => 'integer',
        'order' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserProgress::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class)
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('lesson_question.order');
    }

    public function userProgress(User $user)
    {
        return $this->progress()->where('user_id', $user->id)->first();
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('created_at');
    }

    // Helpers
    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at
            && $this->published_at->isPast();
    }

    public function canUserAccess(User $user): bool
    {
        if ($this->is_free) {
            return true;
        }

        // Check if user has active subscription
        return $user->hasActiveSubscription();
    }

    public function getVideoEmbedUrl(): ?string
    {
        if (!$this->video_url) {
            return null;
        }

        return match ($this->video_type) {
            'youtube' => $this->getYouTubeEmbedUrl(),
            'vimeo' => $this->getVimeoEmbedUrl(),
            default => $this->video_url,
        };
    }

    protected function getYouTubeEmbedUrl(): ?string
    {
        // Extract video ID from various YouTube URL formats
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\?\/]+)/', $this->video_url, $matches);

        if (isset($matches[1])) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }

        return $this->video_url;
    }

    protected function getVimeoEmbedUrl(): ?string
    {
        preg_match('/vimeo\.com\/(\d+)/', $this->video_url, $matches);

        if (isset($matches[1])) {
            return "https://player.vimeo.com/video/{$matches[1]}";
        }

        return $this->video_url;
    }
}
