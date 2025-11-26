<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    protected $fillable = [
        'question_id',
        'label',
        'option_text',
        'option_image',
        'is_correct',
        'sort_order',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    // Helper methods
    public function markAsCorrect(): void
    {
        $this->update(['is_correct' => true]);
    }

    public function markAsIncorrect(): void
    {
        $this->update(['is_correct' => false]);
    }
}
