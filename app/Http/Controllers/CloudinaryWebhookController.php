<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Services\CloudinaryUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Livewire\Livewire;

/**
 * Handles webhooks from Cloudinary when video processing completes.
 */
class CloudinaryWebhookController extends Controller
{
    /**
     * Handle Cloudinary webhook for video processing completion.
     *
     * Cloudinary sends notifications when:
     * - Video upload completes
     * - Video transcoding finishes
     * - Processing fails
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            // Validate webhook signature
            $uploadService = new CloudinaryUploadService();
            if (!$uploadService->validateWebhookSignature(
                $payload,
                $request->header('X-Cldnry-Signature', '')
            )) {
                \Log::warning('Invalid Cloudinary webhook signature', $payload);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $eventType = $payload['notification_type'] ?? null;
            $publicId = $payload['public_id'] ?? null;
            $status = $payload['status'] ?? null;

            if (!$publicId) {
                return response()->json(['error' => 'No public_id'], 400);
            }

            // Handle different event types
            match ($eventType) {
                'upload_success' => $this->handleUploadSuccess($publicId, $payload),
                'resource_ready' => $this->handleResourceReady($publicId, $payload),
                'error' => $this->handleError($publicId, $payload),
                default => \Log::info('Unhandled webhook event', ['type' => $eventType]),
            };

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            \Log::error('Cloudinary webhook error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle successful upload.
     */
    private function handleUploadSuccess(string $publicId, array $payload): void
    {
        \Log::info('Video uploaded to Cloudinary', [
            'public_id' => $publicId,
            'bytes' => $payload['bytes'] ?? 0,
            'duration' => $payload['duration'] ?? 0,
        ]);

        // Video is uploaded but still transcoding
        // We'll update when resource_ready fires
    }

    /**
     * Handle resource ready (transcoding complete).
     */
    private function handleResourceReady(string $publicId, array $payload): void
    {
        \Log::info('Video transcoding complete', [
            'public_id' => $publicId,
            'format' => $payload['format'] ?? 'unknown',
            'duration' => $payload['duration'] ?? 0,
        ]);

        // Update lesson with completed status
        $this->updateLessonVideoStatus($publicId, 'ready');
    }

    /**
     * Handle processing errors.
     */
    private function handleError(string $publicId, array $payload): void
    {
        \Log::error('Cloudinary video processing error', [
            'public_id' => $publicId,
            'error' => $payload['error'] ?? 'unknown',
        ]);

        $this->updateLessonVideoStatus($publicId, 'failed');
    }

    /**
     * Update lesson video processing status.
     */
    private function updateLessonVideoStatus(string $publicId, string $status): void
    {
        // Find lesson by video_url (contains the public_id)
        $lesson = Lesson::where('video_url', 'like', "%{$publicId}%")
            ->where('video_type', 'local')
            ->first();

        if ($lesson) {
            $lesson->update([
                'video_status' => $status,
                'video_processed_at' => $status === 'ready' ? now() : null,
            ]);

            \Log::info('Updated lesson video status', [
                'lesson_id' => $lesson->id,
                'status' => $status,
            ]);

            // Dispatch Livewire event to update connected components in real-time
            if ($status === 'ready') {
                Livewire::dispatch('video-ready', lessonId: $lesson->id);
            }
        }
    }
}
