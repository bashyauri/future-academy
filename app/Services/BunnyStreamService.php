<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

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

        $payload = array_filter([
            'title' => $title,
            'collectionId' => $collectionId,
            'thumbnailTime' => $thumbnailTime,
        ], fn ($value) => $value !== null);

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Accept' => 'application/json',
        ])->post($this->baseUrl . "/library/{$this->libraryId}/videos", $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Bunny Stream create video failed: ' . $response->body());
        }

        return (array) $response->json();
    }

    public function uploadVideo(string $videoId, UploadedFile $file): void
    {
        $this->assertConfigured();

        $contents = file_get_contents($file->getRealPath());

        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Accept' => 'application/json',
        ])->withBody($contents, $file->getMimeType() ?: 'application/octet-stream')
            ->put($this->baseUrl . "/library/{$this->libraryId}/videos/{$videoId}");

        if (!$response->successful()) {
            throw new \RuntimeException('Bunny Stream upload failed: ' . $response->body());
        }
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

    public function deleteVideo(string $videoId): void
    {
        $this->assertConfigured();

        Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Accept' => 'application/json',
        ])->delete($this->baseUrl . "/library/{$this->libraryId}/videos/{$videoId}");
    }

    private function assertConfigured(): void
    {
        if (!$this->libraryId || !$this->apiKey) {
            throw new \RuntimeException('Bunny Stream is not configured. Set BUNNY_STREAM_LIBRARY_ID and BUNNY_STREAM_API_KEY.');
        }
    }
}
