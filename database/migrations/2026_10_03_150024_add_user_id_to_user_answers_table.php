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
        if (Schema::hasColumn('user_answers', 'user_id')) {
            return;
        }

        Schema::table('user_answers', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->index('user_id', 'user_answers_user_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('user_answers', 'user_id')) {
            return;
        }

        Schema::table('user_answers', function (Blueprint $table): void {
            $table->dropIndex('user_answers_user_id_idx');
            $table->dropColumn('user_id');
        });
    }
};
