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
                'name' => 'WAEC/NECO(SSCE)',
                'slug' => 'waec-neco-ssce',
                'code' => 'SSCE',
                'description' => 'Senior School Certificate Examination - West African Examinations Council (WAEC) and National Examinations Council (NECO)',
                'color' => '#10B981',
                'is_active' => true,
                'sort_order' => 2,
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
