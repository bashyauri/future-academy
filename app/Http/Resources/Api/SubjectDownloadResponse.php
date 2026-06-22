<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectDownloadResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'subject' => [
                'id' => $this->resource['subject']['id'],
                'name' => $this->resource['subject']['name'],
                'code' => $this->resource['subject']['code'],
                'slug' => $this->resource['subject']['slug'],
                'icon' => $this->resource['subject']['icon'],
                'color' => $this->resource['subject']['color'],
            ],
            'questions' => $this->resource['questions'],
            'pagination' => $this->resource['pagination'],
            'year_filter' => $this->resource['year_filter'],
        ];
    }
}
