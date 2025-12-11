<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class JambPhysicsBatchSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $jambExam = ExamType::where('slug', 'jamb')->first();
        $subject = Subject::where('name', 'Physics')->first();

        if (!$admin || !$jambExam || !$subject) {
            $this->command->error('Required data not found.');
            return;
        }

        $questions = [
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
            ['text' => 'What is acceleration due to gravity?', 'options' => [['A', '8.9 m/s²', false], ['B', '9.8 m/s²', true], ['C', '10.8 m/s²', false], ['D', '11.8 m/s²', false]], 'explanation' => 'g ≈ 9.8 m/s² on Earth', 'year' => 2022],
            ['text' => 'Ohm\'s Law formula:', 'options' => [['A', 'P=IV', false], ['B', 'V=IR', true], ['C', 'F=ma', false], ['D', 'E=mc²', false]], 'explanation' => 'V=IR (Voltage = Current × Resistance)', 'year' => 2022],
            ['text' => 'What type of lens is used in magnifying glass?', 'options' => [['A', 'Concave', false], ['B', 'Convex', true], ['C', 'Plane', false], ['D', 'Cylindrical', false]], 'explanation' => 'Convex lens converges light', 'year' => 2022],
            ['text' => 'What is the unit of frequency?', 'options' => [['A', 'Newton', false], ['B', 'Joule', false], ['C', 'Hertz', true], ['D', 'Watt', false]], 'explanation' => 'Hertz (Hz) measures frequency', 'year' => 2022],
            ['text' => 'Which law: "Action and reaction are equal"', 'options' => [['A', 'First law', false], ['B', 'Second law', false], ['C', 'Third law', true], ['D', 'Law of gravity', false]], 'explanation' => 'Newton\'s third law', 'year' => 2022],
            ['text' => 'What is the unit of electric current?', 'options' => [['A', 'Volt', false], ['B', 'Ampere', true], ['C', 'Ohm', false], ['D', 'Watt', false]], 'explanation' => 'Ampere (A) is unit of current', 'year' => 2022],
            ['text' => 'Calculate momentum: mass 2kg, velocity 5m/s', 'options' => [['A', '7 kg·m/s', false], ['B', '10 kg·m/s', true], ['C', '3 kg·m/s', false], ['D', '2.5 kg·m/s', false]], 'explanation' => 'p = mv = 2 × 5 = 10 kg·m/s', 'year' => 2022],
            ['text' => 'What is the formula for potential energy?', 'options' => [['A', '½mv²', false], ['B', 'mgh', true], ['C', 'mv', false], ['D', 'Fd', false]], 'explanation' => 'PE = mgh', 'year' => 2022],
            ['text' => 'What is the unit of electric charge?', 'options' => [['A', 'Ampere', false], ['B', 'Coulomb', true], ['C', 'Volt', false], ['D', 'Ohm', false]], 'explanation' => 'Coulomb (C) is unit of charge', 'year' => 2022],
            ['text' => 'Which color has the highest frequency?', 'options' => [['A', 'Red', false], ['B', 'Green', false], ['C', 'Blue', false], ['D', 'Violet', true]], 'explanation' => 'Violet has highest frequency in visible light', 'year' => 2022],
            ['text' => 'What is the unit of resistance?', 'options' => [['A', 'Volt', false], ['B', 'Ampere', false], ['C', 'Ohm', true], ['D', 'Watt', false]], 'explanation' => 'Ohm (Ω) is unit of resistance', 'year' => 2023],
            ['text' => 'Calculate work: Force=10N, Distance=5m', 'options' => [['A', '15J', false], ['B', '50J', true], ['C', '2J', false], ['D', '5J', false]], 'explanation' => 'Work = F × d = 10 × 5 = 50J', 'year' => 2023],
            ['text' => 'What is the unit of power?', 'options' => [['A', 'Joule', false], ['B', 'Newton', false], ['C', 'Watt', true], ['D', 'Pascal', false]], 'explanation' => 'Watt (W) is unit of power', 'year' => 2023],
            ['text' => 'Which is a scalar quantity?', 'options' => [['A', 'Velocity', false], ['B', 'Force', false], ['C', 'Speed', true], ['D', 'Acceleration', false]], 'explanation' => 'Speed has only magnitude', 'year' => 2023],
            ['text' => 'What is the formula for acceleration?', 'options' => [['A', 'v/t', false], ['B', '(v-u)/t', true], ['C', 'mv', false], ['D', 'Fd', false]], 'explanation' => 'a = (v-u)/t', 'year' => 2023],
            ['text' => 'What is the unit of voltage?', 'options' => [['A', 'Ampere', false], ['B', 'Volt', true], ['C', 'Ohm', false], ['D', 'Watt', false]], 'explanation' => 'Volt (V) is unit of voltage', 'year' => 2023],
            ['text' => 'Calculate distance: speed=20m/s, time=5s', 'options' => [['A', '4m', false], ['B', '25m', false], ['C', '100m', true], ['D', '15m', false]], 'explanation' => 'Distance = speed × time = 20 × 5 = 100m', 'year' => 2023],
            ['text' => 'What type of mirror is used in car rear-view?', 'options' => [['A', 'Plane', false], ['B', 'Convex', true], ['C', 'Concave', false], ['D', 'Cylindrical', false]], 'explanation' => 'Convex mirror gives wider field of view', 'year' => 2023],
            ['text' => 'What is specific heat capacity measured in?', 'options' => [['A', 'J/kg', false], ['B', 'J/(kg·K)', true], ['C', 'J/K', false], ['D', 'kg/J', false]], 'explanation' => 'Specific heat: J/(kg·K)', 'year' => 2023],
            ['text' => 'Which material is a good conductor?', 'options' => [['A', 'Wood', false], ['B', 'Plastic', false], ['C', 'Copper', true], ['D', 'Rubber', false]], 'explanation' => 'Copper is an excellent conductor', 'year' => 2023],
            ['text' => 'What is the formula for density?', 'options' => [['A', 'm/v', true], ['B', 'mv', false], ['C', 'v/m', false], ['D', 'm+v', false]], 'explanation' => 'Density = mass/volume', 'year' => 2023],
            ['text' => 'What is the unit of magnetic flux?', 'options' => [['A', 'Tesla', false], ['B', 'Weber', true], ['C', 'Henry', false], ['D', 'Ampere', false]], 'explanation' => 'Weber (Wb) is unit of magnetic flux', 'year' => 2023],
            ['text' => 'Calculate speed: distance=100m, time=10s', 'options' => [['A', '5 m/s', false], ['B', '10 m/s', true], ['C', '15 m/s', false], ['D', '20 m/s', false]], 'explanation' => 'Speed = 100/10 = 10 m/s', 'year' => 2023],
            ['text' => 'What happens to resistance when temperature increases in metals?', 'options' => [['A', 'Decreases', false], ['B', 'Increases', true], ['C', 'Stays same', false], ['D', 'Becomes zero', false]], 'explanation' => 'Resistance increases with temperature in metals', 'year' => 2023],
            ['text' => 'What is the principle of flotation?', 'options' => [['A', 'Weight = Upthrust', true], ['B', 'Weight = Mass', false], ['C', 'Mass = Volume', false], ['D', 'Force = Pressure', false]], 'explanation' => 'Object floats when weight equals upthrust', 'year' => 2023],
            ['text' => 'What is the unit of capacitance?', 'options' => [['A', 'Henry', false], ['B', 'Farad', true], ['C', 'Ohm', false], ['D', 'Volt', false]], 'explanation' => 'Farad (F) is unit of capacitance', 'year' => 2023],
            ['text' => 'What is atmospheric pressure at sea level?', 'options' => [['A', '101 kPa', true], ['B', '100 kPa', false], ['C', '110 kPa', false], ['D', '90 kPa', false]], 'explanation' => 'Standard atmospheric pressure ≈ 101 kPa', 'year' => 2023],
            ['text' => 'Which law: "Energy cannot be created or destroyed"', 'options' => [['A', 'Newton\'s law', false], ['B', 'Conservation of energy', true], ['C', 'Ohm\'s law', false], ['D', 'Hooke\'s law', false]], 'explanation' => 'Law of conservation of energy', 'year' => 2023],
            ['text' => 'What is the refractive index of water approximately?', 'options' => [['A', '1.0', false], ['B', '1.33', true], ['C', '1.5', false], ['D', '2.0', false]], 'explanation' => 'Refractive index of water ≈ 1.33', 'year' => 2023],
            ['text' => 'Calculate: If v = u + at, u=0, a=2m/s², t=5s, find v', 'options' => [['A', '5 m/s', false], ['B', '10 m/s', true], ['C', '15 m/s', false], ['D', '20 m/s', false]], 'explanation' => 'v = 0 + (2×5) = 10 m/s', 'year' => 2023],
        ];

        $this->command->info('Seeding 40 Physics questions...');
        $this->createQuestions($questions, $subject, $jambExam, $admin);
        $this->command->info('Physics seeding complete!');
    }

    private function createQuestions(array $questions, $subject, $examType, $admin): void
    {
        foreach ($questions as $q) {
            // Check if question already exists to prevent duplicates
            $exists = Question::where('question_text', $q['text'])
                ->where('subject_id', $subject->id)
                ->where('exam_type_id', $examType->id)
                ->where('exam_year', $q['year'])
                ->exists();

            if ($exists) {
                $this->command->warn("Skipping duplicate: " . substr($q['text'], 0, 50) . "...");
                continue;
            }

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
