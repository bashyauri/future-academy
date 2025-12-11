<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProgress extends Model
{
    protected $table = 'video_progress';

    protected $fillable = [
        'user_id',
        'video_id',
        'watch_time',
        'percentage',
        'completed',
    ];

    protected $casts = [
        'watch_time' => 'integer',
        'percentage' => 'integer',
        'completed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Mark video as completed
     */
    public function markCompleted(): void
    {
        $this->update([
            'completed' => true,
            'percentage' => 100,
        ]);
    }

    /**
     * Update watch progress
     */
    public function updateProgress(int $watchTime, int $percentage): void
    {
        $this->update([
            'watch_time' => $watchTime,
            'percentage' => $percentage,
            'completed' => $percentage >= 90, // Consider 90% as completed
        ]);
    }
}
