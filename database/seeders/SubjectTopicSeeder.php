<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class SubjectTopicSeeder extends Seeder
{
    public function run(): void
    {
        // Check if data already exists
        if (ExamType::count() > 0) {
            $this->command->info('Exam types already exist. Skipping SubjectTopicSeeder.');
            return;
        }

        // Create Exam Types
        $waec = ExamType::create([
            'name' => 'WAEC',
            'slug' => 'waec',
            'code' => 'WAEC',
            'description' => 'West African Examinations Council - Senior School Certificate Examination',
            'color' => '#DC2626',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $neco = ExamType::create([
            'name' => 'NECO',
            'slug' => 'neco',
            'code' => 'NECO',
            'description' => 'National Examinations Council - Senior School Certificate Examination',
            'color' => '#16A34A',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $jamb = ExamType::create([
            'name' => 'JAMB UTME',
            'slug' => 'jamb-utme',
            'code' => 'JAMB',
            'description' => 'Joint Admissions and Matriculation Board - Unified Tertiary Matriculation Examination',
            'color' => '#2563EB',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $nabteb = ExamType::create([
            'name' => 'NABTEB',
            'slug' => 'nabteb',
            'code' => 'NABTEB',
            'description' => 'National Business and Technical Examinations Board',
            'color' => '#9333EA',
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Mathematics
        $mathematics = Subject::create([
            'name' => 'Mathematics',
            'slug' => 'mathematics',
            'description' => 'Study of numbers, quantities, shapes, and patterns',
            'icon' => '📐',
            'color' => '#3B82F6',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $mathematics->examTypes()->attach([$waec->id, $neco->id, $jamb->id, $nabteb->id]);

        Topic::insert([
            ['subject_id' => $mathematics->id, 'name' => 'Algebra', 'slug' => 'algebra', 'description' => 'Study of mathematical symbols and rules', 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $mathematics->id, 'name' => 'Geometry', 'slug' => 'geometry', 'description' => 'Study of shapes and spatial relationships', 'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $mathematics->id, 'name' => 'Trigonometry', 'slug' => 'trigonometry', 'description' => 'Study of triangles and angles', 'is_active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $mathematics->id, 'name' => 'Calculus', 'slug' => 'calculus', 'description' => 'Study of change and motion', 'is_active' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $mathematics->id, 'name' => 'Statistics', 'slug' => 'statistics', 'description' => 'Collection, analysis and interpretation of data', 'is_active' => true, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // English Language
        $english = Subject::create([
            'name' => 'English Language',
            'slug' => 'english-language',
            'description' => 'Study of English grammar, composition, and literature',
            'icon' => '📚',
            'color' => '#EF4444',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $english->examTypes()->attach([$waec->id, $neco->id, $jamb->id]);

        Topic::insert([
            ['subject_id' => $english->id, 'name' => 'Comprehension', 'slug' => 'comprehension', 'description' => 'Reading and understanding passages', 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $english->id, 'name' => 'Grammar', 'slug' => 'grammar', 'description' => 'Rules of language structure', 'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $english->id, 'name' => 'Essay Writing', 'slug' => 'essay-writing', 'description' => 'Composition and creative writing', 'is_active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $english->id, 'name' => 'Vocabulary', 'slug' => 'vocabulary', 'description' => 'Word meanings and usage', 'is_active' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Physics
        $physics = Subject::create([
            'name' => 'Physics',
            'slug' => 'physics',
            'description' => 'Study of matter, energy, and their interactions',
            'icon' => '⚛️',
            'color' => '#8B5CF6',
            'is_active' => true,
            'sort_order' => 3,
        ]);
        $physics->examTypes()->attach([$waec->id, $neco->id, $jamb->id]);

        Topic::insert([
            ['subject_id' => $physics->id, 'name' => 'Mechanics', 'slug' => 'mechanics', 'description' => 'Study of motion and forces', 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $physics->id, 'name' => 'Electricity', 'slug' => 'electricity', 'description' => 'Study of electric charges and circuits', 'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $physics->id, 'name' => 'Waves and Optics', 'slug' => 'waves-and-optics', 'description' => 'Study of wave motion and light', 'is_active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $physics->id, 'name' => 'Thermodynamics', 'slug' => 'thermodynamics', 'description' => 'Study of heat and energy transfer', 'is_active' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Chemistry
        $chemistry = Subject::create([
            'name' => 'Chemistry',
            'slug' => 'chemistry',
            'description' => 'Study of substances and their properties',
            'icon' => '🧪',
            'color' => '#10B981',
            'is_active' => true,
            'sort_order' => 4,
        ]);
        $chemistry->examTypes()->attach([$waec->id, $neco->id, $jamb->id]);

        Topic::insert([
            ['subject_id' => $chemistry->id, 'name' => 'Atomic Structure', 'slug' => 'atomic-structure', 'description' => 'Structure of atoms and elements', 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $chemistry->id, 'name' => 'Chemical Bonding', 'slug' => 'chemical-bonding', 'description' => 'How atoms combine', 'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $chemistry->id, 'name' => 'Organic Chemistry', 'slug' => 'organic-chemistry', 'description' => 'Chemistry of carbon compounds', 'is_active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $chemistry->id, 'name' => 'Acids and Bases', 'slug' => 'acids-and-bases', 'description' => 'Properties of acids and bases', 'is_active' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Biology
        $biology = Subject::create([
            'name' => 'Biology',
            'slug' => 'biology',
            'description' => 'Study of living organisms',
            'icon' => '🧬',
            'color' => '#059669',
            'is_active' => true,
            'sort_order' => 5,
        ]);
        $biology->examTypes()->attach([$waec->id, $neco->id, $jamb->id]);

        Topic::insert([
            ['subject_id' => $biology->id, 'name' => 'Cell Biology', 'slug' => 'cell-biology', 'description' => 'Structure and function of cells', 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $biology->id, 'name' => 'Genetics', 'slug' => 'genetics', 'description' => 'Study of heredity and genes', 'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $biology->id, 'name' => 'Ecology', 'slug' => 'ecology', 'description' => 'Organisms and their environment', 'is_active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $biology->id, 'name' => 'Human Anatomy', 'slug' => 'human-anatomy', 'description' => 'Structure of the human body', 'is_active' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Economics
        $economics = Subject::create([
            'name' => 'Economics',
            'slug' => 'economics',
            'description' => 'Study of production, distribution, and consumption',
            'icon' => '💰',
            'color' => '#F59E0B',
            'is_active' => true,
            'sort_order' => 6,
        ]);
        $economics->examTypes()->attach([$waec->id, $neco->id, $jamb->id]);

        Topic::insert([
            ['subject_id' => $economics->id, 'name' => 'Microeconomics', 'slug' => 'microeconomics', 'description' => 'Individual markets and consumers', 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $economics->id, 'name' => 'Macroeconomics', 'slug' => 'macroeconomics', 'description' => 'National and global economy', 'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $economics->id, 'name' => 'Money and Banking', 'slug' => 'money-and-banking', 'description' => 'Financial systems', 'is_active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Government/Civic Education
        $government = Subject::create([
            'name' => 'Government',
            'slug' => 'government',
            'description' => 'Study of political systems and governance',
            'icon' => '🏛️',
            'color' => '#6366F1',
            'is_active' => true,
            'sort_order' => 7,
        ]);
        $government->examTypes()->attach([$waec->id, $neco->id, $jamb->id]);

        Topic::insert([
            ['subject_id' => $government->id, 'name' => 'Constitutional Law', 'slug' => 'constitutional-law', 'description' => 'Principles of constitution', 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $government->id, 'name' => 'Democracy', 'slug' => 'democracy', 'description' => 'Democratic systems and practices', 'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $government->id, 'name' => 'Political Parties', 'slug' => 'political-parties', 'description' => 'Party systems and politics', 'is_active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('✅ Exam types, subjects, and topics created successfully!');
        $this->command->info("📚 Created {$waec->subjects()->count()} subjects for WAEC");
        $this->command->info("📚 Created {$neco->subjects()->count()} subjects for NECO");
        $this->command->info("📚 Created {$jamb->subjects()->count()} subjects for JAMB");
        $this->command->info("📋 Total topics: " . Topic::count());
    }
}
