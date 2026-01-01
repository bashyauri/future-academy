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
        Schema::create('mock_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_type_id')->constrained();
            $table->json('subject_ids'); // Array of selected subject IDs
            $table->json('questions_per_subject'); // {"17": 60, "18": 50}
            $table->integer('time_limit'); // Minutes
            $table->integer('selected_year')->nullable();
            $table->boolean('shuffle')->default(false);
            $table->enum('status', ['active', 'completed', 'expired'])->default('active');
            $table->timestamp('expires_at')->nullable(); // Auto-expire after 24 hours
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mock_sessions');
    }
};
