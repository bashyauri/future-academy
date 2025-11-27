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
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('attempt_number')->default(1);
            
            // Timing
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent_seconds')->nullable()->comment('Actual time spent in seconds');
            
            // Scoring
            $table->integer('total_questions')->default(0);
            $table->integer('answered_questions')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->decimal('score_percentage', 5, 2)->nullable();
            $table->boolean('passed')->default(false);
            
            // Status
            $table->enum('status', ['in_progress', 'completed', 'timed_out', 'abandoned'])->default('in_progress');
            
            // Snapshot of questions for this attempt (JSON array of question IDs in order shown)
            $table->json('question_order')->nullable()->comment('Array of question IDs in the order shown');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'quiz_id']);
            $table->index('status');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};