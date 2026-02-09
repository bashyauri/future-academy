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
        Schema::create('video_analytics', function (Blueprint $table): void {
            $table->id();

            // Video reference
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->string('bunny_video_id')->nullable(); // Bunny Stream video ID

            // Analytics from Bunny
            $table->integer('total_views')->default(0);
            $table->integer('total_watch_time')->default(0); // in seconds
            $table->decimal('average_watch_time', 10, 2)->default(0);
            $table->integer('unique_viewers')->default(0);

            // Engagement metrics
            $table->decimal('completion_rate', 5, 2)->default(0); // 0-100%
            $table->integer('average_bitrate')->nullable(); // in kbps
            $table->string('top_country')->nullable();
            $table->string('top_device')->nullable();

            // Last synced
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('lesson_id');
            $table->index('bunny_video_id');
            $table->index('last_synced_at');
        })->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_analytics');
    }
};
