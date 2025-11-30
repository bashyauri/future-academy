<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super-admin',
            'admin',
            'tutor',
            'student',
        ];

        foreach ($roles as $name) {
            Role::findOrCreate($name);
        }
    }
}
