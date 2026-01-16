<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MockGroup extends Model
{
    protected $fillable = [
        'subject_id',
        'exam_type_id',
        'batch_number',
        'total_questions',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
