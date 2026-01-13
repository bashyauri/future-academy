<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Video Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for video upload and delivery via Cloudinary.
    | Cloudinary provides 25GB free storage, auto-transcoding, and built-in CDN.
    |
    */

    'upload' => [
        // Storage disk for video uploads (cloudinary)
        'disk' => env('VIDEO_STORAGE_DISK', 'cloudinary'),

        // Max upload size in KB (500MB default)
        'max_size' => env('VIDEO_MAX_SIZE', 512000),

        // Accepted MIME types
        'accepted_types' => ['video/mp4', 'video/quicktime'],

        // Upload directory in Cloudinary
        'directory' => 'future-academy/lessons',
    ],

    'delivery' => [
        // Signed URL expiration in minutes (24 hours default)
        'signed_url_expiry' => env('VIDEO_SIGNED_URL_EXPIRY', 1440),

        // Use Cloudinary's authenticated delivery
        'authenticated' => env('VIDEO_AUTHENTICATED_DELIVERY', true),
    ],

    'thumbnail' => [
        // Cloudinary can generate thumbnails automatically
        'auto_generate' => env('VIDEO_THUMBNAIL_AUTO_GENERATE', true),

        // Thumbnail width (Cloudinary will auto-scale)
        'width' => env('VIDEO_THUMBNAIL_WIDTH', 320),

        // Thumbnail height
        'height' => env('VIDEO_THUMBNAIL_HEIGHT', 180),
    ],
];
