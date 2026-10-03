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
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            if (! Schema::hasColumn('quiz_attempts', 'attempt_number')) {
                $table->integer('attempt_number')->nullable()->after('exam_year');
            }

            if (! Schema::hasColumn('quiz_attempts', 'exam_type_id')) {
                $table->unsignedBigInteger('exam_type_id')->nullable()->after('quiz_id');
            }

            if (! Schema::hasColumn('quiz_attempts', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('exam_type_id');
            }

            if (! Schema::hasColumn('quiz_attempts', 'mock_group_id')) {
                $table->unsignedBigInteger('mock_group_id')->nullable()->after('subject_id');
            }

            if (! Schema::hasColumn('quiz_attempts', 'time_spent_seconds')) {
                $table->integer('time_spent_seconds')->default(0)->after('completed_at');
            }

            if (! Schema::hasColumn('quiz_attempts', 'time_taken_seconds')) {
                $table->integer('time_taken_seconds')->nullable()->after('time_spent_seconds');
            }

            if (! Schema::hasColumn('quiz_attempts', 'answered_questions')) {
                $table->integer('answered_questions')->default(0)->after('total_questions');
            }

            if (! Schema::hasColumn('quiz_attempts', 'score')) {
                $table->integer('score')->default(0)->after('correct_answers');
            }

            if (! Schema::hasColumn('quiz_attempts', 'percentage')) {
                $table->decimal('percentage', 5, 2)->default(0)->after('score');
            }

            if (! Schema::hasColumn('quiz_attempts', 'passed')) {
                $table->boolean('passed')->default(false)->after('score_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $columns = ['attempt_number', 'exam_type_id', 'subject_id', 'mock_group_id', 'time_spent_seconds', 'time_taken_seconds', 'answered_questions', 'score', 'percentage', 'passed'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('quiz_attempts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
