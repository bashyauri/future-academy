<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        // Check if lessons already exist
        if (Lesson::count() > 0) {
            $this->command->info('Lessons already exist. Skipping LessonSeeder.');
            return;
        }

        $admin = User::where('email', 'super@admin.com')->first();

        if (!$admin) {
            $this->command->error('Super admin not found. Please run RolePermissionSeeder first.');
            return;
        }

        // Get Mathematics and English subjects
        $mathematics = Subject::where('name', 'Mathematics')->first();
        $english = Subject::where('name', 'English Language')->first();

        if (!$mathematics || !$english) {
            $this->command->error('Subjects not found. Please run SubjectTopicSeeder first.');
            return;
        }

        // Get topics
        $algebraTopic = Topic::where('subject_id', $mathematics->id)->where('slug', 'algebra')->first();
        $geometryTopic = Topic::where('subject_id', $mathematics->id)->where('slug', 'geometry')->first();
        $trigonometryTopic = Topic::where('subject_id', $mathematics->id)->where('slug', 'trigonometry')->first();

        $grammarTopic = Topic::where('subject_id', $english->id)->where('slug', 'grammar')->first();
        $comprehensionTopic = Topic::where('subject_id', $english->id)->where('slug', 'comprehension')->first();
        $essayTopic = Topic::where('subject_id', $english->id)->where('slug', 'essay-writing')->first();

        $this->command->info('Seeding Mathematics lessons...');
        $this->seedMathematicsLessons($mathematics, $algebraTopic, $geometryTopic, $trigonometryTopic, $admin);

        $this->command->info('Seeding English lessons...');
        $this->seedEnglishLessons($english, $grammarTopic, $comprehensionTopic, $essayTopic, $admin);

        $this->command->info('Lessons seeded successfully!');
    }

    protected function seedMathematicsLessons($subject, $algebraTopic, $geometryTopic, $trigonometryTopic, $admin)
    {
        $lessons = [
            // Algebra Lessons
            [
                'title' => 'Introduction to Algebra',
                'description' => 'Learn the fundamentals of algebraic expressions and equations',
                'content' => '<h2>What is Algebra?</h2><p>Algebra is a branch of mathematics that uses symbols and letters to represent numbers and quantities in formulas and equations.</p><h3>Key Concepts:</h3><ul><li>Variables and constants</li><li>Algebraic expressions</li><li>Solving simple equations</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=NybHckSEQBI',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $algebraTopic->id,
                'order' => 1,
                'duration_minutes' => 25,
                'is_free' => true,
                'status' => 'published',
                'published_at' => now()->subDays(10),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Linear Equations',
                'description' => 'Master solving linear equations with one and two variables',
                'content' => '<h2>Linear Equations</h2><p>A linear equation is an equation where the highest power of the variable is 1.</p><h3>Types:</h3><ul><li>One-variable equations: ax + b = c</li><li>Two-variable equations: ax + by = c</li><li>Simultaneous equations</li></ul><h3>Methods:</h3><ul><li>Substitution method</li><li>Elimination method</li><li>Graphical method</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=kgnVk9nU17c',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $algebraTopic->id,
                'order' => 2,
                'duration_minutes' => 30,
                'is_free' => false,
                'status' => 'published',
                'published_at' => now()->subDays(9),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Quadratic Equations',
                'description' => 'Understanding and solving quadratic equations using various methods',
                'content' => '<h2>Quadratic Equations</h2><p>A quadratic equation has the form ax² + bx + c = 0</p><h3>Solving Methods:</h3><ul><li>Factorization</li><li>Completing the square</li><li>Quadratic formula</li><li>Graphical method</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=i7idZfS8t8w',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $algebraTopic->id,
                'order' => 3,
                'duration_minutes' => 35,
                'is_free' => false,
                'status' => 'published',
                'published_at' => now()->subDays(8),
                'created_by' => $admin->id,
            ],

            // Geometry Lessons
            [
                'title' => 'Basic Geometric Shapes',
                'description' => 'Explore properties of triangles, circles, and polygons',
                'content' => '<h2>Geometric Shapes</h2><p>Learn about different shapes and their properties.</p><h3>Topics Covered:</h3><ul><li>Triangles: Types and properties</li><li>Circles: Radius, diameter, circumference</li><li>Quadrilaterals: Square, rectangle, parallelogram</li><li>Polygons: Regular and irregular</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=YB1h_Y7MsUw',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $geometryTopic->id,
                'order' => 4,
                'duration_minutes' => 28,
                'is_free' => true,
                'status' => 'published',
                'published_at' => now()->subDays(7),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Angles and Parallel Lines',
                'description' => 'Understanding angles, their types, and properties of parallel lines',
                'content' => '<h2>Angles</h2><p>Learn about different types of angles and their relationships.</p><h3>Key Concepts:</h3><ul><li>Acute, obtuse, right angles</li><li>Complementary and supplementary angles</li><li>Vertically opposite angles</li><li>Angles on parallel lines</li><li>Alternate and corresponding angles</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=_yJTyk-6ajE',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $geometryTopic->id,
                'order' => 5,
                'duration_minutes' => 32,
                'is_free' => false,
                'status' => 'published',
                'published_at' => now()->subDays(6),
                'created_by' => $admin->id,
            ],

            // Trigonometry Lessons
            [
                'title' => 'Introduction to Trigonometry',
                'description' => 'Learn basic trigonometric ratios: sine, cosine, and tangent',
                'content' => '<h2>Trigonometry Basics</h2><p>Trigonometry is the study of relationships between angles and sides of triangles.</p><h3>Basic Ratios:</h3><ul><li>Sin θ = Opposite/Hypotenuse</li><li>Cos θ = Adjacent/Hypotenuse</li><li>Tan θ = Opposite/Adjacent</li></ul><h3>Remember: SOHCAHTOA</h3>',
                'video_url' => 'https://www.youtube.com/watch?v=yBw67Fb31Cs',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $trigonometryTopic->id,
                'order' => 6,
                'duration_minutes' => 30,
                'is_free' => true,
                'status' => 'published',
                'published_at' => now()->subDays(5),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Trigonometric Identities',
                'description' => 'Master essential trigonometric identities and their applications',
                'content' => '<h2>Trigonometric Identities</h2><h3>Fundamental Identities:</h3><ul><li>sin²θ + cos²θ = 1</li><li>1 + tan²θ = sec²θ</li><li>1 + cot²θ = csc²θ</li></ul><h3>Angle Sum Formulas:</h3><ul><li>sin(A + B) = sinA cosB + cosA sinB</li><li>cos(A + B) = cosA cosB - sinA sinB</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=DgXR46HvzfY',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $trigonometryTopic->id,
                'order' => 7,
                'duration_minutes' => 40,
                'is_free' => false,
                'status' => 'published',
                'published_at' => now()->subDays(4),
                'created_by' => $admin->id,
            ],
        ];

        foreach ($lessons as $lesson) {
            Lesson::create($lesson);
        }
    }

    protected function seedEnglishLessons($subject, $grammarTopic, $comprehensionTopic, $essayTopic, $admin)
    {
        $lessons = [
            // Grammar Lessons
            [
                'title' => 'Parts of Speech',
                'description' => 'Understanding nouns, verbs, adjectives, and other parts of speech',
                'content' => '<h2>Parts of Speech</h2><p>The building blocks of English grammar.</p><h3>Main Categories:</h3><ul><li><strong>Nouns:</strong> Names of people, places, things</li><li><strong>Verbs:</strong> Action or state words</li><li><strong>Adjectives:</strong> Describe nouns</li><li><strong>Adverbs:</strong> Describe verbs, adjectives</li><li><strong>Pronouns:</strong> Replace nouns</li><li><strong>Prepositions:</strong> Show relationships</li><li><strong>Conjunctions:</strong> Connect words/phrases</li><li><strong>Interjections:</strong> Express emotions</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=5jKZ9KGtee0',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $grammarTopic->id,
                'order' => 1,
                'duration_minutes' => 22,
                'is_free' => true,
                'status' => 'published',
                'published_at' => now()->subDays(10),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Tenses and Time',
                'description' => 'Master present, past, and future tenses in English',
                'content' => '<h2>English Tenses</h2><h3>Present Tenses:</h3><ul><li>Simple Present</li><li>Present Continuous</li><li>Present Perfect</li><li>Present Perfect Continuous</li></ul><h3>Past Tenses:</h3><ul><li>Simple Past</li><li>Past Continuous</li><li>Past Perfect</li><li>Past Perfect Continuous</li></ul><h3>Future Tenses:</h3><ul><li>Simple Future (will/shall)</li><li>Future Continuous</li><li>Future Perfect</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=_p9cHPNZXZg',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $grammarTopic->id,
                'order' => 2,
                'duration_minutes' => 35,
                'is_free' => false,
                'status' => 'published',
                'published_at' => now()->subDays(9),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Subject-Verb Agreement',
                'description' => 'Learn the rules of subject-verb agreement in sentences',
                'content' => '<h2>Subject-Verb Agreement</h2><p>The verb must agree with its subject in number and person.</p><h3>Basic Rules:</h3><ul><li>Singular subjects take singular verbs</li><li>Plural subjects take plural verbs</li><li>Compound subjects joined by "and" take plural verbs</li><li>Subjects joined by "or" or "nor" follow the nearest subject</li></ul><h3>Special Cases:</h3><ul><li>Collective nouns</li><li>Indefinite pronouns</li><li>Titles and names</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=7v7OvCMPgGg',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $grammarTopic->id,
                'order' => 3,
                'duration_minutes' => 28,
                'is_free' => false,
                'status' => 'published',
                'published_at' => now()->subDays(8),
                'created_by' => $admin->id,
            ],

            // Comprehension Lessons
            [
                'title' => 'Reading Comprehension Strategies',
                'description' => 'Effective techniques for understanding and analyzing texts',
                'content' => '<h2>Reading Comprehension</h2><h3>Before Reading:</h3><ul><li>Preview the text</li><li>Look at headings and images</li><li>Predict what the text is about</li></ul><h3>During Reading:</h3><ul><li>Read actively</li><li>Highlight key points</li><li>Ask questions</li><li>Make connections</li></ul><h3>After Reading:</h3><ul><li>Summarize main ideas</li><li>Answer comprehension questions</li><li>Reflect on the content</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=o7_LBkyHZkc',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $comprehensionTopic->id,
                'order' => 4,
                'duration_minutes' => 25,
                'is_free' => true,
                'status' => 'published',
                'published_at' => now()->subDays(7),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Identifying Main Ideas and Details',
                'description' => 'Learn to distinguish between main ideas and supporting details',
                'content' => '<h2>Main Ideas vs Supporting Details</h2><h3>Main Idea:</h3><p>The central point or message of a passage.</p><ul><li>Usually found in topic sentence</li><li>Answers "What is this about?"</li><li>General and broad</li></ul><h3>Supporting Details:</h3><ul><li>Facts, examples, descriptions</li><li>Support or explain the main idea</li><li>Specific and narrow</li><li>Answer who, what, when, where, why, how</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=u6f8lB3H_70',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $comprehensionTopic->id,
                'order' => 5,
                'duration_minutes' => 30,
                'is_free' => false,
                'status' => 'published',
                'published_at' => now()->subDays(6),
                'created_by' => $admin->id,
            ],

            // Essay Writing Lessons
            [
                'title' => 'Essay Structure and Organization',
                'description' => 'Learn the essential components of a well-structured essay',
                'content' => '<h2>Essay Structure</h2><h3>Introduction:</h3><ul><li>Hook: Grab reader\'s attention</li><li>Background information</li><li>Thesis statement</li></ul><h3>Body Paragraphs:</h3><ul><li>Topic sentence</li><li>Supporting evidence</li><li>Analysis and explanation</li><li>Transition to next paragraph</li></ul><h3>Conclusion:</h3><ul><li>Restate thesis</li><li>Summarize main points</li><li>Final thought or call to action</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=AX3Bsxfgco4',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $essayTopic->id,
                'order' => 6,
                'duration_minutes' => 32,
                'is_free' => true,
                'status' => 'published',
                'published_at' => now()->subDays(5),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Narrative Writing',
                'description' => 'Master the art of storytelling through narrative essays',
                'content' => '<h2>Narrative Writing</h2><p>Tell a story with a clear beginning, middle, and end.</p><h3>Key Elements:</h3><ul><li><strong>Plot:</strong> Sequence of events</li><li><strong>Characters:</strong> People in the story</li><li><strong>Setting:</strong> Time and place</li><li><strong>Conflict:</strong> Problem or challenge</li><li><strong>Theme:</strong> Central message</li></ul><h3>Tips:</h3><ul><li>Use descriptive language</li><li>Show, don\'t tell</li><li>Include dialogue</li><li>Create vivid imagery</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=M_3aJnEG6C0',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $essayTopic->id,
                'order' => 7,
                'duration_minutes' => 38,
                'is_free' => false,
                'status' => 'published',
                'published_at' => now()->subDays(4),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Argumentative Writing',
                'description' => 'Build strong arguments and persuade your readers effectively',
                'content' => '<h2>Argumentative Essays</h2><p>Present a claim and support it with evidence and reasoning.</p><h3>Components:</h3><ul><li><strong>Claim:</strong> Your position on the issue</li><li><strong>Evidence:</strong> Facts, statistics, examples</li><li><strong>Reasoning:</strong> Explain how evidence supports claim</li><li><strong>Counterargument:</strong> Address opposing views</li><li><strong>Rebuttal:</strong> Refute counterarguments</li></ul><h3>Persuasive Techniques:</h3><ul><li>Logos (logic)</li><li>Ethos (credibility)</li><li>Pathos (emotion)</li></ul>',
                'video_url' => 'https://www.youtube.com/watch?v=qdFGtP3GX5E',
                'video_type' => 'youtube',
                'subject_id' => $subject->id,
                'topic_id' => $essayTopic->id,
                'order' => 8,
                'duration_minutes' => 42,
                'is_free' => false,
                'status' => 'published',
                'published_at' => now()->subDays(3),
                'created_by' => $admin->id,
            ],
        ];

        foreach ($lessons as $lesson) {
            Lesson::create($lesson);
        }
    }
}
