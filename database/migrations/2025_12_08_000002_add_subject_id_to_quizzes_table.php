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
        Schema::table('quizzes', function (Blueprint $table) {
            // Add subject_id if it doesn't exist
            if (!Schema::hasColumn('quizzes', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete()->after('question_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeignIdFor('subject');
            $table->dropColumn('subject_id');
        });
    }
};
