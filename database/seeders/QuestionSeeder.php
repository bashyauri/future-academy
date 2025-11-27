<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();

        if (!$admin) {
            $this->command->error('No admin user found. Run RolePermissionSeeder first.');
            return;
        }

        // Get references
        $mathSubject = Subject::where('name', 'Mathematics')->first();
        $englishSubject = Subject::where('name', 'English Language')->first();
        $physicsSubject = Subject::where('name', 'Physics')->first();
        $chemistrySubject = Subject::where('name', 'Chemistry')->first();
        $biologySubject = Subject::where('name', 'Biology')->first();

        $jambExam = ExamType::where('name', 'JAMB UTME')->first();
        $waecExam = ExamType::where('name', 'WAEC')->first();

        if (!$mathSubject || !$jambExam) {
            $this->command->error('Required subjects or exam types not found. Run SubjectTopicSeeder first.');
            return;
        }

        $this->command->info('Seeding Mathematics questions...');
        $this->seedMathQuestions($mathSubject, $jambExam, $admin);

        $this->command->info('Seeding English questions...');
        $this->seedEnglishQuestions($englishSubject, $jambExam, $admin);

        $this->command->info('Seeding Physics questions...');
        $this->seedPhysicsQuestions($physicsSubject, $jambExam, $admin);

        $this->command->info('Seeding Chemistry questions...');
        $this->seedChemistryQuestions($chemistrySubject, $jambExam, $admin);

        $this->command->info('Seeding Biology questions...');
        $this->seedBiologyQuestions($biologySubject, $jambExam, $admin);

        $this->command->info('Questions seeded successfully!');
    }

    protected function seedMathQuestions($subject, $examType, $admin)
    {
        $questions = [
            [
                'text' => 'What is the value of x in the equation 2x + 5 = 15?',
                'options' => [
                    ['label' => 'A', 'text' => '3', 'correct' => false],
                    ['label' => 'B', 'text' => '5', 'correct' => true],
                    ['label' => 'C', 'text' => '7', 'correct' => false],
                    ['label' => 'D', 'text' => '10', 'correct' => false],
                ],
                'explanation' => '2x + 5 = 15. Subtract 5 from both sides: 2x = 10. Divide by 2: x = 5.',
                'difficulty' => 'easy',
            ],
            [
                'text' => 'Simplify: 3(2x - 4) + 2(x + 5)',
                'options' => [
                    ['label' => 'A', 'text' => '8x - 2', 'correct' => true],
                    ['label' => 'B', 'text' => '8x + 2', 'correct' => false],
                    ['label' => 'C', 'text' => '6x - 2', 'correct' => false],
                    ['label' => 'D', 'text' => '6x + 2', 'correct' => false],
                ],
                'explanation' => '3(2x - 4) + 2(x + 5) = 6x - 12 + 2x + 10 = 8x - 2',
                'difficulty' => 'medium',
            ],
            [
                'text' => 'If the angles of a triangle are in the ratio 2:3:4, find the largest angle.',
                'options' => [
                    ['label' => 'A', 'text' => '60°', 'correct' => false],
                    ['label' => 'B', 'text' => '70°', 'correct' => false],
                    ['label' => 'C', 'text' => '80°', 'correct' => true],
                    ['label' => 'D', 'text' => '90°', 'correct' => false],
                ],
                'explanation' => 'Sum of ratios: 2+3+4 = 9. Total angles = 180°. Largest angle: (4/9) × 180° = 80°',
                'difficulty' => 'medium',
            ],
            [
                'text' => 'What is the square root of 144?',
                'options' => [
                    ['label' => 'A', 'text' => '10', 'correct' => false],
                    ['label' => 'B', 'text' => '11', 'correct' => false],
                    ['label' => 'C', 'text' => '12', 'correct' => true],
                    ['label' => 'D', 'text' => '13', 'correct' => false],
                ],
                'explanation' => '√144 = 12 because 12 × 12 = 144',
                'difficulty' => 'easy',
            ],
            [
                'text' => 'Find the value of y if 3y/4 = 12',
                'options' => [
                    ['label' => 'A', 'text' => '9', 'correct' => false],
                    ['label' => 'B', 'text' => '12', 'correct' => false],
                    ['label' => 'C', 'text' => '16', 'correct' => true],
                    ['label' => 'D', 'text' => '18', 'correct' => false],
                ],
                'explanation' => '3y/4 = 12. Multiply both sides by 4: 3y = 48. Divide by 3: y = 16',
                'difficulty' => 'easy',
            ],
        ];

        $this->createQuestions($questions, $subject, $examType, $admin);
    }

    protected function seedEnglishQuestions($subject, $examType, $admin)
    {
        $questions = [
            [
                'text' => 'Choose the word that is opposite in meaning to "generous".',
                'options' => [
                    ['label' => 'A', 'text' => 'Kind', 'correct' => false],
                    ['label' => 'B', 'text' => 'Selfish', 'correct' => true],
                    ['label' => 'C', 'text' => 'Wealthy', 'correct' => false],
                    ['label' => 'D', 'text' => 'Happy', 'correct' => false],
                ],
                'explanation' => 'Selfish is the opposite of generous, meaning unwilling to share.',
                'difficulty' => 'easy',
            ],
            [
                'text' => 'Identify the correct sentence.',
                'options' => [
                    ['label' => 'A', 'text' => 'She don\'t like apples.', 'correct' => false],
                    ['label' => 'B', 'text' => 'She doesn\'t likes apples.', 'correct' => false],
                    ['label' => 'C', 'text' => 'She doesn\'t like apples.', 'correct' => true],
                    ['label' => 'D', 'text' => 'She not like apples.', 'correct' => false],
                ],
                'explanation' => 'The correct form uses "doesn\'t" (does not) with the base verb "like".',
                'difficulty' => 'easy',
            ],
            [
                'text' => 'Choose the word that best completes the sentence: "The weather was _____ cold that we stayed indoors."',
                'options' => [
                    ['label' => 'A', 'text' => 'very', 'correct' => false],
                    ['label' => 'B', 'text' => 'so', 'correct' => true],
                    ['label' => 'C', 'text' => 'too', 'correct' => false],
                    ['label' => 'D', 'text' => 'much', 'correct' => false],
                ],
                'explanation' => '"So...that" is the correct structure to show result or consequence.',
                'difficulty' => 'medium',
            ],
            [
                'text' => 'What is the plural of "child"?',
                'options' => [
                    ['label' => 'A', 'text' => 'childs', 'correct' => false],
                    ['label' => 'B', 'text' => 'childes', 'correct' => false],
                    ['label' => 'C', 'text' => 'children', 'correct' => true],
                    ['label' => 'D', 'text' => 'childrens', 'correct' => false],
                ],
                'explanation' => '"Children" is the irregular plural form of "child".',
                'difficulty' => 'easy',
            ],
            [
                'text' => 'Choose the correct punctuation: "Its a beautiful day"',
                'options' => [
                    ['label' => 'A', 'text' => 'Its a beautiful day', 'correct' => false],
                    ['label' => 'B', 'text' => 'It\'s a beautiful day', 'correct' => true],
                    ['label' => 'C', 'text' => 'Its\' a beautiful day', 'correct' => false],
                    ['label' => 'D', 'text' => 'It\'s a beautiful day!', 'correct' => false],
                ],
                'explanation' => '"It\'s" is the contraction of "it is" and requires an apostrophe.',
                'difficulty' => 'easy',
            ],
        ];

        $this->createQuestions($questions, $subject, $examType, $admin);
    }

    protected function seedPhysicsQuestions($subject, $examType, $admin)
    {
        $questions = [
            [
                'text' => 'What is the SI unit of force?',
                'options' => [
                    ['label' => 'A', 'text' => 'Joule', 'correct' => false],
                    ['label' => 'B', 'text' => 'Newton', 'correct' => true],
                    ['label' => 'C', 'text' => 'Watt', 'correct' => false],
                    ['label' => 'D', 'text' => 'Pascal', 'correct' => false],
                ],
                'explanation' => 'The Newton (N) is the SI unit of force, named after Isaac Newton.',
                'difficulty' => 'easy',
            ],
            [
                'text' => 'A body of mass 5kg is accelerated at 2m/s². What is the force applied?',
                'options' => [
                    ['label' => 'A', 'text' => '7N', 'correct' => false],
                    ['label' => 'B', 'text' => '10N', 'correct' => true],
                    ['label' => 'C', 'text' => '2.5N', 'correct' => false],
                    ['label' => 'D', 'text' => '3N', 'correct' => false],
                ],
                'explanation' => 'Force = mass × acceleration = 5kg × 2m/s² = 10N',
                'difficulty' => 'medium',
            ],
            [
                'text' => 'Which of the following is a vector quantity?',
                'options' => [
                    ['label' => 'A', 'text' => 'Speed', 'correct' => false],
                    ['label' => 'B', 'text' => 'Mass', 'correct' => false],
                    ['label' => 'C', 'text' => 'Velocity', 'correct' => true],
                    ['label' => 'D', 'text' => 'Temperature', 'correct' => false],
                ],
                'explanation' => 'Velocity is a vector quantity as it has both magnitude and direction.',
                'difficulty' => 'easy',
            ],
        ];

        $this->createQuestions($questions, $subject, $examType, $admin);
    }

    protected function seedChemistryQuestions($subject, $examType, $admin)
    {
        $questions = [
            [
                'text' => 'What is the chemical symbol for water?',
                'options' => [
                    ['label' => 'A', 'text' => 'H2O', 'correct' => true],
                    ['label' => 'B', 'text' => 'CO2', 'correct' => false],
                    ['label' => 'C', 'text' => 'O2', 'correct' => false],
                    ['label' => 'D', 'text' => 'H2O2', 'correct' => false],
                ],
                'explanation' => 'Water consists of two hydrogen atoms and one oxygen atom: H2O',
                'difficulty' => 'easy',
            ],
            [
                'text' => 'What is the atomic number of Carbon?',
                'options' => [
                    ['label' => 'A', 'text' => '4', 'correct' => false],
                    ['label' => 'B', 'text' => '6', 'correct' => true],
                    ['label' => 'C', 'text' => '8', 'correct' => false],
                    ['label' => 'D', 'text' => '12', 'correct' => false],
                ],
                'explanation' => 'Carbon has 6 protons, giving it an atomic number of 6.',
                'difficulty' => 'easy',
            ],
            [
                'text' => 'Which of the following is an acid?',
                'options' => [
                    ['label' => 'A', 'text' => 'NaOH', 'correct' => false],
                    ['label' => 'B', 'text' => 'HCl', 'correct' => true],
                    ['label' => 'C', 'text' => 'NaCl', 'correct' => false],
                    ['label' => 'D', 'text' => 'KOH', 'correct' => false],
                ],
                'explanation' => 'HCl (Hydrochloric acid) is a strong acid.',
                'difficulty' => 'easy',
            ],
        ];

        $this->createQuestions($questions, $subject, $examType, $admin);
    }

    protected function seedBiologyQuestions($subject, $examType, $admin)
    {
        $questions = [
            [
                'text' => 'What is the powerhouse of the cell?',
                'options' => [
                    ['label' => 'A', 'text' => 'Nucleus', 'correct' => false],
                    ['label' => 'B', 'text' => 'Mitochondria', 'correct' => true],
                    ['label' => 'C', 'text' => 'Ribosome', 'correct' => false],
                    ['label' => 'D', 'text' => 'Chloroplast', 'correct' => false],
                ],
                'explanation' => 'Mitochondria produce energy (ATP) for the cell through cellular respiration.',
                'difficulty' => 'easy',
            ],
            [
                'text' => 'Which organ is responsible for pumping blood throughout the body?',
                'options' => [
                    ['label' => 'A', 'text' => 'Lungs', 'correct' => false],
                    ['label' => 'B', 'text' => 'Liver', 'correct' => false],
                    ['label' => 'C', 'text' => 'Heart', 'correct' => true],
                    ['label' => 'D', 'text' => 'Kidney', 'correct' => false],
                ],
                'explanation' => 'The heart pumps blood to all parts of the body through the circulatory system.',
                'difficulty' => 'easy',
            ],
            [
                'text' => 'What is the process by which plants make their own food?',
                'options' => [
                    ['label' => 'A', 'text' => 'Respiration', 'correct' => false],
                    ['label' => 'B', 'text' => 'Digestion', 'correct' => false],
                    ['label' => 'C', 'text' => 'Photosynthesis', 'correct' => true],
                    ['label' => 'D', 'text' => 'Transpiration', 'correct' => false],
                ],
                'explanation' => 'Photosynthesis is the process where plants use sunlight, water, and CO2 to produce glucose.',
                'difficulty' => 'easy',
            ],
        ];

        $this->createQuestions($questions, $subject, $examType, $admin);
    }

    protected function createQuestions(array $questions, $subject, $examType, $admin)
    {
        foreach ($questions as $questionData) {
            $question = Question::create([
                'question_text' => $questionData['text'],
                'explanation' => $questionData['explanation'],
                'subject_id' => $subject->id,
                'exam_type_id' => $examType->id,
                'difficulty' => $questionData['difficulty'],
                'year' => 2024,
                'status' => 'approved',
                'is_active' => true,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            foreach ($questionData['options'] as $index => $optionData) {
                $question->options()->create([
                    'label' => $optionData['label'],
                    'option_text' => $optionData['text'],
                    'is_correct' => $optionData['correct'],
                    'sort_order' => $index,
                ]);
            }
        }
    }
}
