<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class JambMathBatchSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $jambExam = ExamType::where('slug', 'jamb')->first();
        $subject = Subject::where('name', 'Mathematics')->first();

        if (!$admin || !$jambExam || !$subject) {
            $this->command->error('Required data not found.');
            return;
        }

        $questions = [
            ['text' => 'Solve: 2x + 5 = 15', 'options' => [['A', '3', false], ['B', '5', true], ['C', '7', false], ['D', '10', false]], 'explanation' => '2x = 10, x = 5', 'year' => 2022],
            ['text' => 'What is 25% of 80?', 'options' => [['A', '15', false], ['B', '20', true], ['C', '25', false], ['D', '30', false]], 'explanation' => '0.25 × 80 = 20', 'year' => 2022],
            ['text' => 'If 3x - 7 = 11, find x', 'options' => [['A', '4', false], ['B', '6', true], ['C', '8', false], ['D', '9', false]], 'explanation' => '3x = 18, x = 6', 'year' => 2022],
            ['text' => 'Find the area of a rectangle 5m by 8m', 'options' => [['A', '13 m²', false], ['B', '26 m²', false], ['C', '40 m²', true], ['D', '45 m²', false]], 'explanation' => 'Area = 5 × 8 = 40 m²', 'year' => 2022],
            ['text' => 'What is the square root of 169?', 'options' => [['A', '11', false], ['B', '12', false], ['C', '13', true], ['D', '14', false]], 'explanation' => '√169 = 13', 'year' => 2022],
            ['text' => 'Simplify: 3² + 4²', 'options' => [['A', '7', false], ['B', '14', false], ['C', '25', true], ['D', '49', false]], 'explanation' => '9 + 16 = 25', 'year' => 2022],
            ['text' => 'If y = 2x + 3, find y when x = 4', 'options' => [['A', '9', false], ['B', '10', false], ['C', '11', true], ['D', '12', false]], 'explanation' => 'y = 2(4) + 3 = 11', 'year' => 2022],
            ['text' => 'What is 0.75 as a fraction?', 'options' => [['A', '1/2', false], ['B', '2/3', false], ['C', '3/4', true], ['D', '4/5', false]], 'explanation' => '0.75 = 75/100 = 3/4', 'year' => 2022],
            ['text' => 'Find the perimeter of a square with side 6cm', 'options' => [['A', '12cm', false], ['B', '18cm', false], ['C', '24cm', true], ['D', '36cm', false]], 'explanation' => 'Perimeter = 4 × 6 = 24cm', 'year' => 2022],
            ['text' => 'Evaluate: 5 × (3 + 2)', 'options' => [['A', '10', false], ['B', '15', false], ['C', '20', false], ['D', '25', true]], 'explanation' => '5 × 5 = 25', 'year' => 2022],
            ['text' => 'What is 60% of 150?', 'options' => [['A', '80', false], ['B', '85', false], ['C', '90', true], ['D', '95', false]], 'explanation' => '0.6 × 150 = 90', 'year' => 2022],
            ['text' => 'Solve: x/4 = 12', 'options' => [['A', '3', false], ['B', '16', false], ['C', '48', true], ['D', '52', false]], 'explanation' => 'x = 12 × 4 = 48', 'year' => 2022],
            ['text' => 'Find the average of 10, 20, 30, 40', 'options' => [['A', '20', false], ['B', '25', true], ['C', '30', false], ['D', '35', false]], 'explanation' => '(10+20+30+40)/4 = 25', 'year' => 2022],
            ['text' => 'What is 2³?', 'options' => [['A', '6', false], ['B', '8', true], ['C', '9', false], ['D', '16', false]], 'explanation' => '2 × 2 × 2 = 8', 'year' => 2022],
            ['text' => 'If 5 + x = 12, find x', 'options' => [['A', '5', false], ['B', '6', false], ['C', '7', true], ['D', '8', false]], 'explanation' => 'x = 12 - 5 = 7', 'year' => 2022],
            ['text' => 'Calculate: 15% of 200', 'options' => [['A', '25', false], ['B', '30', true], ['C', '35', false], ['D', '40', false]], 'explanation' => '0.15 × 200 = 30', 'year' => 2022],
            ['text' => 'Solve: 4x = 36', 'options' => [['A', '8', false], ['B', '9', true], ['C', '10', false], ['D', '12', false]], 'explanation' => 'x = 36/4 = 9', 'year' => 2022],
            ['text' => 'What is 8 × 7?', 'options' => [['A', '54', false], ['B', '56', true], ['C', '58', false], ['D', '60', false]], 'explanation' => '8 × 7 = 56', 'year' => 2022],
            ['text' => 'Simplify: 6 + 3 × 2', 'options' => [['A', '10', false], ['B', '12', true], ['C', '14', false], ['D', '18', false]], 'explanation' => '6 + 6 = 12 (multiply first)', 'year' => 2022],
            ['text' => 'Find the LCM of 4 and 6', 'options' => [['A', '10', false], ['B', '12', true], ['C', '18', false], ['D', '24', false]], 'explanation' => 'LCM(4,6) = 12', 'year' => 2022],
            ['text' => 'What is 5² - 3²?', 'options' => [['A', '4', false], ['B', '8', false], ['C', '16', true], ['D', '20', false]], 'explanation' => '25 - 9 = 16', 'year' => 2023],
            ['text' => 'Solve: 7x - 3 = 18', 'options' => [['A', '2', false], ['B', '3', true], ['C', '4', false], ['D', '5', false]], 'explanation' => '7x = 21, x = 3', 'year' => 2023],
            ['text' => 'Convert 3/5 to decimal', 'options' => [['A', '0.4', false], ['B', '0.5', false], ['C', '0.6', true], ['D', '0.7', false]], 'explanation' => '3 ÷ 5 = 0.6', 'year' => 2023],
            ['text' => 'Find the sum: 1/2 + 1/4', 'options' => [['A', '2/6', false], ['B', '3/4', true], ['C', '1/6', false], ['D', '2/4', false]], 'explanation' => '2/4 + 1/4 = 3/4', 'year' => 2023],
            ['text' => 'What is 10% of 500?', 'options' => [['A', '5', false], ['B', '50', true], ['C', '100', false], ['D', '150', false]], 'explanation' => '0.1 × 500 = 50', 'year' => 2023],
            ['text' => 'Simplify: 12/16', 'options' => [['A', '2/3', false], ['B', '3/4', true], ['C', '4/5', false], ['D', '1/2', false]], 'explanation' => '12/16 = 3/4', 'year' => 2023],
            ['text' => 'Calculate: 9 × 8', 'options' => [['A', '70', false], ['B', '72', true], ['C', '74', false], ['D', '76', false]], 'explanation' => '9 × 8 = 72', 'year' => 2023],
            ['text' => 'Find x: x + 15 = 30', 'options' => [['A', '10', false], ['B', '15', true], ['C', '20', false], ['D', '25', false]], 'explanation' => 'x = 30 - 15 = 15', 'year' => 2023],
            ['text' => 'What is the HCF of 12 and 18?', 'options' => [['A', '3', false], ['B', '6', true], ['C', '9', false], ['D', '12', false]], 'explanation' => 'HCF(12,18) = 6', 'year' => 2023],
            ['text' => 'Evaluate: 100 ÷ 4', 'options' => [['A', '20', false], ['B', '25', true], ['C', '30', false], ['D', '35', false]], 'explanation' => '100 ÷ 4 = 25', 'year' => 2023],
            ['text' => 'Solve: 2(x + 3) = 14', 'options' => [['A', '3', false], ['B', '4', true], ['C', '5', false], ['D', '6', false]], 'explanation' => '2x + 6 = 14, 2x = 8, x = 4', 'year' => 2023],
            ['text' => 'What is 1/3 of 60?', 'options' => [['A', '15', false], ['B', '20', true], ['C', '25', false], ['D', '30', false]], 'explanation' => '60 ÷ 3 = 20', 'year' => 2023],
            ['text' => 'Calculate the circumference: radius = 7cm (π = 22/7)', 'options' => [['A', '22cm', false], ['B', '44cm', true], ['C', '88cm', false], ['D', '154cm', false]], 'explanation' => 'C = 2πr = 2 × 22/7 × 7 = 44cm', 'year' => 2023],
            ['text' => 'Find the volume of a cube with side 3cm', 'options' => [['A', '9 cm³', false], ['B', '18 cm³', false], ['C', '27 cm³', true], ['D', '36 cm³', false]], 'explanation' => 'Volume = 3³ = 27 cm³', 'year' => 2023],
            ['text' => 'Simplify: √64', 'options' => [['A', '6', false], ['B', '7', false], ['C', '8', true], ['D', '9', false]], 'explanation' => '√64 = 8', 'year' => 2023],
            ['text' => 'What is 40% of 250?', 'options' => [['A', '80', false], ['B', '90', false], ['C', '100', true], ['D', '110', false]], 'explanation' => '0.4 × 250 = 100', 'year' => 2023],
            ['text' => 'Calculate: (-3) × (-4)', 'options' => [['A', '-12', false], ['B', '-7', false], ['C', '7', false], ['D', '12', true]], 'explanation' => 'Negative × Negative = Positive', 'year' => 2023],
            ['text' => 'Find y: y - 8 = 15', 'options' => [['A', '7', false], ['B', '20', false], ['C', '23', true], ['D', '25', false]], 'explanation' => 'y = 15 + 8 = 23', 'year' => 2023],
            ['text' => 'What is 3/4 × 8?', 'options' => [['A', '4', false], ['B', '6', true], ['C', '8', false], ['D', '10', false]], 'explanation' => '(3 × 8)/4 = 24/4 = 6', 'year' => 2023],
            ['text' => 'Solve: 10 - 2x = 4', 'options' => [['A', '2', false], ['B', '3', true], ['C', '4', false], ['D', '5', false]], 'explanation' => '-2x = -6, x = 3', 'year' => 2023],
        ];

        $this->command->info('Seeding 40 Mathematics questions...');
        $this->createQuestions($questions, $subject, $jambExam, $admin);
        $this->command->info('Mathematics seeding complete!');
    }

    private function createQuestions(array $questions, $subject, $examType, $admin): void
    {
        foreach ($questions as $q) {
            $question = Question::create([
                'question_text' => $q['text'],
                'explanation' => $q['explanation'],
                'subject_id' => $subject->id,
                'exam_type_id' => $examType->id,
                'exam_year' => $q['year'],
                'difficulty' => 'medium',
                'status' => 'approved',
                'is_active' => true,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            foreach ($q['options'] as $index => $opt) {
                $question->options()->create([
                    'label' => $opt[0],
                    'option_text' => $opt[1],
                    'is_correct' => $opt[2],
                    'sort_order' => $index,
                ]);
            }
        }
    }
}
