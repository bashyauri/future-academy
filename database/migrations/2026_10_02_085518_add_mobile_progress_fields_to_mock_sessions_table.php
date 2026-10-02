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
        Schema::table('mock_sessions', function (Blueprint $table) {
            $table->foreignId('quiz_attempt_id')->nullable()->after('exam_type_id')->constrained()->nullOnDelete();
            $table->foreignId('mock_group_id')->nullable()->after('quiz_attempt_id')->constrained()->nullOnDelete();
            $table->json('option_order')->nullable()->after('questions_per_subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mock_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mock_group_id');
            $table->dropConstrainedForeignId('quiz_attempt_id');
            $table->dropColumn('option_order');
        });
    }
};
