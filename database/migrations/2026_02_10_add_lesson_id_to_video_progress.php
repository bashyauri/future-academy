<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_progress', function (Blueprint $table) {
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->onDelete('cascade');
            $table->index(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::table('video_progress', function (Blueprint $table) {
            $table->dropForeignIdFor('Lesson', 'lesson_id');
            $table->dropIndex(['user_id', 'lesson_id']);
            $table->dropColumn('lesson_id');
        });
    }
};
