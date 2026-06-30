<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class OnboardingController extends Controller
{
    /**
     * Complete the onboarding process.
     *
     * Expected payload:
     *   - stream: string (slug of the chosen stream)
     *   - exam_types: array of exam type IDs (optional)
     *   - subjects: array of subject IDs
     */
    public function complete(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'stream' => 'required|string',
            'exam_types' => 'sometimes|array|min:1',
            'exam_types.*' => 'integer',
            'subjects' => 'required|array|min:1',
            'subjects.*' => 'integer',
        ], [
            'stream.required' => 'Please select a stream.',
            'subjects.required' => 'Please select at least one subject.',
            'subjects.min' => 'Please select at least one subject.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update user preferences and mark onboarding as completed
        $user->update([
            'stream' => $request->input('stream'),
            'exam_types' => $request->input('exam_types', []),
            'selected_subjects' => $request->input('subjects'),
            'has_completed_onboarding' => true,
        ]);

        // Deactivate any previous enrollments
        $user->enrollments()->update(['is_active' => false]);

        // Create new enrollments for the selected subjects
        foreach ($request->input('subjects') as $subjectId) {
            $user->enrollments()->create([
                'subject_id' => $subjectId,
                'enrolled_at' => now(),
                'is_active' => true,
            ]);
        }

        // Dispatch an event similar to the Livewire flow (optional)
        event('student-enrollment-changed', ['studentId' => $user->id]);

        return response()->json([
            'message' => 'Onboarding completed successfully.',
            'user' => $user->fresh(),
        ]);
    }
}
?>
