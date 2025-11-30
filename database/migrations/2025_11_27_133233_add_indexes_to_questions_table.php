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
        Schema::table('questions', function (Blueprint $table) {
            // Composite index for common query filters
            $table->index(['status', 'is_active', 'subject_id', 'difficulty'], 'idx_questions_search');

            // Individual indexes for frequently searched columns
            $table->index('exam_type_id', 'idx_exam_type');
            $table->index('topic_id', 'idx_topic');
            $table->index('year', 'idx_year');

            // Fulltext index for question text search (only for MySQL)
            if (config('database.default') !== 'sqlite') {
                $table->fullText('question_text', 'idx_question_text_fulltext');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Drop indexes in reverse order
            if (config('database.default') !== 'sqlite') {
                $table->dropFullText('idx_question_text_fulltext');
            }
            $table->dropIndex('idx_year');
            $table->dropIndex('idx_topic');
            $table->dropIndex('idx_exam_type');
            $table->dropIndex('idx_questions_search');
        });
    }
};
