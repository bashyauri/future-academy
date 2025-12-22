<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ExamType;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get WAEC and NECO exam types
        $waec = ExamType::where('slug', 'waec')->first();
        $neco = ExamType::where('slug', 'neco')->first();

        if ($waec && $neco) {
            // Update NECO to be WAEC/NECO(SSCE)
            $neco->update([
                'name' => 'WAEC/NECO(SSCE)',
                'slug' => 'waec-neco-ssce',
                'code' => 'SSCE',
                'description' => 'Senior School Certificate Examination - West African Examinations Council (WAEC) and National Examinations Council (NECO)',
                'color' => '#10B981',
                'sort_order' => 2,
            ]);

            // Update all questions that use WAEC to use the merged NECO
            DB::table('questions')
                ->where('exam_type_id', $waec->id)
                ->update(['exam_type_id' => $neco->id]);

            // Handle subject-exam_type relationships - delete WAEC relationships first to avoid duplicates
            DB::table('exam_type_subject')
                ->where('exam_type_id', $waec->id)
                ->delete();

            // Update quiz relationships if they exist
            if (Schema::hasTable('quiz_exam_type')) {
                DB::table('quiz_exam_type')
                    ->where('exam_type_id', $waec->id)
                    ->delete(); // Delete to avoid duplicates
            }

            // Update quiz attempts if they exist
            if (Schema::hasTable('quiz_attempts')) {
                DB::table('quiz_attempts')
                    ->where('exam_type_id', $waec->id)
                    ->update(['exam_type_id' => $neco->id]);
            }

            // Delete WAEC exam type
            $waec->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the merged exam type
        $merged = ExamType::where('slug', 'waec-neco-ssce')->first();

        if ($merged) {
            // Recreate WAEC
            $waec = ExamType::create([
                'name' => 'WAEC (SSCE)',
                'slug' => 'waec',
                'code' => 'WAEC',
                'description' => 'West African Examinations Council - Senior School Certificate Examination',
                'color' => '#10B981',
                'is_active' => true,
                'sort_order' => 2,
            ]);

            // Revert merged exam type back to NECO
            $merged->update([
                'name' => 'NECO (SSCE)',
                'slug' => 'neco',
                'code' => 'NECO',
                'description' => 'National Examinations Council - Senior School Certificate Examination',
                'color' => '#8B5CF6',
                'sort_order' => 3,
            ]);
        }
    }
};
