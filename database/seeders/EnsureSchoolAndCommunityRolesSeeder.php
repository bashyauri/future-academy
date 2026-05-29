<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class EnsureSchoolAndCommunityRolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['school', 'community'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
