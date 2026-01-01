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
        Schema::table('user_answers', function (Blueprint $table) {
            // Speeds up lookups by attempt + question when saving/reading answers
            $table->index(['quiz_attempt_id', 'question_id'], 'user_answers_quiz_attempt_question_idx');

            // Optional: fast lookups by attempt + option if needed for analytics/reporting
            $table->index(['quiz_attempt_id', 'option_id'], 'user_answers_quiz_attempt_option_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->dropIndex('user_answers_quiz_attempt_question_idx');
            $table->dropIndex('user_answers_quiz_attempt_option_idx');
        });
    }
};
