<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Stream extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'default_subjects',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_subjects' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Stream $stream) {
            if (empty($stream->slug)) {
                $stream->slug = Str::slug($stream->name);
            }
        });

        static::updating(function (Stream $stream) {
            if ($stream->isDirty('name') && empty($stream->slug)) {
                $stream->slug = Str::slug($stream->name);
            }
        });
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'stream_subject')
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'stream', 'slug');
    }
}
