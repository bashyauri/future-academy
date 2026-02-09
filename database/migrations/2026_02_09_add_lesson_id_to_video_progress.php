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
        Schema::table('video_progress', function (Blueprint $table) {
            // Add lesson_id column if it doesn't exist
            if (!Schema::hasColumn('video_progress', 'lesson_id')) {
                $table->foreignId('lesson_id')->nullable()->after('video_id')->constrained('lessons')->onDelete('cascade');

                // Add index for lesson_id
                $table->index(['user_id', 'lesson_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_progress', function (Blueprint $table) {
            $table->dropForeignIdFor('Lesson');
            $table->dropIndex(['user_id', 'lesson_id']);
            $table->dropColumn('lesson_id');
        });
    }
};
