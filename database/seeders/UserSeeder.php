<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'account_type' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Create teacher users
        for ($i = 1; $i <= 3; $i++) {
            $teacher = User::firstOrCreate(
                ['email' => "teacher{$i}@example.com"],
                [
                    'name' => "Teacher {$i}",
                    'email' => "teacher{$i}@example.com",
                    'password' => Hash::make('password'),
                    'account_type' => 'teacher',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $teacher->assignRole('teacher');
        }

        // Create student users
        for ($i = 1; $i <= 10; $i++) {
            $student = User::firstOrCreate(
                ['email' => "student{$i}@example.com"],
                [
                    'name' => "Student {$i}",
                    'email' => "student{$i}@example.com",
                    'password' => Hash::make('password'),
                    'account_type' => 'student',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $student->assignRole('student');
        }
    }
}
