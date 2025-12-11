<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionSeeder_New extends Seeder
{
    public function run(): void
    {
        if (Question::count() > 100) {
            $this->command->info('Sufficient questions already exist.');
            return;
        }

        $admin = User::whereAccountType('admin')->first();
        if (!$admin) {
            $this->command->error('No admin user found.');
            return;
        }

        $jamb = ExamType::where('slug', 'jamb')->first();
        $neco = ExamType::where('slug', 'neco')->first();
        $waec = ExamType::where('slug', 'waec')->first();

        // ENGLISH LANGUAGE Questions
        $english = Subject::where('slug', 'english-language')->first();
        if ($english) {
            $this->createEnglishQuestions($admin, $jamb, $english);
        }

        // MATHEMATICS Questions
        $math = Subject::where('slug', 'mathematics')->first();
        if ($math) {
            $this->createMathematicsQuestions($admin, $jamb, $math);
        }

        // BIOLOGY Questions
        $biology = Subject::where('slug', 'biology')->first();
        if ($biology) {
            $this->createBiologyQuestions($admin, $jamb, $biology);
        }

        // CHEMISTRY Questions
        $chemistry = Subject::where('slug', 'chemistry')->first();
        if ($chemistry) {
            $this->createChemistryQuestions($admin, $jamb, $chemistry);
        }

        // PHYSICS Questions
        $physics = Subject::where('slug', 'physics')->first();
        if ($physics) {
            $this->createPhysicsQuestions($admin, $jamb, $physics);
        }
    }

    private function createEnglishQuestions($admin, $examType, $subject)
    {
        $questions = [
            [
                'question' => 'Which of the following is a synonym for "perspicacious"?',
                'explanation' => 'Perspicacious means having keen insight or discernment. The synonym is "discerning" or "astute".',
                'options' => [
                    ['text' => 'Shrewd and astute', 'correct' => true],
                    ['text' => 'Stubborn and rigid', 'correct' => false],
                    ['text' => 'Slow and lazy', 'correct' => false],
                    ['text' => 'Angry and irritable', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'hard',
            ],
            [
                'question' => 'The phrase "a sea of troubles" is an example of:',
                'explanation' => 'A "sea of troubles" is a metaphor that compares troubles to a vast sea, showing the overwhelming nature of problems.',
                'options' => [
                    ['text' => 'Metaphor', 'correct' => true],
                    ['text' => 'Simile', 'correct' => false],
                    ['text' => 'Alliteration', 'correct' => false],
                    ['text' => 'Irony', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'medium',
            ],
            [
                'question' => 'Which of these is NOT a verb form?',
                'explanation' => 'The gerund form (ending in -ing) is a noun, not a verb. "Running", "jumping" are gerunds acting as nouns.',
                'options' => [
                    ['text' => 'Running', 'correct' => true],
                    ['text' => 'Runs', 'correct' => false],
                    ['text' => 'Run', 'correct' => false],
                    ['text' => 'Running away', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'medium',
            ],
            [
                'question' => 'The sentence "She walks to the store" is in which tense?',
                'explanation' => 'This sentence uses the simple present tense (she + walks).',
                'options' => [
                    ['text' => 'Simple Present', 'correct' => true],
                    ['text' => 'Simple Past', 'correct' => false],
                    ['text' => 'Present Continuous', 'correct' => false],
                    ['text' => 'Simple Future', 'correct' => false],
                ],
                'year' => 2022,
                'difficulty' => 'easy',
            ],
            [
                'question' => 'Which word is most nearly opposite in meaning to "benevolent"?',
                'explanation' => 'Benevolent means kind and generous. The opposite would be malevolent (evil) or cruel.',
                'options' => [
                    ['text' => 'Malevolent', 'correct' => true],
                    ['text' => 'Benign', 'correct' => false],
                    ['text' => 'Neutral', 'correct' => false],
                    ['text' => 'Indifferent', 'correct' => false],
                ],
                'year' => 2022,
                'difficulty' => 'hard',
            ],
        ];

        foreach ($questions as $q) {
            $this->createQuestion($admin, $examType, $subject, $q);
        }
    }

    private function createMathematicsQuestions($admin, $examType, $subject)
    {
        $questions = [
            [
                'question' => 'Solve for x: 2x + 5 = 13',
                'explanation' => 'Subtract 5 from both sides: 2x = 8. Divide by 2: x = 4',
                'options' => [
                    ['text' => '4', 'correct' => true],
                    ['text' => '5', 'correct' => false],
                    ['text' => '6', 'correct' => false],
                    ['text' => '9', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'easy',
            ],
            [
                'question' => 'What is the value of √144?',
                'explanation' => 'The square root of 144 is 12, since 12 × 12 = 144',
                'options' => [
                    ['text' => '12', 'correct' => true],
                    ['text' => '11', 'correct' => false],
                    ['text' => '13', 'correct' => false],
                    ['text' => '14', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'easy',
            ],
            [
                'question' => 'Find the area of a circle with radius 5cm. (Use π = 3.14)',
                'explanation' => 'Area = πr² = 3.14 × 5² = 3.14 × 25 = 78.5 cm²',
                'options' => [
                    ['text' => '78.5 cm²', 'correct' => true],
                    ['text' => '31.4 cm²', 'correct' => false],
                    ['text' => '157 cm²', 'correct' => false],
                    ['text' => '15.7 cm²', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'medium',
            ],
            [
                'question' => 'If 3x = 27, what is x?',
                'explanation' => '3x = 27 means 3 to the power of x equals 27. Since 3³ = 27, x = 3',
                'options' => [
                    ['text' => '3', 'correct' => true],
                    ['text' => '9', 'correct' => false],
                    ['text' => '8', 'correct' => false],
                    ['text' => '6', 'correct' => false],
                ],
                'year' => 2022,
                'difficulty' => 'medium',
            ],
            [
                'question' => 'What is the slope of the line passing through (2,3) and (4,7)?',
                'explanation' => 'Slope = (y₂ - y₁)/(x₂ - x₁) = (7-3)/(4-2) = 4/2 = 2',
                'options' => [
                    ['text' => '2', 'correct' => true],
                    ['text' => '1', 'correct' => false],
                    ['text' => '3', 'correct' => false],
                    ['text' => '2.5', 'correct' => false],
                ],
                'year' => 2022,
                'difficulty' => 'hard',
            ],
        ];

        foreach ($questions as $q) {
            $this->createQuestion($admin, $examType, $subject, $q);
        }
    }

    private function createBiologyQuestions($admin, $examType, $subject)
    {
        $questions = [
            [
                'question' => 'Which organelle is responsible for energy production in a cell?',
                'explanation' => 'The mitochondrion (plural: mitochondria) is known as the "powerhouse of the cell" because it produces ATP through cellular respiration.',
                'options' => [
                    ['text' => 'Mitochondrion', 'correct' => true],
                    ['text' => 'Nucleus', 'correct' => false],
                    ['text' => 'Ribosome', 'correct' => false],
                    ['text' => 'Endoplasmic Reticulum', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'easy',
            ],
            [
                'question' => 'What is the process by which plants make their own food?',
                'explanation' => 'Photosynthesis is the process where plants convert sunlight into chemical energy, producing glucose and oxygen.',
                'options' => [
                    ['text' => 'Photosynthesis', 'correct' => true],
                    ['text' => 'Respiration', 'correct' => false],
                    ['text' => 'Fermentation', 'correct' => false],
                    ['text' => 'Digestion', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'easy',
            ],
            [
                'question' => 'Which blood type is known as the universal donor?',
                'explanation' => 'Type O negative blood can be given to anyone regardless of their blood type, making it the universal donor.',
                'options' => [
                    ['text' => 'O negative', 'correct' => true],
                    ['text' => 'O positive', 'correct' => false],
                    ['text' => 'AB negative', 'correct' => false],
                    ['text' => 'A positive', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'medium',
            ],
            [
                'question' => 'What is the main function of the ribosome?',
                'explanation' => 'Ribosomes are sites of protein synthesis where amino acids are assembled into proteins according to mRNA instructions.',
                'options' => [
                    ['text' => 'Protein synthesis', 'correct' => true],
                    ['text' => 'Energy production', 'correct' => false],
                    ['text' => 'Photosynthesis', 'correct' => false],
                    ['text' => 'DNA replication', 'correct' => false],
                ],
                'year' => 2022,
                'difficulty' => 'medium',
            ],
            [
                'question' => 'During which phase of mitosis do chromosomes line up at the cell\'s equator?',
                'explanation' => 'During metaphase, chromosomes align at the metaphase plate (cell equator) before being separated in anaphase.',
                'options' => [
                    ['text' => 'Metaphase', 'correct' => true],
                    ['text' => 'Prophase', 'correct' => false],
                    ['text' => 'Anaphase', 'correct' => false],
                    ['text' => 'Telophase', 'correct' => false],
                ],
                'year' => 2022,
                'difficulty' => 'hard',
            ],
        ];

        foreach ($questions as $q) {
            $this->createQuestion($admin, $examType, $subject, $q);
        }
    }

    private function createChemistryQuestions($admin, $examType, $subject)
    {
        $questions = [
            [
                'question' => 'What is the chemical formula for table salt?',
                'explanation' => 'Table salt is sodium chloride, composed of sodium (Na) and chlorine (Cl) atoms in a 1:1 ratio.',
                'options' => [
                    ['text' => 'NaCl', 'correct' => true],
                    ['text' => 'CaCl₂', 'correct' => false],
                    ['text' => 'KCl', 'correct' => false],
                    ['text' => 'MgCl₂', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'easy',
            ],
            [
                'question' => 'What is the pH of a neutral solution?',
                'explanation' => 'A neutral solution has a pH of 7. Below 7 is acidic, above 7 is basic/alkaline.',
                'options' => [
                    ['text' => '7', 'correct' => true],
                    ['text' => '5', 'correct' => false],
                    ['text' => '9', 'correct' => false],
                    ['text' => '0', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'easy',
            ],
            [
                'question' => 'Which of the following is a noble gas?',
                'explanation' => 'Noble gases (Group 18) are chemically inert. Neon is a noble gas; the others are not.',
                'options' => [
                    ['text' => 'Neon (Ne)', 'correct' => true],
                    ['text' => 'Nitrogen (N)', 'correct' => false],
                    ['text' => 'Sodium (Na)', 'correct' => false],
                    ['text' => 'Nickel (Ni)', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'medium',
            ],
            [
                'question' => 'What type of bond exists between H and Cl in HCl?',
                'explanation' => 'HCl has a polar covalent bond where electrons are unequally shared between hydrogen and chlorine atoms.',
                'options' => [
                    ['text' => 'Polar covalent', 'correct' => true],
                    ['text' => 'Ionic', 'correct' => false],
                    ['text' => 'Nonpolar covalent', 'correct' => false],
                    ['text' => 'Metallic', 'correct' => false],
                ],
                'year' => 2022,
                'difficulty' => 'hard',
            ],
            [
                'question' => 'What is the oxidation state of oxygen in H₂O₂?',
                'explanation' => 'In hydrogen peroxide, oxygen has an oxidation state of -1 (not the typical -2).',
                'options' => [
                    ['text' => '-1', 'correct' => true],
                    ['text' => '-2', 'correct' => false],
                    ['text' => '+2', 'correct' => false],
                    ['text' => '0', 'correct' => false],
                ],
                'year' => 2022,
                'difficulty' => 'hard',
            ],
        ];

        foreach ($questions as $q) {
            $this->createQuestion($admin, $examType, $subject, $q);
        }
    }

    private function createPhysicsQuestions($admin, $examType, $subject)
    {
        $questions = [
            [
                'question' => 'What is the SI unit of force?',
                'explanation' => 'The Newton (N) is the SI unit of force. 1 Newton = 1 kg·m/s²',
                'options' => [
                    ['text' => 'Newton', 'correct' => true],
                    ['text' => 'Pascal', 'correct' => false],
                    ['text' => 'Joule', 'correct' => false],
                    ['text' => 'Watt', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'easy',
            ],
            [
                'question' => 'What is the speed of light in vacuum?',
                'explanation' => 'The speed of light is approximately 3 × 10⁸ m/s or 299,792,458 m/s.',
                'options' => [
                    ['text' => '3 × 10⁸ m/s', 'correct' => true],
                    ['text' => '3 × 10⁶ m/s', 'correct' => false],
                    ['text' => '3 × 10¹⁰ m/s', 'correct' => false],
                    ['text' => '3 × 10⁴ m/s', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'medium',
            ],
            [
                'question' => 'Which of Newton\'s laws states that every action has an equal and opposite reaction?',
                'explanation' => 'Newton\'s Third Law of Motion describes action-reaction pairs.',
                'options' => [
                    ['text' => 'Third Law', 'correct' => true],
                    ['text' => 'First Law', 'correct' => false],
                    ['text' => 'Second Law', 'correct' => false],
                    ['text' => 'Law of Gravity', 'correct' => false],
                ],
                'year' => 2023,
                'difficulty' => 'easy',
            ],
            [
                'question' => 'What is the formula for kinetic energy?',
                'explanation' => 'Kinetic energy is the energy of motion. KE = ½mv² where m is mass and v is velocity.',
                'options' => [
                    ['text' => 'KE = ½mv²', 'correct' => true],
                    ['text' => 'KE = mgh', 'correct' => false],
                    ['text' => 'KE = mv', 'correct' => false],
                    ['text' => 'KE = ½mgh', 'correct' => false],
                ],
                'year' => 2022,
                'difficulty' => 'medium',
            ],
            [
                'question' => 'A sound wave travels faster in which medium?',
                'explanation' => 'Sound travels fastest in solids, slower in liquids, and slowest in gases due to particle density and molecular motion.',
                'options' => [
                    ['text' => 'Solid', 'correct' => true],
                    ['text' => 'Liquid', 'correct' => false],
                    ['text' => 'Gas', 'correct' => false],
                    ['text' => 'All the same', 'correct' => false],
                ],
                'year' => 2022,
                'difficulty' => 'hard',
            ],
        ];

        foreach ($questions as $q) {
            $this->createQuestion($admin, $examType, $subject, $q);
        }
    }

    private function createQuestion($admin, $examType, $subject, $data)
    {
        $question = Question::create([
            'question_text' => $data['question'],
            'explanation' => $data['explanation'],
            'subject_id' => $subject->id,
            'exam_type_id' => $examType->id,
            'exam_year' => $data['year'],
            'difficulty' => $data['difficulty'],
            'created_by' => $admin->id,
            'is_active' => true,
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        // Create options with labels A, B, C, D
        $labels = ['A', 'B', 'C', 'D'];
        foreach ($data['options'] as $index => $optionData) {
            $question->options()->create([
                'label' => $labels[$index] ?? chr(65 + $index),
                'option_text' => $optionData['text'],
                'is_correct' => $optionData['correct'],
            ]);
        }
    }
}
