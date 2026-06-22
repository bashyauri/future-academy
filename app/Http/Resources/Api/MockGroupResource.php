<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MockGroupResource extends JsonResource
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
            'subject_id' => $this->resource->subject_id,
            'exam_type_id' => $this->resource->exam_type_id,
            'batch_number' => $this->resource->batch_number,
            'total_questions' => $this->resource->total_questions,
            'subject' => [
                'id' => $this->resource->subject->id,
                'name' => $this->resource->subject->name,
                'code' => $this->resource->subject->code,
                'slug' => $this->resource->subject->slug,
                'icon' => $this->resource->subject->icon,
                'color' => $this->resource->subject->color,
            ],
            'exam_type' => [
                'id' => $this->resource->examType->id,
                'name' => $this->resource->examType->name,
                'slug' => $this->resource->examType->slug,
            ],
        ];
    }
}
