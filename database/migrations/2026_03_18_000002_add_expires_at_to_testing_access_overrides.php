<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('testing_access_expires_at')->nullable()->after('testing_access_all_lessons');
        });

        Schema::table('lesson_user_access_overrides', function (Blueprint $table) {
            $table->dateTime('expires_at')->nullable()->after('lesson_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_user_access_overrides', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('testing_access_expires_at');
        });
    }
};
