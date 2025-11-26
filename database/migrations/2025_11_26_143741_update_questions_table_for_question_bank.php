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
        Schema::table('questions', function (Blueprint $table) {
            // Add new image fields
            $table->string('question_image')->nullable()->after('question_text');
            $table->string('explanation_image')->nullable()->after('explanation');

            // Change exam_type from string to foreign key
            $table->foreignId('exam_type_id')->nullable()->after('topic_id')->constrained()->cascadeOnDelete();

            // Add status and approval workflow
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('difficulty');
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');

            // Add active flag and usage tracking
            $table->boolean('is_active')->default(true)->after('rejection_reason');
            $table->integer('times_used')->default(0)->after('is_active');
        });

        // Drop old exam_type column after adding new exam_type_id
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('exam_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('exam_type')->after('topic_id');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn([
                'question_image',
                'explanation_image',
                'exam_type_id',
                'status',
                'created_by',
                'approved_by',
                'approved_at',
                'rejection_reason',
                'is_active',
                'times_used'
            ]);
        });
    }
};
