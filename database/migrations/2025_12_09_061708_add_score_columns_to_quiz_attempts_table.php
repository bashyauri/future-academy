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
            // Add score columns if they don't exist
            if (!Schema::hasColumn('quiz_attempts', 'score')) {
                $table->integer('score')->default(0);
            }
            if (!Schema::hasColumn('quiz_attempts', 'percentage')) {
                $table->decimal('percentage', 5, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('quiz_attempts', 'score')) {
                $table->dropColumn('score');
            }
            if (Schema::hasColumn('quiz_attempts', 'percentage')) {
                $table->dropColumn('percentage');
            }
        });
    }
};
