<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class JambQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $jambExam = ExamType::where('slug', 'jamb')->first();

        if (!$admin || !$jambExam) {
            $this->command->error('Admin user or JAMB exam type not found.');
            return;
        }

        $subjects = [
            'Mathematics' => $this->getMathQuestions(),
            'English Language' => $this->getEnglishQuestions(),
            'Physics' => $this->getPhysicsQuestions(),
            'Chemistry' => $this->getChemistryQuestions(),
        ];

        foreach ($subjects as $subjectName => $questions) {
            $subject = Subject::where('name', $subjectName)->first();
            if (!$subject) continue;

            $this->command->info("Seeding {$subjectName} questions...");
            $this->createQuestions($questions, $subject, $jambExam, $admin);
        }

        $this->command->info('JAMB questions seeded successfully!');
    }

    private function getMathQuestions(): array
    {
        return [
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
            ['text' => 'What is 60% of 150?', 'options' => [['A', '80', false], ['B', '85', false], ['C', '90', true], ['D', '95', false]], 'explanation' => '0.6 × 150 = 90', 'year' => 2023],
            ['text' => 'Solve: x/4 = 12', 'options' => [['A', '3', false], ['B', '16', false], ['C', '48', true], ['D', '52', false]], 'explanation' => 'x = 12 × 4 = 48', 'year' => 2023],
            ['text' => 'Find the average of 10, 20, 30, 40', 'options' => [['A', '20', false], ['B', '25', true], ['C', '30', false], ['D', '35', false]], 'explanation' => '(10+20+30+40)/4 = 25', 'year' => 2023],
            ['text' => 'What is 2³?', 'options' => [['A', '6', false], ['B', '8', true], ['C', '9', false], ['D', '16', false]], 'explanation' => '2 × 2 × 2 = 8', 'year' => 2023],
            ['text' => 'If 5 + x = 12, find x', 'options' => [['A', '5', false], ['B', '6', false], ['C', '7', true], ['D', '8', false]], 'explanation' => 'x = 12 - 5 = 7', 'year' => 2023],
        ];
    }

    private function getEnglishQuestions(): array
    {
        return [
            ['text' => 'Choose the opposite of "generous"', 'options' => [['A', 'Kind', false], ['B', 'Selfish', true], ['C', 'Wealthy', false], ['D', 'Happy', false]], 'explanation' => 'Selfish means unwilling to share', 'year' => 2022],
            ['text' => 'Identify the correct sentence', 'options' => [['A', 'She don\'t like it', false], ['B', 'She doesn\'t like it', true], ['C', 'She not like it', false], ['D', 'She doesn\'t likes it', false]], 'explanation' => 'Correct form: doesn\'t + base verb', 'year' => 2022],
            ['text' => 'What is the plural of "child"?', 'options' => [['A', 'childs', false], ['B', 'childes', false], ['C', 'children', true], ['D', 'childrens', false]], 'explanation' => 'Irregular plural form', 'year' => 2022],
            ['text' => 'Choose the synonym of "happy"', 'options' => [['A', 'Sad', false], ['B', 'Angry', false], ['C', 'Joyful', true], ['D', 'Tired', false]], 'explanation' => 'Joyful means happy', 'year' => 2022],
            ['text' => 'Which is a pronoun?', 'options' => [['A', 'Run', false], ['B', 'Quick', false], ['C', 'He', true], ['D', 'Book', false]], 'explanation' => 'He is a personal pronoun', 'year' => 2022],
            ['text' => 'Choose the correct: "Its a ___ day"', 'options' => [['A', 'beautifull', false], ['B', 'beautiful', true], ['C', 'beutiful', false], ['D', 'beautful', false]], 'explanation' => 'Correct spelling: beautiful', 'year' => 2022],
            ['text' => 'What is the past tense of "go"?', 'options' => [['A', 'goed', false], ['B', 'went', true], ['C', 'gone', false], ['D', 'going', false]], 'explanation' => 'Irregular past tense', 'year' => 2022],
            ['text' => 'Choose the correct article: "___ apple"', 'options' => [['A', 'A', false], ['B', 'An', true], ['C', 'The', false], ['D', 'No article', false]], 'explanation' => 'Use "an" before vowel sounds', 'year' => 2022],
            ['text' => 'Which is an adjective?', 'options' => [['A', 'Quickly', false], ['B', 'Blue', true], ['C', 'Run', false], ['D', 'She', false]], 'explanation' => 'Blue describes a noun', 'year' => 2022],
            ['text' => 'Choose the correct: "They ___ students"', 'options' => [['A', 'is', false], ['B', 'am', false], ['C', 'are', true], ['D', 'be', false]], 'explanation' => 'Use "are" with plural subjects', 'year' => 2022],
            ['text' => 'What is the opposite of "fast"?', 'options' => [['A', 'Quick', false], ['B', 'Slow', true], ['C', 'Speed', false], ['D', 'Run', false]], 'explanation' => 'Slow is the opposite of fast', 'year' => 2023],
            ['text' => 'Choose the correct preposition: "on ___ table"', 'options' => [['A', 'at', false], ['B', 'in', false], ['C', 'the', true], ['D', 'of', false]], 'explanation' => 'Use "on the table"', 'year' => 2023],
            ['text' => 'Which is a verb?', 'options' => [['A', 'Happy', false], ['B', 'Book', false], ['C', 'Sing', true], ['D', 'Beautiful', false]], 'explanation' => 'Sing is an action word', 'year' => 2023],
            ['text' => 'Choose the correct: "I ___ to school"', 'options' => [['A', 'goes', false], ['B', 'go', true], ['C', 'going', false], ['D', 'gone', false]], 'explanation' => 'Use base form with "I"', 'year' => 2023],
            ['text' => 'What is the plural of "box"?', 'options' => [['A', 'boxs', false], ['B', 'boxes', true], ['C', 'boxies', false], ['D', 'boxen', false]], 'explanation' => 'Add -es to words ending in x', 'year' => 2023],
        ];
    }

    private function getPhysicsQuestions(): array
    {
        return [
            ['text' => 'What is the SI unit of force?', 'options' => [['A', 'Joule', false], ['B', 'Newton', true], ['C', 'Watt', false], ['D', 'Pascal', false]], 'explanation' => 'Newton (N) is the unit of force', 'year' => 2022],
            ['text' => 'Calculate force: mass 5kg, acceleration 2m/s²', 'options' => [['A', '7N', false], ['B', '10N', true], ['C', '2.5N', false], ['D', '3N', false]], 'explanation' => 'F = ma = 5 × 2 = 10N', 'year' => 2022],
            ['text' => 'Which is a vector quantity?', 'options' => [['A', 'Speed', false], ['B', 'Mass', false], ['C', 'Velocity', true], ['D', 'Temperature', false]], 'explanation' => 'Velocity has magnitude and direction', 'year' => 2022],
            ['text' => 'What is the speed of light?', 'options' => [['A', '3×10⁶ m/s', false], ['B', '3×10⁸ m/s', true], ['C', '3×10⁹ m/s', false], ['D', '3×10⁷ m/s', false]], 'explanation' => 'Light travels at 3×10⁸ m/s', 'year' => 2022],
            ['text' => 'What is the unit of energy?', 'options' => [['A', 'Newton', false], ['B', 'Joule', true], ['C', 'Watt', false], ['D', 'Volt', false]], 'explanation' => 'Joule (J) is the unit of energy', 'year' => 2022],
            ['text' => 'What is Newton\'s first law?', 'options' => [['A', 'F=ma', false], ['B', 'Inertia', true], ['C', 'Action-reaction', false], ['D', 'Gravity', false]], 'explanation' => 'Law of inertia: object at rest stays at rest', 'year' => 2022],
            ['text' => 'Calculate: Power = Work/Time, Work=100J, Time=5s', 'options' => [['A', '10W', false], ['B', '20W', true], ['C', '25W', false], ['D', '30W', false]], 'explanation' => 'Power = 100/5 = 20W', 'year' => 2022],
            ['text' => 'What is the formula for kinetic energy?', 'options' => [['A', 'mgh', false], ['B', '½mv²', true], ['C', 'mv', false], ['D', 'Fd', false]], 'explanation' => 'KE = ½mv²', 'year' => 2022],
            ['text' => 'What is the SI unit of pressure?', 'options' => [['A', 'Newton', false], ['B', 'Joule', false], ['C', 'Pascal', true], ['D', 'Watt', false]], 'explanation' => 'Pascal (Pa) is the unit of pressure', 'year' => 2022],
            ['text' => 'Which has the longest wavelength?', 'options' => [['A', 'X-rays', false], ['B', 'Visible light', false], ['C', 'Radio waves', true], ['D', 'Gamma rays', false]], 'explanation' => 'Radio waves have the longest wavelength', 'year' => 2022],
            ['text' => 'What is acceleration due to gravity?', 'options' => [['A', '8.9 m/s²', false], ['B', '9.8 m/s²', true], ['C', '10.8 m/s²', false], ['D', '11.8 m/s²', false]], 'explanation' => 'g ≈ 9.8 m/s² on Earth', 'year' => 2023],
            ['text' => 'Ohm\'s Law formula:', 'options' => [['A', 'P=IV', false], ['B', 'V=IR', true], ['C', 'F=ma', false], ['D', 'E=mc²', false]], 'explanation' => 'V=IR (Voltage = Current × Resistance)', 'year' => 2023],
            ['text' => 'What type of lens is used in magnifying glass?', 'options' => [['A', 'Concave', false], ['B', 'Convex', true], ['C', 'Plane', false], ['D', 'Cylindrical', false]], 'explanation' => 'Convex lens converges light', 'year' => 2023],
            ['text' => 'What is the unit of frequency?', 'options' => [['A', 'Newton', false], ['B', 'Joule', false], ['C', 'Hertz', true], ['D', 'Watt', false]], 'explanation' => 'Hertz (Hz) measures frequency', 'year' => 2023],
            ['text' => 'Which law: "Action and reaction are equal"', 'options' => [['A', 'First law', false], ['B', 'Second law', false], ['C', 'Third law', true], ['D', 'Law of gravity', false]], 'explanation' => 'Newton\'s third law', 'year' => 2023],
        ];
    }

    private function getChemistryQuestions(): array
    {
        return [
            ['text' => 'What is the chemical formula for water?', 'options' => [['A', 'H2O', true], ['B', 'CO2', false], ['C', 'O2', false], ['D', 'H2O2', false]], 'explanation' => 'Water = H2O', 'year' => 2022],
            ['text' => 'What is the atomic number of Carbon?', 'options' => [['A', '4', false], ['B', '6', true], ['C', '8', false], ['D', '12', false]], 'explanation' => 'Carbon has 6 protons', 'year' => 2022],
            ['text' => 'Which is an acid?', 'options' => [['A', 'NaOH', false], ['B', 'HCl', true], ['C', 'NaCl', false], ['D', 'KOH', false]], 'explanation' => 'HCl is hydrochloric acid', 'year' => 2022],
            ['text' => 'What is the pH of pure water?', 'options' => [['A', '0', false], ['B', '7', true], ['C', '14', false], ['D', '1', false]], 'explanation' => 'Neutral pH = 7', 'year' => 2022],
            ['text' => 'What is the symbol for Gold?', 'options' => [['A', 'Go', false], ['B', 'Gd', false], ['C', 'Au', true], ['D', 'Ag', false]], 'explanation' => 'Gold = Au (Aurum)', 'year' => 2022],
            ['text' => 'How many electrons in Carbon?', 'options' => [['A', '4', false], ['B', '6', true], ['C', '8', false], ['D', '12', false]], 'explanation' => 'Atomic number = electrons', 'year' => 2022],
            ['text' => 'What is table salt\'s chemical name?', 'options' => [['A', 'Sodium chloride', true], ['B', 'Sodium carbonate', false], ['C', 'Calcium chloride', false], ['D', 'Potassium chloride', false]], 'explanation' => 'NaCl = Sodium chloride', 'year' => 2022],
            ['text' => 'Which gas do plants absorb?', 'options' => [['A', 'Oxygen', false], ['B', 'Nitrogen', false], ['C', 'Carbon dioxide', true], ['D', 'Hydrogen', false]], 'explanation' => 'Plants use CO2 for photosynthesis', 'year' => 2022],
            ['text' => 'What is the atomic number of Oxygen?', 'options' => [['A', '6', false], ['B', '7', false], ['C', '8', true], ['D', '9', false]], 'explanation' => 'Oxygen has 8 protons', 'year' => 2022],
            ['text' => 'Which is a noble gas?', 'options' => [['A', 'Oxygen', false], ['B', 'Nitrogen', false], ['C', 'Helium', true], ['D', 'Hydrogen', false]], 'explanation' => 'Helium is a noble gas', 'year' => 2022],
            ['text' => 'What is the formula for carbon dioxide?', 'options' => [['A', 'CO', false], ['B', 'CO2', true], ['C', 'C2O', false], ['D', 'O2', false]], 'explanation' => 'Carbon dioxide = CO2', 'year' => 2023],
            ['text' => 'Which element has symbol Na?', 'options' => [['A', 'Neon', false], ['B', 'Nitrogen', false], ['C', 'Sodium', true], ['D', 'Nickel', false]], 'explanation' => 'Na = Sodium (Natrium)', 'year' => 2023],
            ['text' => 'What is the charge of a proton?', 'options' => [['A', 'Negative', false], ['B', 'Positive', true], ['C', 'Neutral', false], ['D', 'Variable', false]], 'explanation' => 'Protons have positive charge', 'year' => 2023],
            ['text' => 'Which is a base?', 'options' => [['A', 'HCl', false], ['B', 'H2SO4', false], ['C', 'NaOH', true], ['D', 'HNO3', false]], 'explanation' => 'NaOH is sodium hydroxide (base)', 'year' => 2023],
            ['text' => 'What is the symbol for Iron?', 'options' => [['A', 'Ir', false], ['B', 'Fe', true], ['C', 'In', false], ['D', 'I', false]], 'explanation' => 'Iron = Fe (Ferrum)', 'year' => 2023],
        ];
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
