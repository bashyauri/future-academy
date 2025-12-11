<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topics = [
            'mathematics' => [
                'Algebra',
                'Geometry',
                'Trigonometry',
                'Calculus',
                'Statistics',
            ],
            'english-language' => [
                'Grammar',
                'Comprehension',
                'Essay Writing',
                'Vocabulary',
                'Literature',
            ],
            'physics' => [
                'Mechanics',
                'Thermodynamics',
                'Waves and Optics',
                'Electricity and Magnetism',
                'Modern Physics',
            ],
            'chemistry' => [
                'Atomic Structure',
                'Chemical Bonding',
                'States of Matter',
                'Chemical Reactions',
                'Organic Chemistry',
            ],
            'biology' => [
                'Cell Biology',
                'Genetics',
                'Ecology',
                'Human Anatomy',
                'Plant Biology',
            ],
        ];

        foreach ($topics as $subjectSlug => $topicNames) {
            $subject = Subject::where('slug', $subjectSlug)->first();
            
            if ($subject) {
                foreach ($topicNames as $index => $topicName) {
                    Topic::updateOrCreate(
                        ['subject_id' => $subject->id, 'slug' => Str::slug($topicName)],
                        [
                            'name' => $topicName,
                            'slug' => Str::slug($topicName),
                            'subject_id' => $subject->id,
                            'description' => "Learn about {$topicName} in {$subject->name}",
                            'sort_order' => $index + 1,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        $this->command->info('Topics seeded successfully!');
    }
}
