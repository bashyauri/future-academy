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
        Schema::table('lessons', function (Blueprint $table) {
            // Add video processing status tracking
            $table->string('video_status')->default('pending')
                ->after('video_type')
                ->comment('pending, processing, ready, failed');

            // Track when video processing completed
            $table->timestamp('video_processed_at')
                ->nullable()
                ->after('video_status')
                ->comment('When Cloudinary finished transcoding');

            // Index for finding unprocessed videos
            $table->index(['video_type', 'video_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['video_status', 'video_processed_at']);
            $table->dropIndex(['lessons_video_type_video_status_index']);
        });
    }
};
