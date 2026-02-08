<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Livewire\Livewire;

/**
 * Handles webhooks from Bunny Stream when video processing completes.
 *
 * Bunny Stream sends webhook notifications when:
 * - Video upload completes
 * - Video encoding/transcoding finishes
 * - Processing fails
 *
 * Webhook payload structure:
 * {
 *   "EventType": "VideoTranscodingComplete" | "VideoEncodingFailed" | etc.
 *   "VideoGuid": "uuid",
 *   "VideoTitle": "string",
 *   "LibraryId": 123,
 *   "CollectionId": 456 (optional),
 *   ...
 * }
 */
class BunnyWebhookController extends Controller
{
    /**
     * Handle webhook from Bunny Stream.
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            // Log webhook receipt for debugging
            \Log::info('Bunny webhook received', [
                'event_type' => $payload['EventType'] ?? 'unknown',
                'video_guid' => $payload['VideoGuid'] ?? 'unknown',
            ]);

            // Validate webhook signature if AccessKey is configured
            if (!$this->validateWebhookSignature($request)) {
                \Log::warning('Invalid Bunny webhook signature');
                // Note: Still process the webhook (Bunny signature validation is optional)
            }

            $eventType = $payload['EventType'] ?? null;
            $videoGuid = $payload['VideoGuid'] ?? null;

            if (!$videoGuid) {
                return response()->json(['error' => 'No VideoGuid'], 400);
            }

            // Handle different Bunny event types
            match ($eventType) {
                'VideoTranscodingComplete' => $this->handleTranscodingComplete($videoGuid, $payload),
                'VideoEncodingFailed' => $this->handleEncodingFailed($videoGuid, $payload),
                'VideoTranscodingStarted' => $this->handleTranscodingStarted($videoGuid, $payload),
                default => \Log::info('Unhandled Bunny webhook event', ['type' => $eventType]),
            };

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            \Log::error('Bunny webhook error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Validate webhook signature (optional - Bunny allows insecure webhooks).
     *
     * In production, you should:
     * 1. Configure Bunny webhook signing in dashboard
     * 2. Get the signing key from Bunny
     * 3. Verify HMAC signature from X-Bunny-Webhook-Signature header
     */
    private function validateWebhookSignature(Request $request): bool
    {
        // Signature validation can be implemented here if Bunny is configured with signing
        // For now, returning true to allow webhooks through
        // To enable: Get signing key from config and verify request body HMAC

        return true;
    }

    /**
     * Handle successful video transcoding completion.
     *
     * Called when Bunny finishes encoding the video into multiple quality formats.
     */
    private function handleTranscodingComplete(string $videoGuid, array $payload): void
    {
        \Log::info('Bunny video transcoding complete', [
            'video_guid' => $videoGuid,
            'title' => $payload['VideoTitle'] ?? 'unknown',
        ]);

        $this->updateLessonVideoStatus($videoGuid, 'ready');
    }

    /**
     * Handle encoding failure.
     *
     * Called when Bunny encounters an error during video processing.
     */
    private function handleEncodingFailed(string $videoGuid, array $payload): void
    {
        \Log::error('Bunny video encoding failed', [
            'video_guid' => $videoGuid,
            'error' => $payload['Error'] ?? 'unknown error',
        ]);

        $this->updateLessonVideoStatus($videoGuid, 'failed');
    }

    /**
     * Handle transcoding start notification.
     *
     * Optional: Called when Bunny starts transcoding the video.
     * Useful for user notifications.
     */
    private function handleTranscodingStarted(string $videoGuid, array $payload): void
    {
        \Log::info('Bunny video transcoding started', [
            'video_guid' => $videoGuid,
        ]);

        // Could dispatch event here for real-time UI updates
        // Livewire::dispatch('video-transcoding-started', videoId: $videoGuid);
    }

    /**
     * Update lesson video processing status based on Bunny events.
     *
     * Matches Bunny video_guid (stored in video_url) to lesson records
     * and updates the video_status field accordingly.
     */
    private function updateLessonVideoStatus(string $videoGuid, string $status): void
    {
        // Find lesson by video_url (contains the Bunny video GUID) and video_type
        $lesson = Lesson::where('video_url', $videoGuid)
            ->where('video_type', 'bunny')
            ->first();

        if ($lesson) {
            $lesson->update([
                'video_status' => $status,
                'video_processed_at' => $status === 'ready' ? now() : null,
            ]);

            \Log::info('Updated lesson video status from Bunny webhook', [
                'lesson_id' => $lesson->id,
                'status' => $status,
                'video_guid' => $videoGuid,
            ]);

            // Dispatch Livewire event for real-time UI updates
            if ($status === 'ready') {
                Livewire::dispatch('video-ready', lessonId: $lesson->id);
            } elseif ($status === 'failed') {
                Livewire::dispatch('video-failed', lessonId: $lesson->id);
            }
        } else {
            \Log::warning('No lesson found for Bunny video', [
                'video_guid' => $videoGuid,
            ]);
        }
    }
}
