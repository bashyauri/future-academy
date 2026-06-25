<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'quiz_id' => $this->resource->quiz_id,
            'subject_id' => $this->resource->subject_id,
            'exam_year' => $this->resource->exam_year,
            'status' => $this->resource->status,
            'score_percentage' => $this->resource->score_percentage,
            'passed' => $this->resource->passed,
            'time_taken_seconds' => $this->resource->time_taken_seconds,
            'total_questions' => $this->resource->total_questions,
            'correct_answers' => $this->resource->correct_answers,
            'started_at' => $this->resource->started_at?->toIso8601String(),
            'completed_at' => $this->resource->completed_at?->toIso8601String(),
        ];
    }
}
