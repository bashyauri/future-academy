<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            // Core/Compulsory
            ['name' => 'English Language', 'code' => 'ENG', 'icon' => '📚', 'color' => '#EF4444'],
            ['name' => 'Mathematics', 'code' => 'MATH', 'icon' => '🔢', 'color' => '#3B82F6'],

            // Sciences
            ['name' => 'Physics', 'code' => 'PHY', 'icon' => '⚛️', 'color' => '#8B5CF6'],
            ['name' => 'Chemistry', 'code' => 'CHEM', 'icon' => '🧪', 'color' => '#10B981'],
            ['name' => 'Biology', 'code' => 'BIO', 'icon' => '🧬', 'color' => '#14B8A6'],
            ['name' => 'Agricultural Science', 'code' => 'AGRIC', 'icon' => '🌾', 'color' => '#84CC16'],

            // Arts & Humanities
            ['name' => 'Literature in English', 'code' => 'LIT', 'icon' => '📖', 'color' => '#F59E0B'],
            ['name' => 'Government', 'code' => 'GOVT', 'icon' => '🏛️', 'color' => '#6366F1'],
            ['name' => 'History', 'code' => 'HIST', 'icon' => '📜', 'color' => '#8B5CF6'],
            ['name' => 'CRK', 'code' => 'CRK', 'icon' => '✝️', 'color' => '#EC4899'],
            ['name' => 'IRK', 'code' => 'IRK', 'icon' => '☪️', 'color' => '#10B981'],
            ['name' => 'French', 'code' => 'FRE', 'icon' => '🇫🇷', 'color' => '#3B82F6'],

            // Social Sciences
            ['name' => 'Economics', 'code' => 'ECON', 'icon' => '💰', 'color' => '#F59E0B'],
            ['name' => 'Commerce', 'code' => 'COMM', 'icon' => '🏪', 'color' => '#06B6D4'],
            ['name' => 'Accounting', 'code' => 'ACCT', 'icon' => '📊', 'color' => '#8B5CF6'],
            ['name' => 'Geography', 'code' => 'GEO', 'icon' => '🌍', 'color' => '#14B8A6'],

            // Technical
            ['name' => 'Further Mathematics', 'code' => 'FMATH', 'icon' => '➗', 'color' => '#6366F1'],
            ['name' => 'Computer Studies', 'code' => 'COMP', 'icon' => '💻', 'color' => '#3B82F6'],

            // Languages
            ['name' => 'Yoruba', 'code' => 'YOR', 'icon' => '🗣️', 'color' => '#EF4444'],
            ['name' => 'Igbo', 'code' => 'IGB', 'icon' => '🗣️', 'color' => '#10B981'],
            ['name' => 'Hausa', 'code' => 'HAU', 'icon' => '🗣️', 'color' => '#3B82F6'],
        ];

        foreach ($subjects as $index => $subjectData) {
            $subject = Subject::updateOrCreate(
                ['code' => $subjectData['code']],
                [
                    'name' => $subjectData['name'],
                    'slug' => Str::slug($subjectData['name']),
                    'icon' => $subjectData['icon'],
                    'color' => $subjectData['color'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );

            // Attach to all exam types
            $examTypes = ExamType::all();
            if ($examTypes->isNotEmpty()) {
                $subject->examTypes()->syncWithoutDetaching($examTypes->pluck('id')->toArray());
            }
        }
    }
}
