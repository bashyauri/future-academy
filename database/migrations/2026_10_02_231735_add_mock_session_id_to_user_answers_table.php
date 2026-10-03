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
        Schema::table('user_answers', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_answers', 'mock_session_id')) {
                $table->unsignedBigInteger('mock_session_id')->nullable()->after('quiz_attempt_id');
                $table->index('mock_session_id', 'user_answers_mock_session_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_answers', function (Blueprint $table): void {
            if (Schema::hasColumn('user_answers', 'mock_session_id')) {
                $table->dropIndex('user_answers_mock_session_idx');
                $table->dropColumn('mock_session_id');
            }
        });
    }
};
