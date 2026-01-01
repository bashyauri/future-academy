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
        Schema::table('exam_types', function (Blueprint $table) {
            $table->enum('exam_format', ['jamb', 'ssce', 'default'])->default('default')->after('name');
        });

        // Set exam format by ID (more reliable than name matching)
        // ID 1 = JAMB, ID 3 = SSCE
        DB::table('exam_types')->where('id', 1)->update(['exam_format' => 'jamb']);
        DB::table('exam_types')->where('id', 3)->update(['exam_format' => 'ssce']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_types', function (Blueprint $table) {
            $table->dropColumn('exam_format');
        });
    }
};
