<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MockSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'session_id' => $this->resource['session_id'] ?? null,
            'exam_type' => [
                'id' => $this->resource['exam_type']['id'],
                'name' => $this->resource['exam_type']['name'],
                'slug' => $this->resource['exam_type']['slug'],
            ],
            'subjects' => $this->resource['subjects'],
            'duration_minutes' => $this->resource['duration_minutes'],
            'total_questions' => $this->resource['total_questions'],
            'time_limit_per_subject' => $this->resource['time_limit_per_subject'],
            'created_at' => $this->resource['created_at'] ?? now()->toIso8601String(),
        ];
    }
}
