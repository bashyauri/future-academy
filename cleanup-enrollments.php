<?php
/**
 * Cleanup script to fix duplicate active enrollments
 * Preserves historical records by marking old ones as inactive
 * Run from Laravel tinker or as a command
 */

use App\Models\User;
use App\Models\Enrollment;

// Get student 21
$student = User::find(21);

if (!$student) {
    echo "Student 21 not found\n";
    exit;
}

// Get all active enrollments
$activeEnrollments = $student->enrollments()->where('is_active', true)->get();
echo "Active enrollments: " . $activeEnrollments->count() . "\n";
echo "Active subjects (via enrolledSubjects): " . $student->enrolledSubjects()->count() . "\n\n";

// Group by subject_id to find duplicates
$groupedBySubject = $activeEnrollments->groupBy('subject_id');

echo "Breakdown by subject:\n";
foreach ($groupedBySubject as $subjectId => $enrollments) {
    echo "Subject ID $subjectId: " . $enrollments->count() . " active enrollment(s)\n";

    if ($enrollments->count() > 1) {
        echo "  ⚠️  DUPLICATE FOUND! Keeping newest, marking others inactive...\n";

        // Sort by created_at, keep the newest, deactivate the rest
        $sorted = $enrollments->sortByDesc('created_at');
        foreach ($sorted->skip(1) as $enrollment) {
            echo "    - Deactivating enrollment ID {$enrollment->id} (created: {$enrollment->created_at})\n";
            $enrollment->update(['is_active' => false]);
        }
    }
}

// Final check
$student = User::find(21); // Refresh
$finalActive = $student->enrolledSubjects()->count();

echo "\n✅ Cleanup complete!\n";
echo "Active subjects now: " . $finalActive . "\n";

