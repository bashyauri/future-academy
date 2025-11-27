<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'type',
        'duration_minutes',
        'passing_score',
        'question_count',
        'subject_ids',
        'topic_ids',
        'exam_type_ids',
        'difficulty_levels',
        'years',
        'randomize_questions',
        'shuffle_questions',
        'shuffle_options',
        'show_answers_after_submit',
        'allow_review',
        'show_explanations',
        'max_attempts',
        'is_active',
        'status',
        'published_at',
        'available_from',
        'available_until',
        'created_by',
    ];

    protected $casts = [
        'subject_ids' => 'array',
        'topic_ids' => 'array',
        'exam_type_ids' => 'array',
        'difficulty_levels' => 'array',
        'years' => 'array',
        'randomize_questions' => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'show_answers_after_submit' => 'boolean',
        'allow_review' => 'boolean',
        'show_explanations' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class)
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('quiz_question.order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'published')->where(function ($q) {
            $q->whereNull('available_from')
                ->orWhere('available_from', '<=', now());
        })->where(function ($q) {
            $q->whereNull('available_until')
                ->orWhere('available_until', '>=', now());
        });
    }

    public function scopePractice($query)
    {
        return $query->where('type', 'practice');
    }

    public function scopeTimed($query)
    {
        return $query->where('type', 'timed');
    }

    public function scopeMock($query)
    {
        return $query->where('type', 'mock');
    }

    // Helper methods
    public function isAvailable(): bool
    {
        if (!$this->is_active || $this->status !== 'published') {
            return false;
        }

        $now = now();

        if ($this->available_from && $now->isBefore($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->isAfter($this->available_until)) {
            return false;
        }

        return true;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isTimed(): bool
    {
        return $this->type === 'timed';
    }

    public function canUserAttempt(User $user): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        if ($this->max_attempts === null) {
            return true;
        }

        $userAttempts = $this->attempts()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        return $userAttempts < $this->max_attempts;
    }

    public function getNextAttemptNumber(User $user): int
    {
        return $this->attempts()
            ->where('user_id', $user->id)
            ->max('attempt_number') + 1;
    }
}
