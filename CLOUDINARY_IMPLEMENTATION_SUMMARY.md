# Cloudinary Best Practices Implementation - Quick Setup

## Files Created/Modified

### New Files
1. **`app/Services/CloudinaryUploadService.php`**
   - Generates upload signatures
   - Validates webhook signatures
   - Creates dynamic folder paths

2. **`app/Http/Controllers/CloudinaryWebhookController.php`**
   - Handles upload_success, resource_ready, error events
   - Updates lesson video_status automatically
   - Validates Cloudinary webhook authenticity

3. **`database/migrations/2026_01_13_add_video_status_to_lessons.php`**
   - Adds `video_status` column (pending, processing, ready, failed)
   - Adds `video_processed_at` timestamp
   - Creates index for status queries

### Updated Files
1. **`app/Models/Lesson.php`**
   - Added `video_status` and `video_processed_at` to fillable
   - Added casts for new columns

2. **`app/Services/VideoSigningService.php`**
   - Added `getHlsStreamingUrl()` - HLS adaptive streaming
   - Added `getDashStreamingUrl()` - DASH adaptive streaming
   - Added `getOptimizedUrl()` - Auto-optimized MP4
   - Added `getThumbnail()` - Generate thumbnails
   - All with signed URLs and auto-quality

3. **`routes/web.php`**
   - Added webhook route: `POST /webhooks/cloudinary`

## Quick Setup Checklist

- [ ] Run migration: `php artisan migrate`
- [ ] Create upload preset in Cloudinary dashboard
- [ ] Add to `.env`: `CLOUDINARY_UPLOAD_PRESET=future-academy-lessons`
- [ ] Add webhook in Cloudinary → Settings → Webhooks
  - URL: `https://future-academy.test/webhooks/cloudinary`
  - Events: upload_success, resource_ready, error
- [ ] Test by uploading a video in Filament
- [ ] Check `video_status` updates automatically

## Key Features

✅ **Adaptive Streaming** - HLS/DASH with auto quality selection
✅ **Auto Transcoding** - Cloudinary handles all video encoding
✅ **Status Tracking** - Real-time webhook notifications
✅ **Signed URLs** - 24-hour expiry, prevents hotlinking
✅ **Global CDN** - Cloudinary's edge network caches videos
✅ **Folder Organization** - Videos organized by subject/topic
✅ **Error Handling** - Failed transcodes tracked
✅ **Security Validated** - Webhook signature verification

## Usage Examples

```php
// Get HLS streaming URL (recommended)
$hlsUrl = $videoService->getHlsStreamingUrl($lesson->video_url);

// Get optimized MP4
$mp4Url = $videoService->getOptimizedUrl($lesson->video_url);

// Get thumbnail
$thumb = $videoService->getThumbnail($lesson->video_url);

// Check video status
if ($lesson->video_status === 'ready') {
    // Safe to play
}
```

## Webhook Flow

1. Video uploaded → Filament saves lesson
2. Cloudinary processes video
3. Webhook notification sent to `/webhooks/cloudinary`
4. Handler updates `video_status` and `video_processed_at`
5. Video ready for playback

## Performance Improvements

- **HLS** provides adaptive bitrate (auto quality selection)
- **DASH** for professional/modern browsers
- **Global CDN** reduces latency worldwide
- **Auto transcoding** happens once, cached forever
- **Signed URLs** allow time-limited access without database lookup

## Security

- 🔐 Signed URLs prevent direct linking
- 🔐 Webhook validation ensures authenticity
- 🔐 Server manages all video operations
- 🔐 24-hour expiry on all signed URLs
- 🔐 Folder-based organization for access control

