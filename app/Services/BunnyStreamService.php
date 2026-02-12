<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BunnyStreamService
{
    private string $baseUrl;
    private string $libraryId;
    private string $apiKey;
    private string $embedBaseUrl;
    private ?string $embedTokenKey;

    public function __construct()
    {
        $this->baseUrl = 'https://video.bunnycdn.com';
        $this->libraryId = (string) config('services.bunny.stream_library_id');
        $this->apiKey = (string) config('services.bunny.stream_api_key');
        $this->embedBaseUrl = (string) config('services.bunny.stream_embed_url', 'https://iframe.mediadelivery.net/embed');
        $this->embedTokenKey = config('services.bunny.stream_embed_token_key');
    }

    public function createVideo(string $title, ?string $collectionId = null, ?int $thumbnailTime = null): array
    {
        $this->assertConfigured();

        Log::info('BunnyStreamService: Creating video', [
            'title' => $title,
            'library_id' => $this->libraryId,
            'collection_id' => $collectionId,
        ]);

        $payload = array_filter([
            'title' => $title,
            'collectionId' => $collectionId,
            'thumbnailTime' => $thumbnailTime,
        ], fn ($value) => $value !== null);

        $url = $this->baseUrl . "/library/{$this->libraryId}/videos";
        Log::info('BunnyStreamService: API Request', ['url' => $url, 'payload' => $payload]);

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Accept' => 'application/json',
        ])->post($url, $payload);

        Log::info('BunnyStreamService: API Response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->body(),
        ]);

        if (!$response->successful()) {
            Log::error('BunnyStreamService: Create video failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Bunny Stream create video failed: ' . $response->body());
        }

        $result = (array) $response->json();
        Log::info('BunnyStreamService: Video created successfully', [
            'video_id' => $result['guid'] ?? $result['videoId'] ?? $result['id'] ?? 'unknown',
            'result' => $result,
        ]);

        return $result;
    }

    public function uploadVideo(string $videoId, UploadedFile $file): void
    {
        $this->assertConfigured();

        Log::info('BunnyStreamService: Starting video upload', [
            'video_id' => $videoId,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_path' => $file->getRealPath(),
        ]);

        $contents = file_get_contents($file->getRealPath());
        Log::info('BunnyStreamService: File contents read', ['content_length' => strlen($contents)]);

        $url = $this->baseUrl . "/library/{$this->libraryId}/videos/{$videoId}";
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';

        Log::info('BunnyStreamService: Uploading to Bunny', [
            'url' => $url,
            'content_length' => strlen($contents),
            'mime_type' => $mimeType,
        ]);

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Accept' => 'application/json',
        ])->withBody($contents, $mimeType)
            ->put($url);

        Log::info('BunnyStreamService: Upload response received', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->body(),
        ]);

        if (!$response->successful()) {
            Log::error('BunnyStreamService: Upload failed', [
                'video_id' => $videoId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Bunny Stream upload failed: ' . $response->body());
        }

        Log::info('BunnyStreamService: Video uploaded successfully', ['video_id' => $videoId]);
    }

    public function fetchVideoFromUrl(string $url, ?string $title = null): array
    {
        $this->assertConfigured();

        $payload = array_filter([
            'url' => $url,
            'title' => $title,
        ], fn ($value) => $value !== null);

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Accept' => 'application/json',
        ])->post($this->baseUrl . "/library/{$this->libraryId}/videos/fetch", $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Bunny Stream fetch failed: ' . $response->body());
        }

        return (array) $response->json();
    }

    public function getVideo(string $videoId): ?array
    {
        $this->assertConfigured();

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Accept' => 'application/json',
        ])->get($this->baseUrl . "/library/{$this->libraryId}/videos/{$videoId}");

        if (!$response->successful()) {
            return null;
        }

        return (array) $response->json();
    }

    public function getEmbedUrl(string $videoId, ?int $expiresSeconds = null): string
    {
        $base = rtrim($this->embedBaseUrl, '/');
        $url = $base . "/{$this->libraryId}/{$videoId}";

        if ($this->embedTokenKey && $expiresSeconds) {
            $token = hash('sha256', $this->embedTokenKey . $videoId . $expiresSeconds);
            $url .= "?token={$token}&expires={$expiresSeconds}";
        }

        return $url;
    }

    /**
     * Get Bunny Stream thumbnail URL (JPEG).
     */
    public function getThumbnailUrl(string $videoId): string
    {
        return $this->baseUrl . "/library/{$this->libraryId}/videos/{$videoId}/thumbnail.jpg";
    }

    /**
     * Get Bunny Stream preview animation URL (WebP).
     */
    public function getPreviewAnimationUrl(string $videoId): string
    {
        return $this->baseUrl . "/library/{$this->libraryId}/videos/{$videoId}/preview.webp";
    }

    /**
     * Get Bunny Stream direct play URL (MP4).
     */
    public function getDirectPlayUrl(string $videoId): string
    {
        return $this->baseUrl . "/library/{$this->libraryId}/videos/{$videoId}/play.mp4";
    }

    /**
     * Get Bunny Stream HLS playlist URL (M3U8).
     */
    public function getHlsPlaylistUrl(string $videoId): string
    {
        return $this->baseUrl . "/library/{$this->libraryId}/videos/{$videoId}/playlist.m3u8";
    }

    public function deleteVideo(string $videoId): void
    {
        $this->assertConfigured();

        Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Accept' => 'application/json',
        ])->delete($this->baseUrl . "/library/{$this->libraryId}/videos/{$videoId}");
    }

    /**
     * Get video statistics from Bunny Analytics
     *
     * Returns view count, watch time, and other analytics data
     */
    public function getVideoStats(string $videoId): ?array
    {
        $this->assertConfigured();

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Accept' => 'application/json',
        ])->get($this->baseUrl . "/library/{$this->libraryId}/videos/{$videoId}/statistics");

        if (!$response->successful()) {
            \Log::warning('Failed to fetch Bunny video stats', [
                'video_id' => $videoId,
                'status' => $response->status(),
            ]);
            return null;
        }

        return (array) $response->json();
    }

    /**
     * Get a video's view count and basic analytics
     */
    public function getVideoViewCount(string $videoId): int
    {
        try {
            $stats = $this->getVideoStats($videoId);
            return $stats['views'] ?? $stats['viewCount'] ?? 0;
        } catch (\Exception $e) {
            \Log::error('Error fetching video view count', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Validate that a video exists on Bunny by video ID.
     * Supports both full URLs and video IDs.
     */
    public function validateVideoId(string $videoInput): ?string
    {
        try {
            // Extract video ID from URL if it's a full URL
            $videoId = $this->extractVideoIdFromUrl($videoInput);

            // Try to fetch the video metadata to validate it exists
            $video = $this->getVideo($videoId);

            return $video ? $videoId : null;
        } catch (\Exception $e) {
            \Log::warning('Video ID validation failed', [
                'input' => $videoInput,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extract video ID from Bunny URL or return as-is if it's already an ID.
     */
    private function extractVideoIdFromUrl(string $input): string
    {
        // If it's already a UUID, return it
        if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $input)) {
            return $input;
        }

        // Try to extract from common Bunny URL patterns
        if (preg_match('/\/videos\/([a-f0-9\-]+)/i', $input, $matches)) {
            return $matches[1];
        }

        // If no pattern matches, treat the entire input as the ID
        return $input;
    }

    private function assertConfigured(): void
    {
        if (!$this->libraryId || !$this->apiKey) {
            throw new \RuntimeException('Bunny Stream is not configured. Set BUNNY_STREAM_LIBRARY_ID and BUNNY_STREAM_API_KEY.');
        }
    }
}
