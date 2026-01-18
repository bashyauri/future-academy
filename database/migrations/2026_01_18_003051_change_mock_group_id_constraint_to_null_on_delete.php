<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Drop the existing foreign key with cascadeOnDelete
            $table->dropForeign(['mock_group_id']);
        });

        Schema::table('questions', function (Blueprint $table) {
            // Recreate the foreign key with nullOnDelete instead
            $table->foreign('mock_group_id')
                ->references('id')
                ->on('mock_groups')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Drop the nullOnDelete foreign key
            $table->dropForeign(['mock_group_id']);
        });

        Schema::table('questions', function (Blueprint $table) {
            // Restore the cascadeOnDelete foreign key
            $table->foreign('mock_group_id')
                ->references('id')
                ->on('mock_groups')
                ->cascadeOnDelete();
        });
    }
};
