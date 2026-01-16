<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    protected $fillable = [
        'quiz_id',
        'user_id',
        'exam_type_id',
        'subject_id',
        'mock_group_id',
        'exam_year',
        'attempt_number',
        'started_at',
        'completed_at',
        'time_spent_seconds',
        'time_taken_seconds',
        'total_questions',
        'answered_questions',
        'correct_answers',
        'score',
        'percentage',
        'score_percentage',
        'passed',
        'status',
        'question_order',
        'current_question_index',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'passed' => 'boolean',
        'question_order' => 'array',
        'current_question_index' => 'integer',
    ];

    // Relationships
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('passed', false)->whereNotNull('completed_at');
    }

    // Helper methods
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function hasTimedOut(): bool
    {
        if (!$this->quiz->isTimed() || !$this->isInProgress()) {
            return false;
        }

        $elapsedMinutes = now()->diffInMinutes($this->started_at);
        return $elapsedMinutes >= $this->quiz->duration_minutes;
    }

    public function getRemainingSeconds(): ?int
    {
        if (!$this->quiz->isTimed() || !$this->isInProgress()) {
            return null;
        }

        $totalSeconds = $this->quiz->duration_minutes * 60;
        $elapsedSeconds = now()->diffInSeconds($this->started_at);
        $remaining = $totalSeconds - $elapsedSeconds;

        return max(0, $remaining);
    }

    public function complete(): void
    {
        $this->update([
            'completed_at' => now(),
            'time_spent_seconds' => now()->diffInSeconds($this->started_at),
            'status' => 'completed',
        ]);

        $this->calculateScore();
    }

    public function calculateScore(): void
    {
        $totalQuestions = $this->answers()->count();
        $correctAnswers = $this->answers()->where('is_correct', true)->count();
        $scorePercentage = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;

        $this->update([
            'total_questions' => $totalQuestions,
            'answered_questions' => $this->answers()->whereNotNull('option_id')->count(),
            'correct_answers' => $correctAnswers,
            'score_percentage' => round($scorePercentage, 2),
            'passed' => $scorePercentage >= $this->quiz->passing_score,
        ]);
    }

    public function getQuestionIds(): array
    {
        return $this->question_order ?? [];
    }
}
