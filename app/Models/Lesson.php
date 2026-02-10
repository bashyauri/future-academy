<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use App\Services\BunnyStreamService;

class Lesson extends Model
{
    protected $fillable = [
        'title',
        'description',
        'content',
        'video_url',
        'video_type',
        'video_status',
        'video_processed_at',
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
        'video_processed_at' => 'datetime',
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

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
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
            'bunny' => $this->getBunnyEmbedUrl(),
            'local' => $this->getSignedVideoUrl(),
            default => $this->video_url,
        };
    }

    /**
     * Get Bunny Stream embed URL (optionally signed).
     */
    public function getBunnyEmbedUrl(int $expirationMinutes = 1440): ?string
    {
        if ($this->video_type !== 'bunny' || !$this->video_url) {
            return null;
        }

        $service = app(BunnyStreamService::class);
        $expires = now()->addMinutes($expirationMinutes)->getTimestamp();

        return $service->getEmbedUrl($this->video_url, $expires);
    }

    /**
     * Get Bunny Stream thumbnail URL.
     */
    public function getBunnyThumbnailUrl(): ?string
    {
        if ($this->video_type !== 'bunny' || !$this->video_url) {
            return null;
        }

        $service = app(BunnyStreamService::class);
        return $service->getThumbnailUrl($this->video_url);
    }

    /**
     * Get Bunny Stream preview animation (WebP).
     */
    public function getBunnyPreviewAnimationUrl(): ?string
    {
        if ($this->video_type !== 'bunny' || !$this->video_url) {
            return null;
        }

        $service = app(BunnyStreamService::class);
        return $service->getPreviewAnimationUrl($this->video_url);
    }

    /**
     * Get signed URL for Cloudinary video with authenticated delivery.
     */
    public function getSignedVideoUrl(): ?string
    {
        if ($this->video_type !== 'local' || !$this->video_url) {
            return null;
        }

        $service = app(\App\Services\VideoSigningService::class);
        return $service->getSignedUrl($this->video_url);
    }

    /**
     * Get HLS streaming URL for adaptive quality playback.
     */
    public function getStreamingUrl(): ?string
    {
        if ($this->video_type !== 'local' || !$this->video_url) {
            return null;
        }

        $service = app(\App\Services\VideoSigningService::class);
        return $service->getHlsStreamingUrl($this->video_url);
    }

    /**
     * Check if video is ready for playback.
     */
    public function isVideoReady(): bool
    {
        return $this->video_type === 'local' && $this->video_status === 'ready';
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
        if (!$this->video_url) {
            return null;
        }

        preg_match('/vimeo\.com\/(\d+)/', $this->video_url, $matches);

        if (isset($matches[1])) {
            return "https://player.vimeo.com/video/{$matches[1]}";
        }

        return $this->video_url;
    }

    protected static function boot()
    {
        parent::boot();

        // When saving, check if video is being cleared and delete from Cloudinary
        static::saving(function ($lesson) {
            // Check if video_url was cleared (set to null/empty)
            if ($lesson->isDirty('video_url')) {
                $oldVideo = $lesson->getOriginal('video_url');
                $newVideo = $lesson->video_url;

                // If video was cleared (old has value, new is empty)
                if ($oldVideo && !$newVideo && $lesson->video_type === 'local') {
                    try {
                        $videoService = app(\App\Services\VideoSigningService::class);
                        $videoService->delete($oldVideo);

                        \Log::info('Video cleared and deleted from Cloudinary', [
                            'lesson_id' => $lesson->id,
                            'video_url' => $oldVideo,
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to delete cleared video from Cloudinary', [
                            'lesson_id' => $lesson->id,
                            'video_url' => $oldVideo,
                            'error' => $e->getMessage(),
                        ]);
                        // Don't fail the save if Cloudinary delete fails
                    }
                }
            }
        });

        // When deleting the entire lesson, remove video from Cloudinary too
        static::deleting(function ($lesson) {
            if ($lesson->video_type === 'local' && $lesson->video_url) {
                try {
                    $videoService = app(\App\Services\VideoSigningService::class);
                    $videoService->delete($lesson->video_url);
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete video from Cloudinary', [
                        'lesson_id' => $lesson->id,
                        'video_url' => $lesson->video_url,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail the deletion if Cloudinary delete fails
                }
            }
        });
    }
}

