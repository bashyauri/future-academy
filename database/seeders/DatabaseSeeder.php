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
        $this->call(\Database\Seeders\RolePermissionSeeder::class);
        $this->call(\Database\Seeders\UserSeeder::class);
        $this->call(\Database\Seeders\StreamSeeder::class);
        $this->call(\Database\Seeders\ExamTypeSeeder::class);
        $this->call(\Database\Seeders\SubjectTopicSeeder::class);
        $this->call(\Database\Seeders\SubjectSeeder::class);
        $this->call(\Database\Seeders\TopicSeeder::class);
        $this->call(\Database\Seeders\QuestionSeeder_New::class);
        $this->call(\Database\Seeders\QuizSeeder::class);
        $this->call(\Database\Seeders\LessonSeeder::class);

        $this->command->info('Database seeding completed successfully! 🎉');
        $this->command->line('Test Credentials:');
        $this->command->line('Admin: admin@future-academy.com');
        $this->command->line('Teacher: okafor@future-academy.com');
        $this->command->line('Student: chioma.eze@student.com');
        $this->command->line('Password: password');
    }
}

