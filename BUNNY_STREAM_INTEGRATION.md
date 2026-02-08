# Bunny Stream Integration Guide

This document covers the implementation of Bunny Stream as the primary video platform for the Future Academy application, with backward compatibility for Cloudinary legacy videos.

## Overview

**Bunny Stream** has been integrated as the primary video upload and streaming platform, replacing Cloudinary as the default choice. This reduces costs and provides a simpler implementation on shared hosting.

**Key Benefits:**
- Lower pricing (ideal for shared hosting)
- Easier API integration
- Native token-based authentication for secure embeds
- Webhook support for async video processing

**Legacy Support:**
- Existing Cloudinary videos continue to work
- Automatic detection by `video_type` field
- Migration tools provided for gradual transition

## Setup

### 1. Environment Configuration

Add these variables to your `.env` file:

```env
BUNNY_STREAM_API_KEY=your-api-key-here
BUNNY_STREAM_LIBRARY_ID=your-library-id
BUNNY_STREAM_EMBED_URL=https://iframe.mediadelivery.net/embed
BUNNY_STREAM_EMBED_TOKEN_KEY=your-embed-token-key
```

**Where to find these:**
1. **API Key**: Bunny Stream Dashboard → Account → API Key
2. **Library ID**: Bunny Stream Dashboard → Video Library → Settings
3. **Embed URL**: Standard Bunny embed URL (usually doesn't change)
4. **Embed Token Key**: Bunny Stream Dashboard → Video Library → Security → Embed Token Key (optional, only needed for signed URLs)

### 2. Verify Configuration

```bash
php artisan tinker
# In tinker:
=> config('services.bunny')
```

Should output all four configuration keys.

## Architecture

### Core Components

#### 1. **BunnyStreamService** (`app/Services/BunnyStreamService.php`)

Main service class for Bunny Stream API integration.

```php
// Create a new video object
$service = app(BunnyStreamService::class);
$video = $service->createVideo('My Video Title');
$videoId = $video['guid']; // Store this in database

// Upload a file
$file = UploadedFile::...;
$service->uploadVideo($videoId, $file);

// Get embed URL (with optional token auth)
$embedUrl = $service->getEmbedUrl($videoId, expiresSeconds: 3600);
// Returns: https://iframe.mediadelivery.net/embed/{libraryId}/{videoId}?token={hash}&expires={timestamp}

// Fetch from remote URL (for migrations)
$result = $service->fetchVideoFromUrl('https://cloudinary.com/video.mp4', 'Title');

// Get video metadata (check transcoding status)
$metadata = $service->getVideo($videoId);

// Delete video
$service->deleteVideo($videoId);
```

#### 2. **Lesson Model** (`app/Models/Lesson.php`)

Updated to support multiple video types.

```php
// Video type can be: 'local' (Cloudinary), 'youtube', 'vimeo', 'bunny'
$lesson->video_type = 'bunny';

// Get the appropriate embed URL
$embedUrl = $lesson->getVideoEmbedUrl(); // Auto-detects video_type

// For Bunny with custom expiration
$embedUrl = $lesson->getBunnyEmbedUrl(expirationMinutes: 720);
```

#### 3. **Filament Upload Handler** (`app/Filament/Resources/LessonResource/Schemas/LessonForm.php`)

Custom file upload handler that:
1. Creates video object on Bunny
2. Uploads file to Bunny
3. Returns videoId (stored in `video_url` column)
4. Sets `video_status` to 'processing'

```php
->saveUploadedFileUsing(function (TemporaryUploadedFile $file, Set $set, Get $get): string {
    $service = app(BunnyStreamService::class);
    
    // 1. Create video
    $video = $service->createVideo($get('title') ?: $file->getClientOriginalName());
    $videoId = $video['guid'] ?? $video['videoId'] ?? null;
    
    // 2. Upload file
    $service->uploadVideo($videoId, $file);
    
    // 3. Set status to processing (webhook will update to 'ready')
    $set('video_status', 'processing');
    
    return (string) $videoId; // Will be stored in video_url column
})
```

### Database Schema

The `lessons` table uses these columns for video management:

| Column | Type | Purpose |
|--------|------|---------|
| `video_type` | string | Type of video: `'local'` (Cloudinary), `'bunny'`, `'youtube'`, `'vimeo'` |
| `video_url` | string | Video identifier or URL (for Bunny: the GUID/videoId) |
| `video_status` | string | Processing state: `'pending'`, `'processing'`, `'ready'`, `'failed'` |
| `video_processed_at` | datetime | When the video finished processing |

**Example record:**
```
id: 1
title: "Mathematics Basics"
video_type: "bunny"                              # Video uploaded to Bunny
video_url: "eb1c4f77-0cda-46be-b47d-1118ad7c2ffe"  # Bunny video GUID
video_status: "processing"                       # Awaiting transcoding completion
video_processed_at: null                         # Will be set when webhook fires
```

## Workflow: Upload to Playback

### 1. **Upload (Admin Interface)**

Admin uploads video through Filament:
1. Form uploads file to Bunny via custom handler
2. `video_type` automatically set to `'bunny'`
3. `video_url` receives Bunny video GUID
4. `video_status` set to `'processing'`

Status view shows: "Processing video (0% complete)"

### 2. **Processing (Background)**

Bunny Stream transcodes video into multiple quality formats (takes 5-30 minutes).

Bunny notifies when complete via webhook:
- Endpoint: `POST /webhooks/bunny`
- Payload: `{ "EventType": "VideoTranscodingComplete", "VideoGuid": "..." }`

### 3. **Status Update (Via Webhook)**

Webhook handler (`BunnyWebhookController`) receives transcoding complete event:
1. Queries database for lesson with matching `video_url`
2. Updates `video_status` to `'ready'`
3. Sets `video_processed_at` to current timestamp
4. Dispatches Livewire event to update connected UIs in real-time

### 4. **Playback (Student View)**

When student views lesson:
1. `LessonView` component detects `video_type = 'bunny'`
2. Calls `$lesson->getVideoEmbedUrl()` → `getBunnyEmbedUrl()`
3. Returns signed embed URL with token authentication
4. Template renders `<iframe src="{embedUrl}" ...>`

Video plays via Bunny's CDN with adaptive bitrate streaming.

## Security: Token Authentication

Bunny Stream supports optional token-based authentication for embeds:

```
SHA256_HEX(embed_token_key + video_id + expiration_timestamp) = token
```

**Example in getBunnyEmbedUrl():**
```php
$expires = now()->addMinutes(1440)->getTimestamp();  // 24 hours
$token = hash('sha256', config('services.bunny.stream_embed_token_key') . $videoId . $expires);
$url = "https://iframe.mediadelivery.net/embed/{$libraryId}/{$videoId}?token={$token}&expires={$expires}";
```

**Benefits:**
- Tokens expire after specified time
- Only users with valid token can view
- Prevents direct URL sharing
- Suitable for paid content

**Disable tokens** by leaving `BUNNY_STREAM_EMBED_TOKEN_KEY` empty in `.env`.

## Console Commands

### 1. Sync Video Status (Fallback for missed webhooks)

```bash
php artisan bunny:sync-video-status                # Check all pending videos
php artisan bunny:sync-video-status --lesson-id=5  # Check one lesson
```

Queries Bunny API for transcoding progress and updates database.

**Output:**
```
Checking lesson #5: Mathematics Basics
  Status: Active, Transcoding: 100%
  ✓ Updated to 'ready'

Sync completed!
  Updated: 1
  Still processing: 0
  Errors: 0
```

### 2. Migrate from Cloudinary to Bunny

```bash
# Dry run (shows what would happen without making changes)
php artisan bunny:migrate-from-cloudinary

# Actually migrate all videos
php artisan bunny:migrate-from-cloudinary --confirm

# Migrate one lesson
php artisan bunny:migrate-from-cloudinary --lesson-id=42 --confirm

# Migrate and delete from Cloudinary
php artisan bunny:migrate-from-cloudinary --confirm --delete
```

**Process:**
1. Finds all lessons with `video_type = 'local'` (Cloudinary videos)
2. Constructs original Cloudinary URL
3. Uploads to Bunny using remote URL fetch API
4. Updates lesson to `video_type = 'bunny'` with Bunny videoId
5. Sets status to `'processing'`
6. Optionally deletes from Cloudinary

**Example output:**
```
Found 5 video(s) to migrate

Lesson #1: Algebra Basics
  Cloudinary URL: videos/lesson-1/...
  Creating video object on Bunny...
  Video created: guid-uuid-here
  Uploading video to Bunny (from Cloudinary URL)...
  ✓ Updated lesson to use Bunny

Lesson #2: Geometry Advanced
  ...

Migration completed!
  Migrated: 5
  Failed: 0
```

**Note:** After migration, videos will be in `'processing'` state until Bunny transcoding completes. Use `--confirm` flag to actually perform migration.

## Webhook Configuration

### Bunny Dashboard Setup

1. Go to **Video Library → Settings → Webhooks**
2. Add new webhook:
   - **URL:** `https://yourdomain.com/webhooks/bunny`
   - **Events:** Check "Video Encoding Completed" and "Video Encoding Failed"
3. Save

### Webhook Signature Validation (Optional)

If Bunny has webhook signing enabled:

1. Get signing key from Bunny Dashboard
2. Store in config or .env
3. Verify HMAC in `BunnyWebhookController::validateWebhookSignature()`

Currently, signature validation is optional (commented out). To enable:
1. Request Bunny to enable webhook signing
2. Implement HMAC verification
3. Update controller's validateWebhookSignature() method

### Webhook Events

**VideoTranscodingComplete**
- Fired when Bunny finishes encoding all quality variants
- Updates lesson `video_status` to 'ready'
- Dispatches `video-ready` Livewire event

**VideoEncodingFailed**
- Fired when transcoding encounters error
- Updates lesson `video_status` to 'failed'
- Dispatches `video-failed` Livewire event

**VideoTranscodingStarted**
- Optional notification when encoding begins
- Currently just logged (not used in UI)

## Video Playback

### Template Integration

The `lesson-view.blade.php` template automatically handles all video types:

```blade
@if($lesson->video_type === 'local')
    {{-- Cloudinary: HLS adaptive streaming --}}
    <video id="lesson-video">
        <source src="{{ $fallbackUrl }}" type="video/mp4">
    </video>
    <script>
        // HLS.js setup for adaptive bitrate
    </script>
@elseif($lesson->video_type === 'bunny')
    {{-- Bunny Stream: Iframe embed --}}
    <iframe src="{{ $lesson->getVideoEmbedUrl() }}" ...></iframe>
@else
    {{-- YouTube/Vimeo: Iframe embed --}}
    <iframe src="{{ $lesson->getVideoEmbedUrl() }}" ...></iframe>
@endif
```

### Bunny Player Features

The Bunny iframe player includes:
- Adaptive bitrate streaming (auto quality selection)
- HLS/DASH support
- Theater mode
- Picture-in-picture
- Captions support
- Analytics integration (optional)
- Keyboard shortcuts

Configuration available via URL parameters (see Bunny documentation).

## Troubleshooting

### Video shows "Uploading..." but never completes

**Cause:** Upload process failed silently

**Solution:**
1. Check Laravel logs: `storage/logs/laravel.log`
2. Look for BunnyStreamService errors
3. Verify API key and Library ID are correct
4. Re-upload through Filament (old attempt will be orphaned)

### Video uploads but stays "Processing" forever

**Cause:** Webhook not received or endpoint not reachable

**Solution:**
1. Verify webhook URL configured in Bunny Dashboard
2. Check firewall/CDN rules allow Bunny IPs
3. Run manual sync: `php artisan bunny:sync-video-status --lesson-id={id}`
4. Check webhook logs in Bunny Dashboard

### Video playback shows black screen

**Cause:** Video GUID incorrect or video not found on Bunny

**Solution:**
1. Verify `video_url` contains correct GUID
2. Check video exists in Bunny Dashboard
3. Verify Library ID matches configuration
4. Check firewall allows access to Bunny CDN

### iFrame embed blocked by CORS

**Cause:** Parent domain not whitelisted in Bunny

**Solution:**
1. Go to Bunny Dashboard → Video Library → Security
2. Add your domain to allowed embed domains
3. Clear browser cache

## Performance Notes

### Upload Optimization

- Files tested: MP4 (H.264 video, AAC audio), up to 500MB
- Recommended: Convert to H.264/AAC before upload for faster processing
- Bitrate: 5-10 Mbps for HD (1080p)

### Transcoding Time

- Small videos (< 50 MB): 5-10 minutes
- Medium videos (50-200 MB): 15-30 minutes
- Large videos (200+ MB): 30+ minutes

Bunny performs encoding in background. Monitor via webhooks or manual sync.

### CDN Delivery

- Bunny's CDN caches transcoded variants globally
- First play slightly delayed (HLS variant list generation)
- Subsequent plays fastest
- Token expiration doesn't affect cached content

## Migration Path: Cloudinary → Bunny

### Option 1: Gradual Transition

1. Set Bunny as default for new uploads
2. Keep Cloudinary videos functional
3. Migrate high-value videos over time

```bash
# Migrate select lessons
php artisan bunny:migrate-from-cloudinary --lesson-id=1 --confirm
php artisan bunny:migrate-from-cloudinary --lesson-id=42 --confirm
```

### Option 2: Bulk Migration

```bash
# Migrate all at once (no Cloudinary deletion)
php artisan bunny:migrate-from-cloudinary --confirm

# Later, delete from Cloudinary when confident
php artisan bunny:migrate-from-cloudinary --confirm --delete
```

### Option 3: Hybrid (Recommended)

Keep both platforms:
- **New videos:** Upload to Bunny
- **Existing videos:** Keep on Cloudinary indefinitely
- **High-traffic videos:** Migrate to Bunny when convenient

This approach requires zero downtime and lets you verify Bunny stability.

## API Reference

### BunnyStreamService Methods

#### createVideo(title, collectionId?, thumbnailTime?)
Creates a new video object on Bunny Stream.

```php
$video = $service->createVideo('My Video');
// Returns: ['guid' => 'uuid', 'videoId' => 123, ...]
```

#### uploadVideo(videoId, UploadedFile)
Uploads file binary to a created video object.

```php
$service->uploadVideo($videoId, $file);
// No return value, throws exception on failure
```

#### fetchVideoFromUrl(url, title?)
Imports video from remote URL to Bunny (for migrations).

```php
$result = $service->fetchVideoFromUrl('https://example.com/video.mp4', 'Title');
// Returns API response with status
```

#### getVideo(videoId)
Retrieves video metadata including transcoding status.

```php
$metadata = $service->getVideo($videoId);
// Returns: ['guid' => '...', 'title' => '...', 'transcodingProgress' => 100, ...]
```

#### getEmbedUrl(videoId, expiresSeconds?)
Generates iframe embed URL with optional token authentication.

```php
$expires = now()->addHours(24)->timestamp;
$url = $service->getEmbedUrl($videoId, $expires);
// Returns: "https://iframe.mediadelivery.net/embed/123/uuid?token=...&expires=..."
```

#### deleteVideo(videoId)
Permanently removes video from Bunny Stream.

```php
$service->deleteVideo($videoId);
// No return value
```

## FAQ

**Q: Do I need to migrate existing Cloudinary videos?**
A: No. They'll continue working. Migrate when convenient for cost savings.

**Q: What if the webhook endpoint is down?**
A: Videos will be stuck in 'processing'. Use `bunny:sync-video-status` to check and update manually.

**Q: Can I use token authentication for free content?**
A: Yes, even though it's more useful for paid content. Leave BUNNY_STREAM_EMBED_TOKEN_KEY empty to disable.

**Q: How do I downgrade videos to lower quality?**
A: Bunny's iframe player handles all qualities. No configuration needed.

**Q: What happens if I change the embed token key?**
A: All existing URLs with old tokens become invalid. Only use during setup.

**Q: Can I embed videos on other websites?**
A: Yes, if you disable token auth or issue tokens for external domains. Bunny allows cross-domain embeds.

**Q: How much does Bunny Stream cost?**
A: Check bunny.net pricing. Generally $0.01-0.03 per GB bandwidth depending on region and volume.

## Support

For Bunny Stream documentation: https://docs.bunny.net/stream

For this integration issues:
1. Check Laravel logs
2. Review console command output
3. Test webhook endpoint at: `POST /webhooks/bunny` (with mock Bunny payload)
