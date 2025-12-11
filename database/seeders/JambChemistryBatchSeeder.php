<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class JambChemistryBatchSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $jambExam = ExamType::where('slug', 'jamb')->first();
        $subject = Subject::where('name', 'Chemistry')->first();

        if (!$admin || !$jambExam || !$subject) {
            $this->command->error('Required data not found.');
            return;
        }

        $questions = [
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
            ['text' => 'What is the formula for carbon dioxide?', 'options' => [['A', 'CO', false], ['B', 'CO2', true], ['C', 'C2O', false], ['D', 'O2', false]], 'explanation' => 'Carbon dioxide = CO2', 'year' => 2022],
            ['text' => 'Which element has symbol Na?', 'options' => [['A', 'Neon', false], ['B', 'Nitrogen', false], ['C', 'Sodium', true], ['D', 'Nickel', false]], 'explanation' => 'Na = Sodium (Natrium)', 'year' => 2022],
            ['text' => 'What is the charge of a proton?', 'options' => [['A', 'Negative', false], ['B', 'Positive', true], ['C', 'Neutral', false], ['D', 'Variable', false]], 'explanation' => 'Protons have positive charge', 'year' => 2022],
            ['text' => 'Which is a base?', 'options' => [['A', 'HCl', false], ['B', 'H2SO4', false], ['C', 'NaOH', true], ['D', 'HNO3', false]], 'explanation' => 'NaOH is sodium hydroxide (base)', 'year' => 2022],
            ['text' => 'What is the symbol for Iron?', 'options' => [['A', 'Ir', false], ['B', 'Fe', true], ['C', 'In', false], ['D', 'I', false]], 'explanation' => 'Iron = Fe (Ferrum)', 'year' => 2022],
            ['text' => 'What is the valency of Oxygen?', 'options' => [['A', '1', false], ['B', '2', true], ['C', '3', false], ['D', '4', false]], 'explanation' => 'Oxygen has valency of 2', 'year' => 2022],
            ['text' => 'Which gas makes up most of Earth\'s atmosphere?', 'options' => [['A', 'Oxygen', false], ['B', 'Nitrogen', true], ['C', 'Carbon dioxide', false], ['D', 'Argon', false]], 'explanation' => 'Nitrogen makes up ~78% of atmosphere', 'year' => 2022],
            ['text' => 'What is the symbol for Silver?', 'options' => [['A', 'Si', false], ['B', 'Ag', true], ['C', 'Au', false], ['D', 'S', false]], 'explanation' => 'Silver = Ag (Argentum)', 'year' => 2022],
            ['text' => 'What is the atomic mass of Hydrogen?', 'options' => [['A', '1', true], ['B', '2', false], ['C', '4', false], ['D', '8', false]], 'explanation' => 'Hydrogen atomic mass ≈ 1', 'year' => 2022],
            ['text' => 'Which is an alkali metal?', 'options' => [['A', 'Carbon', false], ['B', 'Sodium', true], ['C', 'Chlorine', false], ['D', 'Oxygen', false]], 'explanation' => 'Sodium is an alkali metal (Group 1)', 'year' => 2022],
            ['text' => 'What is the formula for methane?', 'options' => [['A', 'CH4', true], ['B', 'C2H6', false], ['C', 'CO2', false], ['D', 'C3H8', false]], 'explanation' => 'Methane = CH4', 'year' => 2023],
            ['text' => 'What is the symbol for Potassium?', 'options' => [['A', 'P', false], ['B', 'Po', false], ['C', 'K', true], ['D', 'Pt', false]], 'explanation' => 'Potassium = K (Kalium)', 'year' => 2023],
            ['text' => 'What is the atomic number of Nitrogen?', 'options' => [['A', '5', false], ['B', '7', true], ['C', '8', false], ['D', '14', false]], 'explanation' => 'Nitrogen has 7 protons', 'year' => 2023],
            ['text' => 'Which is a halogen?', 'options' => [['A', 'Sodium', false], ['B', 'Chlorine', true], ['C', 'Oxygen', false], ['D', 'Nitrogen', false]], 'explanation' => 'Chlorine is a halogen (Group 17)', 'year' => 2023],
            ['text' => 'What is the formula for sulfuric acid?', 'options' => [['A', 'HCl', false], ['B', 'H2SO4', true], ['C', 'HNO3', false], ['D', 'H3PO4', false]], 'explanation' => 'Sulfuric acid = H2SO4', 'year' => 2023],
            ['text' => 'What is the symbol for Copper?', 'options' => [['A', 'Co', false], ['B', 'Cu', true], ['C', 'Cr', false], ['D', 'C', false]], 'explanation' => 'Copper = Cu (Cuprum)', 'year' => 2023],
            ['text' => 'How many electrons can the first shell hold?', 'options' => [['A', '2', true], ['B', '4', false], ['C', '8', false], ['D', '10', false]], 'explanation' => 'First shell holds maximum 2 electrons', 'year' => 2023],
            ['text' => 'What is the formula for ammonia?', 'options' => [['A', 'NH3', true], ['B', 'NH4', false], ['C', 'N2H2', false], ['D', 'N2H4', false]], 'explanation' => 'Ammonia = NH3', 'year' => 2023],
            ['text' => 'Which element has atomic number 1?', 'options' => [['A', 'Helium', false], ['B', 'Hydrogen', true], ['C', 'Lithium', false], ['D', 'Carbon', false]], 'explanation' => 'Hydrogen is the first element', 'year' => 2023],
            ['text' => 'What is the symbol for Lead?', 'options' => [['A', 'L', false], ['B', 'Le', false], ['C', 'Pb', true], ['D', 'Ld', false]], 'explanation' => 'Lead = Pb (Plumbum)', 'year' => 2023],
            ['text' => 'What is the valency of Hydrogen?', 'options' => [['A', '1', true], ['B', '2', false], ['C', '3', false], ['D', '4', false]], 'explanation' => 'Hydrogen has valency of 1', 'year' => 2023],
            ['text' => 'Which gas is used in fire extinguishers?', 'options' => [['A', 'Oxygen', false], ['B', 'Nitrogen', false], ['C', 'Carbon dioxide', true], ['D', 'Hydrogen', false]], 'explanation' => 'CO2 is used in fire extinguishers', 'year' => 2023],
            ['text' => 'What is the formula for nitric acid?', 'options' => [['A', 'HCl', false], ['B', 'H2SO4', false], ['C', 'HNO3', true], ['D', 'CH3COOH', false]], 'explanation' => 'Nitric acid = HNO3', 'year' => 2023],
            ['text' => 'What is the atomic number of Aluminum?', 'options' => [['A', '11', false], ['B', '12', false], ['C', '13', true], ['D', '14', false]], 'explanation' => 'Aluminum has 13 protons', 'year' => 2023],
            ['text' => 'Which is an inert gas?', 'options' => [['A', 'Oxygen', false], ['B', 'Nitrogen', false], ['C', 'Neon', true], ['D', 'Hydrogen', false]], 'explanation' => 'Neon is a noble/inert gas', 'year' => 2023],
            ['text' => 'What is the symbol for Mercury?', 'options' => [['A', 'Me', false], ['B', 'Hg', true], ['C', 'Mc', false], ['D', 'Mr', false]], 'explanation' => 'Mercury = Hg (Hydrargyrum)', 'year' => 2023],
            ['text' => 'What is the formula for calcium carbonate?', 'options' => [['A', 'CaCO3', true], ['B', 'Ca(OH)2', false], ['C', 'CaCl2', false], ['D', 'CaSO4', false]], 'explanation' => 'Calcium carbonate = CaCO3', 'year' => 2023],
            ['text' => 'How many protons does Helium have?', 'options' => [['A', '1', false], ['B', '2', true], ['C', '3', false], ['D', '4', false]], 'explanation' => 'Helium has 2 protons', 'year' => 2023],
            ['text' => 'What is the symbol for Tin?', 'options' => [['A', 'Ti', false], ['B', 'Sn', true], ['C', 'Tn', false], ['D', 'T', false]], 'explanation' => 'Tin = Sn (Stannum)', 'year' => 2023],
            ['text' => 'Which element is a liquid at room temperature?', 'options' => [['A', 'Iron', false], ['B', 'Mercury', true], ['C', 'Gold', false], ['D', 'Silver', false]], 'explanation' => 'Mercury is liquid at room temperature', 'year' => 2023],
        ];

        $this->command->info('Seeding 40 Chemistry questions...');
        $this->createQuestions($questions, $subject, $jambExam, $admin);
        $this->command->info('Chemistry seeding complete!');
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
