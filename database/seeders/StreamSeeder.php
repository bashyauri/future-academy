<?php

namespace Database\Seeders;

use App\Models\Stream;
use Illuminate\Database\Seeder;

class StreamSeeder extends Seeder
{
    public function run(): void
    {
        $streams = [
            [
                'name' => 'Science',
                'slug' => 'science',
                'description' => 'For students pursuing science-related fields including Medicine, Engineering, and Pure Sciences',
                'icon' => '🔬',
                'color' => '#3B82F6',
                'default_subjects' => ['Mathematics', 'Physics', 'Chemistry', 'Biology', 'English Language'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Arts',
                'slug' => 'arts',
                'description' => 'For students interested in Literature, Languages, Arts, and Humanities',
                'icon' => '🎨',
                'color' => '#8B5CF6',
                'default_subjects' => ['Literature in English', 'Government', 'CRK/IRK', 'History', 'English Language'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Social Sciences',
                'slug' => 'social-sciences',
                'description' => 'For students pursuing Economics, Accounting, Business Administration, and Social Studies',
                'icon' => '💼',
                'color' => '#10B981',
                'default_subjects' => ['Economics', 'Commerce', 'Accounting', 'Government', 'English Language'],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($streams as $stream) {
            Stream::updateOrCreate(
                ['slug' => $stream['slug']],
                $stream
            );
        }
    }
}
