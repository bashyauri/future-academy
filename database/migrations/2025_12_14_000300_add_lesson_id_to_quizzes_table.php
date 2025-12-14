<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'lesson_id')) {
                $table->foreignId('lesson_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete()
                    ->after('subject_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'lesson_id')) {
                $table->dropConstrainedForeignId('lesson_id');
            }
        });
    }
};
