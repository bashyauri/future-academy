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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g "WAEC Math Practice 1"
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('exam_type'); // WAEC, NECO
            $table->integer('duration'); // Minutes
            $table->integer('total_questions');
            $table->enum('mode', ['practice', 'timed', 'past-questions']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};