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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->text('question_text');
            $table->string('question_image')->nullable();
            $table->string('explanation')->nullable();
            $table->string('explanation_image')->nullable();

            // Relationships
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_type_id')->constrained()->cascadeOnDelete();

            // Metadata
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->year('year')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Approval tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('times_used')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['subject_id', 'topic_id', 'exam_type_id']);
            $table->index(['status', 'is_active']);
            $table->index('difficulty');
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
