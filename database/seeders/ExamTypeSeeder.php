<?php

namespace Database\Seeders;

use App\Models\ExamType;
use Illuminate\Database\Seeder;

class ExamTypeSeeder extends Seeder
{
    public function run(): void
    {
        $examTypes = [
            [
                'name' => 'JAMB (UTME)',
                'slug' => 'jamb',
                'code' => 'JAMB',
                'description' => 'Joint Admissions and Matriculation Board - University entrance examination',
                'color' => '#3B82F6',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'WAEC (SSCE)',
                'slug' => 'waec',
                'code' => 'WAEC',
                'description' => 'West African Examinations Council - Senior School Certificate Examination',
                'color' => '#10B981',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'NECO (SSCE)',
                'slug' => 'neco',
                'code' => 'NECO',
                'description' => 'National Examinations Council - Senior School Certificate Examination',
                'color' => '#8B5CF6',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($examTypes as $examType) {
            ExamType::updateOrCreate(
                ['slug' => $examType['slug']],
                $examType
            );
        }
    }
}
