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
        Schema::table('subjects', function (Blueprint $table) {
            // Drop old exam_type string column if exists
            if (Schema::hasColumn('subjects', 'exam_type')) {
                $table->dropColumn('exam_type');
            }

            // Add new columns
            $table->text('description')->nullable()->after('slug');
            $table->string('icon')->nullable()->after('description'); // emoji or icon class
            $table->string('color', 7)->default('#3B82F6')->after('icon');
            $table->boolean('is_active')->default(true)->after('color');
            $table->integer('sort_order')->default(0)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['description', 'icon', 'color', 'is_active', 'sort_order']);
            $table->string('exam_type')->nullable();
        });
    }
};
