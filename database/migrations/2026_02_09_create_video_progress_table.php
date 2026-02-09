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
        Schema::create('video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('video_id')->nullable()->constrained('videos')->onDelete('cascade');

            // Alternative: track by lesson instead of video
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->onDelete('cascade');

            // Video watching metrics
            $table->integer('watch_time')->default(0); // in seconds
            $table->integer('percentage')->default(0); // 0-100% watched
            $table->boolean('completed')->default(false); // true when >= 90% watched

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'video_id']);
            $table->index(['user_id', 'lesson_id']);
            $table->unique(['user_id', 'video_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_progress');
    }
};
