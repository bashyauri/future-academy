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
            // Store exact playback position for resume functionality
            $table->integer('current_time')->default(0)->after('percentage')->comment('Current playback position in seconds');
            // Store Bunny webhook event data for analytics integration
            $table->json('bunny_watch_data')->nullable()->after('completed')->comment('Bunny webhook event data and analytics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_progress', function (Blueprint $table) {
            //
        });
    }
};
