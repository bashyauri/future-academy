<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'code',
        'slug',
        'description',
        'icon',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Subject $subject) {
            if (empty($subject->slug)) {
                $subject->slug = Str::slug($subject->name);
            }
        });

        static::updating(function (Subject $subject) {
            if ($subject->isDirty('name') && empty($subject->slug)) {
                $subject->slug = Str::slug($subject->name);
            }
        });
    }

    public function examTypes(): BelongsToMany
    {
        return $this->belongsToMany(ExamType::class)
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function activeTopics(): HasMany
    {
        return $this->topics()->where('is_active', true);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)
            ->orderBy('order')
            ->orderBy('created_at');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)
            ->where('is_active', true)
            ->where('status', 'approved');
    }
}
