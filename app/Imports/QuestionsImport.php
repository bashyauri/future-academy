<?php

namespace App\Imports;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class QuestionsImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsOnError,
    SkipsOnFailure
{
    protected $defaultExamTypeId;
    protected $defaultSubjectId;
    protected $userId;
    protected $errors = [];
    protected $imported = 0;
    protected $skipped = 0;

    public function __construct($defaultExamTypeId = null, $defaultSubjectId = null, $userId = null)
    {
        $this->defaultExamTypeId = $defaultExamTypeId;
        $this->defaultSubjectId = $defaultSubjectId;
        $this->userId = $userId ?? \Illuminate\Support\Facades\Auth::id();
    }

    /**
     * Process each row chunk
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $this->processRow($row, $index);
            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $this->skipped++;
            }
        }
    }

    /**
     * Process a single row with validation and error handling
     */
    protected function processRow($row, $index)
    {
        // Clean and validate data
        $data = $this->cleanRowData($row);

        // Validate required fields
        if (empty($data['question_text']) || empty($data['option_a']) || empty($data['option_b'])) {
            throw new \Exception("Missing required fields (question, option_a, option_b)");
        }

        // Resolve Exam Type (accepts ID or name)
        $examTypeId = $this->resolveExamType($data['exam_type'] ?? $data['exam_type_id'] ?? null);
        if (!$examTypeId) {
            $examTypeId = $this->defaultExamTypeId;
        }
        if (!$examTypeId) {
            throw new \Exception("Invalid or missing exam_type (use name like 'WAEC' or ID)");
        }

        // Resolve Subject (accepts ID or name)
        $subjectId = $this->resolveSubject($data['subject'] ?? $data['subject_id'] ?? null);
        if (!$subjectId) {
            $subjectId = $this->defaultSubjectId;
        }
        if (!$subjectId) {
            throw new \Exception("Invalid or missing subject (use name like 'Mathematics' or ID)");
        }

        // Resolve Topic (accepts ID or name) - optional
        $topicId = $this->resolveTopic($data['topic'] ?? $data['topic_id'] ?? null, $subjectId);

        // Validate difficulty
        $difficulty = strtolower($data['difficulty'] ?? 'medium');
        if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
            $difficulty = 'medium';
        }

        // Validate correct answer
        $correctAnswer = strtoupper($data['correct_answer'] ?? 'A');
        if (!in_array($correctAnswer, ['A', 'B', 'C', 'D', 'E', 'F'])) {
            throw new \Exception("Invalid correct_answer: must be A, B, C, D, E, or F");
        }

        // Create question
        DB::transaction(function () use ($data, $examTypeId, $subjectId, $topicId, $difficulty, $correctAnswer) {
            $question = Question::create([
                'question_text' => $this->cleanText($data['question_text']),
                'explanation' => $this->cleanText($data['explanation'] ?? null),
                'exam_type_id' => $examTypeId,
                'subject_id' => $subjectId,
                'topic_id' => $topicId,
                'difficulty' => $difficulty,
                'year' => $data['year'] ?? null,
                'status' => 'pending',
                'created_by' => $this->userId,
                'is_active' => true,
            ]);

            // Create options (A-F)
            $options = [
                ['label' => 'A', 'text' => $data['option_a']],
                ['label' => 'B', 'text' => $data['option_b']],
                ['label' => 'C', 'text' => $data['option_c'] ?? null],
                ['label' => 'D', 'text' => $data['option_d'] ?? null],
                ['label' => 'E', 'text' => $data['option_e'] ?? null],
                ['label' => 'F', 'text' => $data['option_f'] ?? null],
            ];

            foreach ($options as $index => $option) {
                if (!empty($option['text'])) {
                    $question->options()->create([
                        'label' => $option['label'],
                        'option_text' => $this->cleanText($option['text']),
                        'is_correct' => $option['label'] === $correctAnswer,
                        'sort_order' => $index,
                    ]);
                }
            }

            $this->imported++;
        });
    }

    /**
     * Clean row data - handle encoding, trim, special characters
     */
    protected function cleanRowData($row): array
    {
        return [
            'question_text' => $row['question_text'] ?? $row['question'] ?? null,
            'option_a' => $row['option_a'] ?? null,
            'option_b' => $row['option_b'] ?? null,
            'option_c' => $row['option_c'] ?? null,
            'option_d' => $row['option_d'] ?? null,
            'option_e' => $row['option_e'] ?? null,
            'option_f' => $row['option_f'] ?? null,
            'correct_answer' => $row['correct_answer'] ?? $row['answer'] ?? null,
            'explanation' => $row['explanation'] ?? null,
            'difficulty' => $row['difficulty'] ?? null,
            'year' => $row['year'] ?? null,
            'exam_type' => $row['exam_type'] ?? $row['exam_type_name'] ?? null,
            'exam_type_id' => $row['exam_type_id'] ?? null,
            'subject' => $row['subject'] ?? $row['subject_name'] ?? null,
            'subject_id' => $row['subject_id'] ?? null,
            'topic' => $row['topic'] ?? $row['topic_name'] ?? null,
            'topic_id' => $row['topic_id'] ?? null,
        ];
    }

    /**
     * Resolve exam type from name or ID
     */
    protected function resolveExamType($value): ?int
    {
        if (empty($value)) {
            return null;
        }

        // If numeric, check if ID exists
        if (is_numeric($value)) {
            $examType = ExamType::find($value);
            return $examType ? $examType->id : null;
        }

        // Try to find by name (case-insensitive)
        $examType = ExamType::whereRaw('LOWER(name) = ?', [strtolower(trim($value))])->first();
        return $examType ? $examType->id : null;
    }

    /**
     * Resolve subject from name or ID
     */
    protected function resolveSubject($value): ?int
    {
        if (empty($value)) {
            return null;
        }

        // If numeric, check if ID exists
        if (is_numeric($value)) {
            $subject = Subject::find($value);
            return $subject ? $subject->id : null;
        }

        // Try to find by name (case-insensitive)
        $subject = Subject::whereRaw('LOWER(name) = ?', [strtolower(trim($value))])->first();
        return $subject ? $subject->id : null;
    }

    /**
     * Resolve topic from name or ID (scoped to subject)
     */
    protected function resolveTopic($value, $subjectId): ?int
    {
        if (empty($value)) {
            return null;
        }

        // If numeric, check if ID exists
        if (is_numeric($value)) {
            $topic = Topic::where('subject_id', $subjectId)->find($value);
            return $topic ? $topic->id : null;
        }

        // Try to find by name (case-insensitive, scoped to subject)
        $topic = Topic::where('subject_id', $subjectId)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($value))])
            ->first();
        return $topic ? $topic->id : null;
    }

    /**
     * Clean text - handle special characters, encoding, symbols
     */
    protected function cleanText(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }

        // Convert to UTF-8 if needed
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }

        // Trim whitespace
        $text = trim($text);

        // Preserve mathematical symbols: √, ∑, π, ≈, ≠, ≤, ≥, ∞, ∫, ∂, ∇
        // Preserve Nigerian currency: ₦
        // Preserve fractions: ½, ⅓, ¼, ⅔, ¾, ⅛
        // Preserve superscripts/subscripts: ², ³, ₁, ₂

        // Remove only dangerous characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Normalize quotes
        $text = str_replace([chr(0xE2) . chr(0x80) . chr(0x9C), chr(0xE2) . chr(0x80) . chr(0x9D)], '"', $text);
        $text = str_replace([chr(0xE2) . chr(0x80) . chr(0x98), chr(0xE2) . chr(0x80) . chr(0x99)], "'", $text);

        return $text;
    }

    /**
     * Handle errors gracefully
     */
    public function onError(Throwable $error)
    {
        $this->errors[] = $error->getMessage();
        $this->skipped++;
    }

    /**
     * Handle validation failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
            $this->skipped++;
        }
    }

    /**
     * Process in chunks for large files
     */
    public function chunkSize(): int
    {
        return 100; // Process 100 rows at a time
    }

    /**
     * Get import summary
     */
    public function getSummary(): array
    {
        return [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
