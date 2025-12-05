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
        $this->call(\Database\Seeders\SubjectTopicSeeder::class);
        $this->call(\Database\Seeders\QuestionSeeder::class);
        $this->call(\Database\Seeders\QuizSeeder::class);
        $this->call(\Database\Seeders\LessonSeeder::class);


        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
