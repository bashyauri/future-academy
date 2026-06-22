<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'message' => 'Sync completed successfully',
            'synced_attempts' => $this->resource['synced_attempts'] ?? 0,
            'synced_answers' => $this->resource['synced_answers'] ?? 0,
            'synced_lesson_progress' => $this->resource['synced_lesson_progress'] ?? 0,
            'failed_attempts' => $this->resource['failed_attempts'] ?? 0,
            'failed_answers' => $this->resource['failed_answers'] ?? 0,
            'failed_lesson_progress' => $this->resource['failed_lesson_progress'] ?? 0,
        ];
    }
}
