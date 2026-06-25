<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
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
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'type' => $this->resource->type,
            'duration_minutes' => $this->resource->duration_minutes,
            'question_count' => $this->resource->question_count,
            'subject' => $this->resource->subject ? [
                'id' => $this->resource->subject->id,
                'name' => $this->resource->subject->name,
                'code' => $this->resource->subject->code,
            ] : null,
            'exam_type' => $this->resource->examType ? [
                'id' => $this->resource->examType->id,
                'name' => $this->resource->examType->name,
            ] : null,
        ];
    }
}
