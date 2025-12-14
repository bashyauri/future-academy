<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            // Enforce one quiz per lesson (allows multiple nulls)
            $table->unique('lesson_id', 'quizzes_lesson_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropUnique('quizzes_lesson_id_unique');
        });
    }
};
