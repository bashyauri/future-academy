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
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->onDelete('cascade');
            $table->foreignId('quiz_id')->nullable()->constrained('quizzes')->onDelete('cascade');

            // Type: 'lesson', 'quiz', etc.
            $table->string('type')->default('lesson');

            // Progress tracking
            $table->boolean('is_completed')->default(false);
            $table->integer('progress_percentage')->default(0); // 0-100%
            $table->integer('time_spent_seconds')->default(0);

            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Metadata (for future extensibility)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'lesson_id']);
            $table->index(['user_id', 'quiz_id']);
            $table->index(['user_id', 'type']);
            $table->index(['is_completed', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};
