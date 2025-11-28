<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions
        $permissions = [
            // User Management
            'manage users',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'approve guardians',

            // Academic Structure
            'manage academics',
            'manage subjects',
            'manage topics',
            'manage exam types',

            // Question Management
            'view questions',
            'create questions',
            'manage questions', // edit/update existing
            'upload questions', // simple single question upload
            'import questions', // bulk import (CSV/Excel)
            'approve questions',
            'delete questions',

            // Quiz/Assessment Management
            'view quizzes',
            'create quizzes',
            'edit quizzes',
            'delete quizzes',
            'publish quizzes',
            'manage quiz attempts',

            // Lesson Management
            'view lessons',
            'create lessons',
            'edit lessons',
            'delete lessons',
            'publish lessons',
            'manage lessons',

            // Video Management
            'manage videos',
            'manage video categories',
            'upload videos',

            // Progress & Analytics
            'view reports',
            'view stats',
            'view user progress',
            'manage user progress',
            'view analytics',

            // Role & Permission Management
            'manage roles',
            'manage permissions',

            // Subscription Management
            'manage subscriptions',
            'view subscriptions',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Roles
        $roles = [
            'super-admin' => $permissions, // full access

            'admin' => [
                // User Management
                'manage users',
                'view users',
                'create users',
                'edit users',
                'delete users',
                'approve guardians',

                // Academic Structure
                'manage academics',
                'manage subjects',
                'manage topics',
                'manage exam types',

                // Question Management
                'view questions',
                'create questions',
                'manage questions',
                'import questions',
                'approve questions',
                'delete questions',

                // Quiz Management
                'view quizzes',
                'create quizzes',
                'edit quizzes',
                'delete quizzes',
                'publish quizzes',
                'manage quiz attempts',

                // Lesson Management
                'view lessons',
                'create lessons',
                'edit lessons',
                'delete lessons',
                'publish lessons',
                'manage lessons',

                // Video Management
                'manage videos',
                'manage video categories',
                'upload videos',

                // Analytics & Reports
                'view reports',
                'view stats',
                'view user progress',
                'manage user progress',
                'view analytics',

                // Subscriptions
                'manage subscriptions',
                'view subscriptions',
            ],

            'teacher' => [
                // Question Management
                'view questions',
                'create questions',
                'manage questions',
                'upload questions',
                'import questions',

                // Quiz Management
                'view quizzes',
                'create quizzes',
                'edit quizzes',
                'publish quizzes',

                // Lesson Management
                'view lessons',
                'create lessons',
                'edit lessons',
                'manage lessons',

                // Video Management
                'manage videos',
                'upload videos',

                // Analytics
                'view reports',
                'view stats',
                'view user progress',
                'view analytics',

                // Academic Structure (view only)
                'manage academics',
            ],

            'uploader' => [
                'upload questions',
                'import questions',
                'upload videos',
                'view questions',
                'view lessons',
            ],

            'guardian' => [
                'view user progress', // view their children's progress
                'view subscriptions', // view their subscriptions
            ],

            'student' => [
                // front-end only, no panel permissions
            ],
        ];

        foreach ($roles as $roleName => $rolePerms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePerms);
        }

        // Create default super-admin user if not exists
        $adminEmail = config('lms.default_super_admin_email', 'super@admin.com');
        $super = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Super Admin',
                'phone' => null,
                'password' => bcrypt('password'),
                'account_type' => 'super-admin',
                'is_active' => true,
            ]
        );

        $super->assignRole('super-admin');

        $this->command->info('Roles, permissions and default super-admin created.');
    }
}
