<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockSession extends Model
{
    protected $fillable = [
        'user_id',
        'exam_type_id',
        'quiz_attempt_id',
        'mock_group_id',
        'subject_ids',
        'questions_per_subject',
        'option_order',
        'time_limit',
        'selected_year',
        'shuffle',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'subject_ids' => 'array',
        'questions_per_subject' => 'array',
        'option_order' => 'array',
        'shuffle' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function mockGroup(): BelongsTo
    {
        return $this->belongsTo(MockGroup::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
