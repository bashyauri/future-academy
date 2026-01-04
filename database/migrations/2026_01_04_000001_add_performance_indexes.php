<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to questions table for faster filtering
        try {
            DB::statement('ALTER TABLE questions ADD INDEX idx_subject_id (subject_id)');
        } catch (\Exception $e) {
            // Index already exists
        }

        try {
            DB::statement('ALTER TABLE questions ADD INDEX idx_is_active (is_active)');
        } catch (\Exception $e) {
            // Index already exists
        }

        try {
            DB::statement('ALTER TABLE questions ADD INDEX idx_status (status)');
        } catch (\Exception $e) {
            // Index already exists
        }

        try {
            DB::statement('ALTER TABLE questions ADD INDEX idx_is_mock (is_mock)');
        } catch (\Exception $e) {
            // Index already exists
        }

        try {
            DB::statement('ALTER TABLE questions ADD INDEX idx_subject_active_status_mock (subject_id, is_active, status, is_mock)');
        } catch (\Exception $e) {
            // Index already exists
        }

        // Add indexes to user_answers table for faster lookups
        try {
            DB::statement('ALTER TABLE user_answers ADD INDEX idx_qa_attempt (quiz_attempt_id)');
        } catch (\Exception $e) {
            // Index already exists
        }

        try {
            DB::statement('ALTER TABLE user_answers ADD INDEX idx_question_id (question_id)');
        } catch (\Exception $e) {
            // Index already exists
        }

        try {
            DB::statement('ALTER TABLE user_answers ADD INDEX idx_user_id (user_id)');
        } catch (\Exception $e) {
            // Index already exists
        }

        try {
            DB::statement('ALTER TABLE user_answers ADD INDEX idx_qa_attempt_question (quiz_attempt_id, question_id)');
        } catch (\Exception $e) {
            // Index already exists
        }

        // Add indexes to quiz_attempts table
        try {
            DB::statement('ALTER TABLE quiz_attempts ADD INDEX idx_user_id (user_id)');
        } catch (\Exception $e) {
            // Index already exists
        }

        try {
            DB::statement('ALTER TABLE quiz_attempts ADD INDEX idx_exam_type_id (exam_type_id)');
        } catch (\Exception $e) {
            // Index already exists
        }

        try {
            DB::statement('ALTER TABLE quiz_attempts ADD INDEX idx_status (status)');
        } catch (\Exception $e) {
            // Index already exists
        }

        try {
            DB::statement('ALTER TABLE quiz_attempts ADD INDEX idx_user_status (user_id, status)');
        } catch (\Exception $e) {
            // Index already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['subject_id']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['status']);
            $table->dropIndex(['is_mock']);
            $table->dropIndex(['subject_id', 'is_active', 'status', 'is_mock']);
        });

        Schema::table('user_answers', function (Blueprint $table) {
            $table->dropIndex(['quiz_attempt_id']);
            $table->dropIndex(['question_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['quiz_attempt_id', 'question_id']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['exam_type_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'status']);
        });
    }
};
