<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class VideoSigningService
{
    /**
     * Generate a secure Cloudinary URL for a lesson video.
     * Includes authentication token for access control.
     */
    public function getSignedUrl(string $publicId, int $expirationMinutes = 1440): ?string
    {
        if (!$publicId) {
            return null;
        }

        try {
            // If already a full URL, return as-is
            if (str_starts_with($publicId, 'http')) {
                return $publicId;
            }

            $publicId = $this->extractPublicId($publicId);

            // Build authenticated video URL with token
            $cloudinary = new \Cloudinary\Cloudinary();

            $token = $this->generateAuthToken($expirationMinutes);

            $url = sprintf(
                'https://res.cloudinary.com/%s/video/authenticated/token=%s/%s.m3u8',
                config('cloudinary.cloud_name'),
                $token,
                $publicId
            );

            return $url;
        } catch (\Exception $e) {
            \Log::warning('Failed to generate signed URL for video', [
                'publicId' => $publicId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate authentication token for Cloudinary.
     */
    private function generateAuthToken(int $expirationMinutes = 1440): string
    {
        $timestamp = now()->getTimestamp();
        $endTime = $timestamp + ($expirationMinutes * 60);
        $secret = config('cloudinary.api_secret');

        // Create auth token
        $authString = "end_time={$endTime}&token_start_time={$timestamp}";
        $authTokenValue = sha1($authString . $secret);

        return base64_encode("{$authString}&auth_token={$authTokenValue}");
    }

    /**
     * Get video metadata from Cloudinary.
     */
    public function getMetadata(string $publicId): ?array
    {
        try {
            // Extract just the public ID without extension
            $publicId = $this->extractPublicId($publicId);

            if (!$publicId) {
                return null;
            }

            $cloudinary = new \Cloudinary\Cloudinary();
            $result = $cloudinary->adminApi()->asset($publicId, [
                'resource_type' => 'video',
            ]);

            return (array) $result;
        } catch (\Exception $e) {
            \Log::warning('Failed to get video metadata', [
                'publicId' => $publicId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Delete a video from Cloudinary.
     * Handles both public IDs and full Cloudinary URLs.
     */
    public function delete(string $videoPath): bool
    {
        try {
            // Extract public ID if it's a full Cloudinary URL
            $publicId = $this->extractPublicId($videoPath);

            if (!$publicId) {
                return false;
            }

            // Use Cloudinary Laravel facade to delete the video
            Cloudinary::uploadApi()->destroy($publicId, [
                'resource_type' => 'video',
                'invalidate' => true, // Invalidate CDN cache
            ]);

            \Log::info('Video deleted from Cloudinary', [
                'publicId' => $publicId,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::warning('Failed to delete video from Cloudinary', [
                'videoPath' => $videoPath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Extract public ID from either a public ID string or a full Cloudinary URL.
     */
    private function extractPublicId(string $videoPath): ?string
    {
        // Remove file extension if present
        $videoPath = preg_replace('/\.(mp4|mov|avi|mkv|flv|m3u8|mpd)$/i', '', $videoPath);

        // If it's already just a public ID (doesn't contain res.cloudinary.com)
        if (!str_contains($videoPath, 'res.cloudinary.com')) {
            return $videoPath;
        }

        // Extract from full URL: res.cloudinary.com/cloud_name/video/upload/v123/public_id
        // Pattern: /v\d+\/(.+?)(?:\?|$)
        if (preg_match('/\/v\d+\/(.+?)(?:\?|$)/', $videoPath, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Move a video to a different folder in Cloudinary.
     * Cloudinary uses "rename" to move files between folders.
     */
    public function moveToFolder(string $publicId, string $newFolder): bool
    {
        try {
            $newPublicId = $newFolder . '/' . basename($publicId);

            Cloudinary::rename($publicId, $newPublicId, ['resource_type' => 'video']);

            return true;
        } catch (\Exception $e) {
            \Log::warning('Failed to move video to folder in Cloudinary', [
                'publicId' => $publicId,
                'newFolder' => $newFolder,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get HLS streaming URL for adaptive bitrate delivery.
     * Serves video in multiple quality levels automatically.
     */
    public function getHlsStreamingUrl(string $publicId): ?string
    {
        if (!$publicId) {
            return null;
        }

        try {
            $publicId = $this->extractPublicId($publicId);
            $token = $this->generateAuthToken(1440);

            // HLS format with quality auto-selection
            return sprintf(
                'https://res.cloudinary.com/%s/video/authenticated/q_auto/token=%s/%s.m3u8',
                config('cloudinary.cloud_name'),
                $token,
                $publicId
            );
        } catch (\Exception $e) {
            \Log::warning('Failed to generate HLS URL', [
                'publicId' => $publicId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get DASH streaming URL for high-quality adaptive delivery.
     */
    public function getDashStreamingUrl(string $publicId): ?string
    {
        if (!$publicId) {
            return null;
        }

        try {
            $publicId = $this->extractPublicId($publicId);
            $token = $this->generateAuthToken(1440);

            // DASH format with auto codec selection
            return sprintf(
                'https://res.cloudinary.com/%s/video/authenticated/vc_auto/token=%s/%s.mpd',
                config('cloudinary.cloud_name'),
                $token,
                $publicId
            );
        } catch (\Exception $e) {
            \Log::warning('Failed to generate DASH URL', [
                'publicId' => $publicId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get optimized video URL with automatic quality and format selection.
     */
    public function getOptimizedUrl(string $publicId): ?string
    {
        if (!$publicId) {
            return null;
        }

        try {
            // Remove extension if present
            $publicId = preg_replace('/\.(mp4|mov|avi|mkv|flv|m3u8|mpd)$/i', '', $publicId);

            // Build direct Cloudinary delivery URL with transformations
            // Format: https://res.cloudinary.com/cloud_name/video/upload/TRANSFORMATIONS/public_id.FORMAT
            return sprintf(
                'https://res.cloudinary.com/%s/video/upload/q_auto,vc_auto,ac_aac/%s.mp4',
                config('cloudinary.cloud_name'),
                $publicId
            );
        } catch (\Exception $e) {
            \Log::warning('Failed to generate optimized URL', [
                'publicId' => $publicId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get video thumbnail with optimization.
     */
    public function getThumbnail(string $publicId, int $width = 320, int $height = 180): ?string
    {
        if (!$publicId) {
            return null;
        }

        try {
            $publicId = $this->extractPublicId($publicId);
            $token = $this->generateAuthToken(1440);

            // Thumbnail JPEG with auto quality
            return sprintf(
                'https://res.cloudinary.com/%s/video/authenticated/w_%d,h_%d,c_fill,q_auto/token=%s/%s.jpg',
                config('cloudinary.cloud_name'),
                $width,
                $height,
                $token,
                $publicId
            );
        } catch (\Exception $e) {
            \Log::warning('Failed to generate thumbnail URL', [
                'publicId' => $publicId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
