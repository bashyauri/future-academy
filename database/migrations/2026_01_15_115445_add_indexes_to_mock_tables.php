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
        // Add indexes to mock_groups table for better query performance
        Schema::table('mock_groups', function (Blueprint $table) {
            $table->index(['subject_id', 'exam_type_id', 'batch_number'], 'idx_mock_groups_lookup');
            $table->index(['subject_id', 'exam_type_id'], 'idx_mock_groups_subject_exam');
        });

        // Add indexes to questions table for mock group queries
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'mock_group_id')) {
                return; // Column might not exist yet
            }
            $table->index('mock_group_id', 'idx_questions_mock_group');
            $table->index(['is_mock', 'is_active', 'status'], 'idx_questions_mock_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mock_groups', function (Blueprint $table) {
            $table->dropIndex('idx_mock_groups_lookup');
            $table->dropIndex('idx_mock_groups_subject_exam');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('idx_questions_mock_group');
            $table->dropIndex('idx_questions_mock_status');        });
    }
};
