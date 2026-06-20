<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_text' => $this->question_text,
            'question_text_html' => $this->question_text_html,
            'question_image' => $this->question_image,
            'explanation' => $this->explanation,
            'explanation_html' => $this->explanation_html,
            'explanation_image' => $this->explanation_image,
            'subject_id' => $this->subject_id,
            'topic_id' => $this->topic_id,
            'exam_type_id' => $this->exam_type_id,
            'exam_year' => $this->exam_year,
            'year' => $this->year,
            'difficulty' => $this->difficulty,
            'is_mock' => $this->is_mock,
            'mock_group_id' => $this->mock_group_id,
            'options' => OptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
