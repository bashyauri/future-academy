<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

/**
 * Service for managing Cloudinary upload presets and configurations.
 * Uses unsigned uploads with server-side folder organization.
 */
class CloudinaryUploadService
{
    /**
     * Get upload signature for client-side widget.
     * Generates a time-limited signature for secure unsigned uploads.
     */
    public function getUploadSignature(string $folder = ''): array
    {
        $timestamp = now()->getTimestamp();
        $cloudName = config('cloudinary.cloud_name');
        $apiSecret = config('cloudinary.api_secret');

        // Build the upload parameters
        $params = [
            'timestamp' => $timestamp,
            'folder' => $folder ?: 'future-academy/lessons/uploads',
            'resource_type' => 'video',
            'type' => 'upload',
            'overwrite' => false,
            'eager' => 'media_limit:20m', // Limit to 20MB for preview
            'eager_async' => true,
        ];

        // Generate signature
        $toSign = collect($params)
            ->filter(fn($value) => $value !== null)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->sort()
            ->implode('&');

        $signature = hash_hmac('sha256', $toSign, $apiSecret);

        return [
            'signature' => $signature,
            'timestamp' => $timestamp,
            'cloud_name' => $cloudName,
            'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET', ''),
            'folder' => $params['folder'],
            'api_key' => config('cloudinary.api_key'),
        ];
    }

    /**
     * Get upload signature with dynamic folder based on subject/topic.
     */
    public function getSignatureForLesson(?int $subjectId, ?int $topicId = null): array
    {
        $folder = 'future-academy/lessons/uploads';

        if ($subjectId) {
            $subject = \App\Models\Subject::find($subjectId);
            if ($subject) {
                $folder = 'future-academy/lessons/' . \Illuminate\Support\Str::slug($subject->name);

                if ($topicId) {
                    $topic = \App\Models\Topic::find($topicId);
                    if ($topic) {
                        $folder .= '/' . \Illuminate\Support\Str::slug($topic->name);
                    }
                }
            }
        }

        return $this->getUploadSignature($folder);
    }

    /**
     * Validate webhook signature from Cloudinary.
     */
    public function validateWebhookSignature(array $data, string $signature): bool
    {
        $apiSecret = config('cloudinary.api_secret');

        // Recreate the signature
        ksort($data);
        $toSign = collect($data)
            ->filter(fn($value) => is_string($value) || is_numeric($value))
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode('&');

        $expectedSignature = hash_hmac('sha256', $toSign, $apiSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
