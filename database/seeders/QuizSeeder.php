<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
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

        // Get some subjects and exam types
        $subjects = Subject::limit(3)->pluck('id')->toArray();
        $examTypes = ExamType::limit(2)->pluck('id')->toArray();

        if (empty($subjects) || empty($examTypes)) {
            $this->command->error('No subjects or exam types found. Run SubjectTopicSeeder first.');
            return;
        }

        // Create Practice Quiz
        $practiceQuiz = Quiz::create([
            'title' => 'Mathematics Practice Test',
            'description' => 'Test your knowledge with these practice questions covering basic mathematics concepts.',
            'type' => 'practice',
            'duration_minutes' => null,
            'passing_score' => 50,
            'question_count' => 10,
            'subject_ids' => [$subjects[0]],
            'difficulty_levels' => ['easy', 'medium'],
            'randomize_questions' => true,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'show_answers_after_submit' => true,
            'allow_review' => true,
            'show_explanations' => true,
            'max_attempts' => null,
            'is_active' => true,
            'created_by' => $admin->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Create Timed Quiz
        $timedQuiz = Quiz::create([
            'title' => 'English Language Speed Test',
            'description' => 'Complete this timed test in 30 minutes to challenge your English language skills.',
            'type' => 'timed',
            'duration_minutes' => 30,
            'passing_score' => 60,
            'question_count' => 20,
            'subject_ids' => array_slice($subjects, 0, 2),
            'difficulty_levels' => ['medium'],
            'randomize_questions' => true,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'show_answers_after_submit' => true,
            'allow_review' => true,
            'show_explanations' => true,
            'max_attempts' => 3,
            'status' => 'published',
            'published_at' => now(),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        // Create Mock Exam
        $mockQuiz = Quiz::create([
            'title' => 'JAMB Mock Examination 2024',
            'description' => 'Practice with past JAMB questions from previous years.',
            'type' => 'mock',
            'duration_minutes' => 120,
            'passing_score' => 70,
            'question_count' => 40,
            'subject_ids' => $subjects,
            'exam_type_ids' => [$examTypes[0]],
            'difficulty_levels' => ['medium', 'hard'],
            'years' => [2023, 2022, 2021],
            'randomize_questions' => false,
            'shuffle_questions' => false,
            'shuffle_options' => true,
            'show_answers_after_submit' => false,
            'allow_review' => true,
            'show_explanations' => false,
            'max_attempts' => 2,
            'is_active' => true,
            'available_from' => now(),
            'available_until' => now()->addMonths(3),
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        $this->command->info('✅ Sample quizzes created successfully!');
        $this->command->info("📝 Practice Quiz: {$practiceQuiz->title}");
        $this->command->info("⏱️  Timed Quiz: {$timedQuiz->title}");
        $this->command->info("🎓 Mock Exam: {$mockQuiz->title}");
    }
}
