<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoAnalytics extends Model
{
    protected $table = 'video_analytics';

    protected $fillable = [
        'lesson_id',
        'bunny_video_id',
        'total_views',
        'total_watch_time',
        'average_watch_time',
        'unique_viewers',
        'completion_rate',
        'average_bitrate',
        'top_country',
        'top_device',
        'last_synced_at',
    ];

    protected $casts = [
        'total_views' => 'integer',
        'total_watch_time' => 'integer',
        'average_watch_time' => 'float',
        'unique_viewers' => 'integer',
        'completion_rate' => 'float',
        'average_bitrate' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
