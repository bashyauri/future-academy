<?php

use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use App\Services\LessonService;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

function createMilestone11Fixture(): array
{
    $user = User::factory()->create();

    $subject = Subject::forceCreate([
        'name' => 'Mathematics',
        'code' => 'MTH',
        'slug' => 'mathematics',
        'description' => 'Mathematics subject',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $lesson = Lesson::forceCreate([
        'title' => 'Introduction to Fractions',
        'description' => 'Learn the basics of fractions',
        'content' => '<p>Fraction content</p>',
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'video_type' => 'youtube',
        'thumbnail' => 'lessons/fractions.jpg',
        'subject_id' => $subject->id,
        'order' => 1,
        'duration_minutes' => 12,
        'is_free' => true,
        'status' => 'published',
        'published_at' => now()->subDay(),
        'created_by' => $user->id,
    ]);

    $question = Question::forceCreate([
        'question_text' => 'What is 1^2?',
        'explanation' => 'Because one squared is still one.',
        'subject_id' => $subject->id,
        'difficulty' => 'easy',
        'status' => 'approved',
        'is_active' => true,
        'is_mock' => false,
        'created_by' => $user->id,
    ]);

    Option::forceCreate([
        'question_id' => $question->id,
        'label' => 'A',
        'option_text' => '1',
        'is_correct' => true,
        'sort_order' => 1,
    ]);

    Option::forceCreate([
        'question_id' => $question->id,
        'label' => 'B',
        'option_text' => '2',
        'is_correct' => false,
        'sort_order' => 2,
    ]);

    $lesson->questions()->attach($question->id, ['order' => 1]);

    $quiz = Quiz::forceCreate([
        'title' => 'Fractions Quiz',
        'description' => 'Lesson quiz for fractions',
        'type' => 'lesson',
        'duration_minutes' => 10,
        'passing_score' => 50,
        'question_count' => 1,
        'subject_id' => $subject->id,
        'lesson_id' => $lesson->id,
        'subject_ids' => [$subject->id],
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'show_answers_after_submit' => true,
        'allow_review' => true,
        'show_explanations' => true,
        'max_attempts' => 3,
        'is_active' => true,
        'status' => 'published',
        'is_mock' => false,
        'is_free' => true,
        'published_at' => now()->subDay(),
        'available_from' => now()->subDay(),
        'available_until' => now()->addDay(),
        'created_by' => $user->id,
    ]);

    $quiz->questions()->attach($question->id);

    return compact('user', 'subject', 'lesson', 'question', 'quiz');
}

it('returns lesson details with aligned mobile payloads', function (): void {
    $fixture = createMilestone11Fixture();
    $service = app(LessonService::class);

    $details = $service->getLessonDetails($fixture['user'], $fixture['lesson']->id);

    expect($details['video_embed_url'])->not->toBeEmpty();
    expect($details['video_stream_url'])->toBeNull();
    expect($details['video_playback_url'])->toBeNull();
    expect($details['quiz'])->not->toBeNull();
    expect($details['quiz']['lesson_id'])->toBe($fixture['lesson']->id);
    expect($details['quiz']['user_stats']['can_attempt'])->toBeTrue();
    expect($details['quiz_completed'])->toBeFalse();
    expect($details['practice_questions'])->toHaveCount(1);
    expect($details['practice_questions'][0]['question_text_html'])->not->toBeEmpty();
    expect($details['practice_questions'][0]['explanation_html'])->not->toBeEmpty();
    expect($details['practice_questions'][0]['options'][0]['option_text_html'])->not->toBeEmpty();
});

it('requires the linked quiz to be completed before marking a lesson complete', function (): void {
    $fixture = createMilestone11Fixture();
    $service = app(LessonService::class);

    expect(fn (): mixed => $service->markAsCompleted($fixture['user'], $fixture['lesson']->id))
        ->toThrow(\Exception::class, 'Please complete the lesson quiz before marking this lesson as complete.');

    QuizAttempt::forceCreate([
        'user_id' => $fixture['user']->id,
        'quiz_id' => $fixture['quiz']->id,
        'subject_id' => $fixture['subject']->id,
        'status' => 'completed',
        'started_at' => now()->subMinutes(15),
        'completed_at' => now()->subMinutes(5),
        'time_taken_seconds' => 600,
        'total_questions' => 1,
        'correct_answers' => 1,
        'score_percentage' => 100,
        'passed' => true,
        'question_order' => [$fixture['question']->id],
    ]);

    $progress = $service->markAsCompleted($fixture['user'], $fixture['lesson']->id);

    expect($progress->is_completed)->toBeTrue();
    expect($progress->progress_percentage)->toBe(100);
    expect($progress->current_time_seconds)->toBe(720);
    expect($progress->time_spent_seconds)->toBe(720);
});

it('returns quiz details with lesson alignment and html payloads', function (): void {
    $fixture = createMilestone11Fixture();
    $service = app(QuizService::class);

    $details = $service->getQuizDetails($fixture['quiz']->id);

    expect($details['lesson_id'])->toBe($fixture['lesson']->id);
    expect($details['questions'])->toHaveCount(1);
    expect($details['questions'][0]['question_text_html'])->not->toBeEmpty();
    expect($details['questions'][0]['explanation_html'])->not->toBeEmpty();
    expect($details['questions'][0]['options'][0]['option_text_html'])->not->toBeEmpty();
});



