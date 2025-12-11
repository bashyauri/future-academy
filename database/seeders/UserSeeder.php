<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@future-academy.com'],
            [
                'name' => 'Administrator',
                'email' => 'admin@future-academy.com',
                'password' => Hash::make('password'),
                'account_type' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Create Teacher Users
        $teachers = [
            ['name' => 'Mr. Chukwu Okafor', 'email' => 'okafor@future-academy.com'],
            ['name' => 'Mrs. Adeyemi Johnson', 'email' => 'adeyemi@future-academy.com'],
            ['name' => 'Dr. Amara Nwankwo', 'email' => 'amara@future-academy.com'],
        ];

        foreach ($teachers as $teacherData) {
            $teacher = User::firstOrCreate(
                ['email' => $teacherData['email']],
                [
                    'name' => $teacherData['name'],
                    'email' => $teacherData['email'],
                    'password' => Hash::make('password'),
                    'account_type' => 'teacher',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $teacher->assignRole('teacher');
        }

        // Create Student Users with realistic Nigerian names
        $students = [
            // Science Stream
            ['name' => 'Chioma Eze', 'email' => 'chioma.eze@student.com', 'stream' => 'science'],
            ['name' => 'Tunde Oladele', 'email' => 'tunde.oladele@student.com', 'stream' => 'science'],
            ['name' => 'Amara Okonkwo', 'email' => 'amara.okonkwo@student.com', 'stream' => 'science'],
            ['name' => 'Obinna Nwankwo', 'email' => 'obinna.nwankwo@student.com', 'stream' => 'science'],
            ['name' => 'Zainab Musa', 'email' => 'zainab.musa@student.com', 'stream' => 'science'],
            // Commercial Stream
            ['name' => 'Deborah Oyedepo', 'email' => 'deborah.oyedepo@student.com', 'stream' => 'commercial'],
            ['name' => 'Favour Akaeze', 'email' => 'favour.akaeze@student.com', 'stream' => 'commercial'],
            ['name' => 'Seun Adepoju', 'email' => 'seun.adepoju@student.com', 'stream' => 'commercial'],
            ['name' => 'Kelechi Uba', 'email' => 'kelechi.uba@student.com', 'stream' => 'commercial'],
            // Arts Stream
            ['name' => 'Iyabo Adebayo', 'email' => 'iyabo.adebayo@student.com', 'stream' => 'arts'],
            ['name' => 'Emeka Obi', 'email' => 'emeka.obi@student.com', 'stream' => 'arts'],
            ['name' => 'Blessing Nkosi', 'email' => 'blessing.nkosi@student.com', 'stream' => 'arts'],
        ];

        foreach ($students as $studentData) {
            $student = User::firstOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
                    'email' => $studentData['email'],
                    'password' => Hash::make('password'),
                    'account_type' => 'student',
                    'stream' => $studentData['stream'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'has_completed_onboarding' => true,
                ]
            );
            $student->assignRole('student');
        }
    }
}
