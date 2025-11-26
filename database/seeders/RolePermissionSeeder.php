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
            'manage users',
            'manage academics',
            'create questions',
            'manage questions', // edit/update existing
            'upload questions', // simple single question upload
            'import questions', // bulk import (CSV/Excel)
            'approve questions',
            'delete questions',
            'view reports',
            'view stats',
            'approve guardians',
            'manage subscriptions',
            'manage videos',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Roles
        $roles = [
            'super-admin' => $permissions, // full access
            'admin' => [
                'manage users',
                'manage academics',
                'create questions',
                'manage questions',
                'import questions',
                'approve questions',
                'delete questions',
                'view reports',
                'view stats',
                'manage subscriptions',
                'manage videos',
            ],
            'teacher' => [
                'create questions',
                'manage questions',
                'upload questions',
                'import questions',
                'view reports',
            ],
            'uploader' => [
                'upload questions',
                'import questions',
            ],
            'guardian' => [
                // intentionally minimal; expand later
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
