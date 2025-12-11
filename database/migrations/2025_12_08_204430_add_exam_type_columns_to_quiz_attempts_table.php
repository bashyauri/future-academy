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
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignId('exam_type_id')->nullable()->after('quiz_id')->constrained('exam_types')->onDelete('set null');
            $table->integer('exam_year')->nullable()->after('exam_type_id');
            $table->integer('time_taken_seconds')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropForeignKey(['exam_type_id']);
            $table->dropColumn(['exam_type_id', 'exam_year', 'time_taken_seconds']);
        });
    }
};
