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
            'upload questions',
            'manage questions',
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
            'super-admin' => $permissions, // super-admin gets everything
            'admin' => [
                'manage users',
                'manage questions',
                'view stats',
                'approve guardians',
                'manage subscriptions',
                'manage videos',
            ],
            'teacher' => [
                'upload questions',
                'manage questions',
            ],
            'uploader' => [
                'upload questions',
            ],
            'guardian' => [],
            'student' => [],
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
