<?php

namespace App\Http\Controllers\Admin;

use App\Services\BunnyStreamService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoUploadController
{
    private BunnyStreamService $bunnyService;
    private string $chunksPath = 'video-uploads/chunks';

    public function __construct(BunnyStreamService $bunnyService)
    {
        $this->bunnyService = $bunnyService;
    }

    /**
     * Validate a Bunny video exists
     */
    public function validate(Request $request)
    {
        $validated = $request->validate([
            'video_id' => 'required|string|size:36',
        ]);

        try {
            $video = $this->bunnyService->getVideo($validated['video_id']);

            if (!$video) {
                return response()->json([
                    'error' => 'Video not found on Bunny'
                ], 404);
            }

            Log::info('Video validated', ['video_id' => $validated['video_id']]);

            return response()->json(['success' => true, 'video' => $video]);
        } catch (\Exception $e) {
            Log::error('Failed to validate video on Bunny', [
                'video_id' => $validated['video_id'],
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Failed to validate video: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new video on Bunny Stream
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        // Debug: Check Bunny config
        $libraryId = config('services.bunny.stream_library_id');
        $apiKey = config('services.bunny.stream_api_key');

        if (empty($libraryId) || empty($apiKey)) {
            Log::error('Bunny credentials missing', [
                'library_id' => $libraryId,
                'api_key_present' => !empty($apiKey),
            ]);
            return response()->json([
                'error' => 'Bunny Stream not configured. Check BUNNY_STREAM_API_KEY and BUNNY_STREAM_LIBRARY_ID in .env'
            ], 500);
        }

        try {
            $video = $this->bunnyService->createVideo($validated['title']);
            $videoId = $video['guid'] ?? $video['videoId'] ?? $video['id'] ?? null;

            if (!$videoId) {
                throw new \RuntimeException('Bunny did not return video ID');
            }

            Log::info('Video created on Bunny', ['video_id' => $videoId, 'title' => $validated['title']]);

            return response()->json(['video_id' => $videoId]);
        } catch (\Exception $e) {
            Log::error('Failed to create video on Bunny', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to create video',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Upload a chunk of a video
     */
    public function uploadChunk(Request $request)
    {
        $validated = $request->validate([
            'video_id' => 'required|string',
            'chunk' => 'required|file',
            'chunk_index' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
        ]);

        try {
            $videoId = $validated['video_id'];
            $chunkIndex = $validated['chunk_index'];
            $totalChunks = $validated['total_chunks'];
            $chunkFile = $request->file('chunk');

            // Store chunk temporarily
            $chunkPath = "{$this->chunksPath}/{$videoId}";
            $chunkFileName = "chunk_{$chunkIndex}.tmp";

            Storage::disk('local')->putFileAs(
                $chunkPath,
                $chunkFile,
                $chunkFileName,
                'private'
            );

            Log::debug("Chunk uploaded", [
                'video_id' => $videoId,
                'chunk_index' => $chunkIndex,
                'total_chunks' => $totalChunks,
                'chunk_size' => $chunkFile->getSize(),
            ]);

            // Check if all chunks are uploaded
            if ($this->allChunksUploaded($videoId, $totalChunks)) {
                // Combine chunks and upload to Bunny
                $this->combineAndUploadToBunny($videoId, $totalChunks);
            }

            return response()->json(['success' => true, 'chunk' => $chunkIndex]);
        } catch (\Exception $e) {
            Log::error('Chunk upload failed', [
                'error' => $e->getMessage(),
                'chunk_index' => $validated['chunk_index'] ?? 'unknown',
            ]);
            return response()->json(['error' => 'Chunk upload failed'], 500);
        }
    }

    /**
     * Check if all chunks have been uploaded
     */
    private function allChunksUploaded(string $videoId, int $totalChunks): bool
    {
        $chunkPath = "{$this->chunksPath}/{$videoId}";

        for ($i = 0; $i < $totalChunks; $i++) {
            if (!Storage::disk('local')->exists("{$chunkPath}/chunk_{$i}.tmp")) {
                return false;
            }
        }

        return true;
    }

    /**
     * Combine chunks and upload to Bunny
     */
    private function combineAndUploadToBunny(string $videoId, int $totalChunks): void
    {
        try {
            Log::info('Combining chunks for upload', ['video_id' => $videoId, 'total_chunks' => $totalChunks]);

            $chunkPath = "{$this->chunksPath}/{$videoId}";
            $videoContent = '';

            // Combine all chunks into memory
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkFile = "{$chunkPath}/chunk_{$i}.tmp";
                $chunkData = Storage::disk('local')->get($chunkFile);
                $videoContent .= $chunkData;

                Log::debug('Chunk combined', [
                    'video_id' => $videoId,
                    'chunk_index' => $i,
                    'chunk_size' => strlen($chunkData),
                    'total_size_so_far' => strlen($videoContent)
                ]);
            }

            Log::info('All chunks combined, uploading to Bunny', [
                'video_id' => $videoId,
                'total_size' => strlen($videoContent)
            ]);

            // Upload to Bunny
            $this->uploadToBunnyStream($videoId, $videoContent);

            // Clean up chunks after successful upload
            Storage::disk('local')->deleteDirectory($chunkPath);

            Log::info('Video successfully combined and uploaded to Bunny', ['video_id' => $videoId]);
        } catch (\Exception $e) {
            Log::error('Failed to combine and upload video to Bunny', [
                'video_id' => $videoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Upload video content to Bunny Stream
     */
    private function uploadToBunnyStream(string $videoId, string $content): void
    {
        $baseUrl = 'https://video.bunnycdn.com';
        $libraryId = config('services.bunny.stream_library_id');
        $apiKey = config('services.bunny.stream_api_key');

        $url = "{$baseUrl}/library/{$libraryId}/videos/{$videoId}";

        Log::info('Uploading to Bunny Stream', [
            'video_id' => $videoId,
            'url' => $url,
            'content_size' => strlen($content)
        ]);

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'AccessKey' => $apiKey,
            'Accept' => 'application/json',
        ])->withBody($content, 'video/mp4')->put($url);

        Log::info('Bunny Stream upload response', [
            'video_id' => $videoId,
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Bunny Stream upload failed (HTTP {$response->status()}): {$response->body()}");
        }
    }
}
