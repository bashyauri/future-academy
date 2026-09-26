<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call(RolePermissionSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(StreamSeeder::class);
        $this->call(ExamTypeSeeder::class);
        $this->call(SubjectTopicSeeder::class);
        $this->call(SubjectSeeder::class);
        $this->call(TopicSeeder::class);
        $this->call(QuestionSeeder_New::class);
        $this->call(QuizSeeder::class);
        $this->call(LessonSeeder::class);

        $this->command->info('Database seeding completed successfully! 🎉');
        $this->command->line('Test Credentials:');
        $this->command->line('Admin: admin@future-academy.com');
        $this->command->line('Teacher: okafor@future-academy.com');
        $this->command->line('Student: chioma.eze@student.com');
        $this->command->line('Password: password');
    }
}
