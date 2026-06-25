<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
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
            'video_url' => $this->resource->video_url,
            'duration_seconds' => $this->resource->duration_seconds,
            'thumbnail_url' => $this->resource->thumbnail_url,
            'order' => $this->resource->order,
            'subject' => $this->resource->subject ? [
                'id' => $this->resource->subject->id,
                'name' => $this->resource->subject->name,
                'code' => $this->resource->subject->code,
                'slug' => $this->resource->subject->slug,
            ] : null,
        ];
    }
}
