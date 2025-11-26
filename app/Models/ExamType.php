<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ExamType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
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
        static::creating(function (ExamType $examType) {
            if (empty($examType->slug)) {
                $examType->slug = Str::slug($examType->name);
            }
            if (empty($examType->code)) {
                $examType->code = strtoupper(Str::limit($examType->slug, 10, ''));
            }
        });

        static::updating(function (ExamType $examType) {
            if ($examType->isDirty('name') && empty($examType->slug)) {
                $examType->slug = Str::slug($examType->name);
            }
        });
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class)
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
