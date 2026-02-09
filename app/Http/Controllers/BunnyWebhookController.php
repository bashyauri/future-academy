<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\VideoProgress;
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
 * - Video analytics/views (if configured)
 *
 * Webhook payload structure:
 * {
 *   "EventType": "VideoTranscodingComplete" | "VideoEncodingFailed" | "VideoAnalyticsEvent" | etc.
 *   "VideoGuid": "uuid",
 *   "VideoTitle": "string",
 *   "LibraryId": 123,
 *   "CollectionId": 456 (optional),
 *   "WatchTime": 123456 (analytics),
 *   "Country": "NG" (analytics),
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
                'user_id' => $payload['UserId'] ?? 'unknown',
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
                'ViewStarted' => $this->handleViewStarted($videoGuid, $payload),
                'ViewEnded' => $this->handleViewEnded($videoGuid, $payload),
                'ViewResume' => $this->handleViewResume($videoGuid, $payload),
                'VideoAnalyticsEvent', 'ViewEvent' => $this->handleAnalyticsEvent($videoGuid, $payload),
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
     * Handle video analytics/playback events.
     *
     * Called when Bunny sends analytics data about video views and watch time.
     * This tracks actual user progress watching the video.
     */
    private function handleViewStarted(string $videoGuid, array $payload): void
    {
        \Log::info('Bunny video view started', [
            'video_guid' => $videoGuid,
            'session_id' => $payload['SessionId'] ?? null,
            'ip' => $payload['IpAddress'] ?? null,
            'country' => $payload['Country'] ?? null,
            'user_agent' => $payload['UserAgent'] ?? null,
        ]);

        // You could create an initial video progress record here
        // This event fires when a user starts playing the video
    }

    /**
     * Handle video view ended event.
     *
     * Called when Bunny detects that the user has finished watching (or closed the player).
     * Contains final analytics of the viewing session.
     */
    private function handleViewEnded(string $videoGuid, array $payload): void
    {
        $watchTime = $payload['WatchTime'] ?? 0; // In milliseconds
        $watchPercentage = $payload['WatchPercentage'] ?? $payload['PercentageWatched'] ?? 0;
        $sessionId = $payload['SessionId'] ?? null;
        $downloadEnable = $payload['DownloadEnabled'] ?? false;

        // Convert milliseconds to seconds
        $watchTimeSeconds = intval($watchTime / 1000);

        \Log::info('Bunny video view ended', [
            'video_guid' => $videoGuid,
            'session_id' => $sessionId,
            'watch_time_seconds' => $watchTimeSeconds,
            'watch_percentage' => $watchPercentage,
            'download_enabled' => $downloadEnable,
        ]);

        // Store final analytics data using SessionId to identify user
        // Note: SessionId is used since Bunny doesn't always provide UserId
        if ($watchPercentage > 0) {
            $this->recordViewEndedAnalytics($videoGuid, $sessionId, $watchTimeSeconds, $watchPercentage, $payload);
        }
    }

    /**
     * Handle video resume event.
     *
     * Called when Bunny detects that a user resumes watching a video
     * from where they left off (if persistentSettings is enabled).
     */
    private function handleViewResume(string $videoGuid, array $payload): void
    {
        $resumeTime = $payload['ResumeTime'] ?? 0; // Resume position in seconds
        $sessionId = $payload['SessionId'] ?? null;

        \Log::info('Bunny video resume detected', [
            'video_guid' => $videoGuid,
            'session_id' => $sessionId,
            'resume_from_seconds' => $resumeTime,
        ]);

        // You could track resume events for user engagement analytics
        // This helps identify users who come back to watch more of the video
    }

    /**
     * Handle video analytics/playback events.
     *
     * Called when Bunny sends analytics data about video views and watch time.
     * This tracks actual user progress watching the video.
     */
    {
        $watchTime = $payload['WatchTime'] ?? 0; // In milliseconds
        $watchPercentage = $payload['WatchPercentage'] ?? $payload['PercentageWatched'] ?? 0;
        $userId = $payload['UserId'] ?? null;
        $countryCode = $payload['Country'] ?? null;

        // Convert milliseconds to seconds
        $watchTimeSeconds = intval($watchTime / 1000);

        \Log::info('Bunny video analytics event', [
            'video_guid' => $videoGuid,
            'user_id' => $userId,
            'watch_time_seconds' => $watchTimeSeconds,
            'watch_percentage' => $watchPercentage,
            'country' => $countryCode,
        ]);

        // If we have user tracking data from Bunny
        if ($userId && $watchPercentage > 0) {
            $this->trackUserVideoProgress($videoGuid, $userId, $watchTimeSeconds, $watchPercentage);
        }
    }

    /**
     * Track user video progress from Bunny analytics.
     *
     * Creates or updates video progress based on Bunny's analytics data.
     * Also stores the raw Bunny webhook data for future reference.
     */
    private function trackUserVideoProgress(string $videoGuid, $userId, int $watchTime, int $watchPercentage): void
    {
        try {
            // Find lesson by video GUID
            $lesson = Lesson::where('video_url', $videoGuid)
                ->where('video_type', 'bunny')
                ->first();

            if (!$lesson) {
                \Log::warning('No lesson found for video analytics', ['video_guid' => $videoGuid]);
                return;
            }

            // Update or create video progress record
            VideoProgress::updateOrCreate(
                [
                    'user_id' => $userId,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'watch_time' => $watchTime,
                    'percentage' => min(100, $watchPercentage),
                    'completed' => $watchPercentage >= 90,
                ]
            );

            \Log::info('Updated video progress from Bunny webhook', [
                'lesson_id' => $lesson->id,
                'user_id' => $userId,
                'percentage' => $watchPercentage,
                'video_guid' => $videoGuid,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error tracking video progress from webhook', [
                'error' => $e->getMessage(),
                'video_guid' => $videoGuid,
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * Record analytics data when a video view session ends.
     *
     * Stores the final viewing metrics in the bunny_watch_data JSON field
     * for detailed analytics and user engagement tracking.
     */
    private function recordViewEndedAnalytics(string $videoGuid, ?string $sessionId, int $watchTimeSeconds, int $watchPercentage, array $payload): void
    {
        try {
            // Find lesson by video GUID
            $lesson = Lesson::where('video_url', $videoGuid)
                ->where('video_type', 'bunny')
                ->first();

            if (!$lesson) {
                \Log::warning('No lesson found for view ended analytics', ['video_guid' => $videoGuid]);
                return;
            }

            // Prepare bunny watch data to store
            $bunnyWatchData = [
                'session_id' => $sessionId,
                'watch_time_seconds' => $watchTimeSeconds,
                'watch_percentage' => min(100, $watchPercentage),
                'download_enabled' => $payload['DownloadEnabled'] ?? false,
                'ip_address' => $payload['IpAddress'] ?? null,
                'country' => $payload['Country'] ?? null,
                'user_agent' => $payload['UserAgent'] ?? null,
                'timestamp' => now()->toIso8601String(),
            ];

            // Get or create progress record
            // If we have a user ID in analytics, use it; otherwise just update by lesson
            $videoProgress = VideoProgress::where('lesson_id', $lesson->id)
                ->latest('updated_at')
                ->first();

            if ($videoProgress) {
                // Update existing progress with final metrics
                $videoProgress->update([
                    'watch_time' => max($videoProgress->watch_time, $watchTimeSeconds),
                    'percentage' => max($videoProgress->percentage, $watchPercentage),
                    'completed' => $watchPercentage >= 90 || $videoProgress->completed,
                    'bunny_watch_data' => $bunnyWatchData,
                ]);
            } else {
                // Create new progress record from webhook data (fallback)
                VideoProgress::create([
                    'lesson_id' => $lesson->id,
                    'watch_time' => $watchTimeSeconds,
                    'percentage' => $watchPercentage,
                    'completed' => $watchPercentage >= 90,
                    'bunny_watch_data' => $bunnyWatchData,
                ]);
            }

            \Log::info('Recorded view ended analytics', [
                'lesson_id' => $lesson->id,
                'session_id' => $sessionId,
                'watch_percentage' => $watchPercentage,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error recording view ended analytics', [
                'error' => $e->getMessage(),
                'video_guid' => $videoGuid,
            ]);
        }
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
