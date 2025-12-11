<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'url',
        'thumbnail',
        'duration',
        'subject_id',
        'topic_id',
        'uploaded_by',
        'order',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'duration' => 'integer',
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

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function progress()
    {
        return $this->hasMany(VideoProgress::class);
    }

    public function userProgress()
    {
        return $this->hasOne(VideoProgress::class)->where('user_id', auth()->id());
    }
}
