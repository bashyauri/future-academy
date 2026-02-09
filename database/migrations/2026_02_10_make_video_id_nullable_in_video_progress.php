<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_progress', function (Blueprint $table) {
            // Drop foreign key by name
            $table->dropForeign(['video_id']);

            // Make video_id nullable since we're tracking by lesson_id now
            $table->unsignedBigInteger('video_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('video_progress', function (Blueprint $table) {
            // Restore foreign key
            $table->unsignedBigInteger('video_id')->change();
            $table->foreign('video_id')->references('id')->on('videos')->onDelete('cascade');
        });
    }
};
