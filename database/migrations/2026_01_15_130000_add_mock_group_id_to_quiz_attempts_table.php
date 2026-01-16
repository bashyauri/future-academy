<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_attempts', 'mock_group_id')) {
                $table->unsignedBigInteger('mock_group_id')->nullable()->after('subject_id');
                $table->index('mock_group_id', 'quiz_attempts_mock_group_id_index');
                $table->foreign('mock_group_id')
                    ->references('id')->on('mock_groups')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('quiz_attempts', 'mock_group_id')) {
                $table->dropForeign(['mock_group_id']);
                $table->dropIndex('quiz_attempts_mock_group_id_index');
                $table->dropColumn('mock_group_id');
            }
        });
    }
};
