<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Handled by update_quizzes_table_for_quiz_engine migration
        // This migration is kept for reference only
        if (Schema::hasTable('quizzes')) {
            return;
        }

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['practice', 'timed', 'mock'])->default('practice');
            $table->integer('duration_minutes')->nullable()->comment('Duration in minutes for timed exams');
            $table->integer('passing_score')->default(50)->comment('Passing score percentage');
            $table->integer('question_count')->comment('Total number of questions in this quiz');

            // Question selection criteria
            $table->json('subject_ids')->nullable()->comment('Array of subject IDs to pull questions from');
            $table->json('topic_ids')->nullable()->comment('Array of topic IDs to pull questions from');
            $table->json('exam_type_ids')->nullable()->comment('Array of exam type IDs (e.g., JAMB, WAEC)');
            $table->json('difficulty_levels')->nullable()->comment('Array of difficulty levels');
            $table->json('years')->nullable()->comment('Array of years for mock exams (past questions)');

            // Randomization settings
            $table->boolean('randomize_questions')->default(true)->comment('Randomize question selection');
            $table->boolean('shuffle_questions')->default(true)->comment('Shuffle question order for each attempt');
            $table->boolean('shuffle_options')->default(true)->comment('Shuffle answer options');

            // Quiz settings
            $table->boolean('show_answers_after_submit')->default(true);
            $table->boolean('allow_review')->default(true)->comment('Allow students to review after completion');
            $table->boolean('show_explanations')->default(true);
            $table->integer('max_attempts')->nullable()->comment('Max attempts per student, null = unlimited');
            $table->boolean('is_active')->default(true);

            // Scheduling
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();

            // Metadata
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('type');
            $table->index('is_active');
            $table->index(['available_from', 'available_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
