# Cloudinary Best Practices Implementation

## What's Been Implemented

This implementation follows Cloudinary industry best practices for video storage, processing, and delivery in educational platforms.

### 1. **Upload Service** (`CloudinaryUploadService`)
- **Generates signed upload tokens** for client-side uploads
- **Dynamic folder organization** based on subject/topic
- **Webhook signature validation** for security
- **Unsigned uploads** with upload presets (simpler, faster)

### 2. **Webhook Handler** (`CloudinaryWebhookController`)
- **Listens for Cloudinary events**:
  - `upload_success` - Video uploaded
  - `resource_ready` - Transcoding complete
  - `error` - Processing failed
- **Updates lesson status** automatically
- **Tracks processing time** with timestamps
- **Route**: `POST /webhooks/cloudinary` (rate limited to 60 requests/min)

### 3. **Video Processing Tracking**
**New columns added to `lessons` table:**
- `video_status` - pending, processing, ready, failed
- `video_processed_at` - timestamp when transcoding completed

**Lesson can now display:**
```php
// In lesson view or admin
if ($lesson->video_status === 'ready') {
    // Show playback controls
} elseif ($lesson->video_status === 'processing') {
    // Show "Video is being processed..."
} elseif ($lesson->video_status === 'failed') {
    // Show error message
}
```

### 4. **Streaming URLs** (Multiple formats)

#### HLS (HTTP Live Streaming)
```php
$hlsUrl = $videoService->getHlsStreamingUrl($videoPublicId);
// Format: m3u8 with adaptive bitrate
// Best for: Mobile devices, live streaming fallback
// Features: Automatic quality selection, wide compatibility
```

#### DASH (Dynamic Adaptive Streaming over HTTP)
```php
$dashUrl = $videoService->getDashStreamingUrl($videoPublicId);
// Format: mpd with adaptive bitrate
// Best for: Professional content, modern browsers
// Features: Higher quality, lower latency
```

#### Optimized MP4
```php
$optimizedUrl = $videoService->getOptimizedUrl($videoPublicId);
// Auto selects best format (H.264 or VP9)
// Auto selects bitrate based on device
// Best for: Universal playback
```

#### Thumbnail
```php
$thumbnailUrl = $videoService->getThumbnail($videoPublicId, 320, 180);
// Auto-optimized JPEG thumbnail
// Used for: Lesson cards, preview images
```

### 5. **Video Transformations** Applied Automatically
- ✅ **Auto Quality** - Cloudinary adjusts based on device/bandwidth
- ✅ **Auto Format** - Serves H.264, VP9, or AV1 based on browser
- ✅ **Auto Bitrate** - Adjusts video bitrate for connection speed
- ✅ **Auto Audio** - AAC codec for universal compatibility
- ✅ **Authenticated Delivery** - Signed URLs with 24-hour expiry
- ✅ **CDN Caching** - Cloudinary's global CDN handles caching

### 6. **Security Features**
- 🔐 **Signed URLs** - Prevents direct linking to videos
- 🔐 **Webhook Validation** - Verifies Cloudinary signature
- 🔐 **Server-side deletion** - Only authenticated admin can delete
- 🔐 **Folder-based access control** - Videos organized by subject/topic
- 🔐 **Time-limited URLs** - 24-hour expiry on all video links

## How to Use

### Setup (First Time)

1. **Create Cloudinary Upload Preset**
   - Go to https://console.cloudinary.com/settings/upload
   - Create a new preset named `future-academy-lessons`
   - Enable "Unsigned" mode
   - Set folder to `future-academy/lessons/uploads`
   - Enable eager transformations for auto-transcoding
   - Note the preset name

2. **Add to .env**
   ```env
   CLOUDINARY_UPLOAD_PRESET=future-academy-lessons
   ```

3. **Add webhook to Cloudinary**
   - In dashboard → Settings → Webhooks
   - Add webhook URL: `https://future-academy.test/webhooks/cloudinary`
   - Select events: `upload_success`, `resource_ready`, `error`
   - Cloudinary will send notifications to this endpoint

4. **Run migration**
   ```bash
   php artisan migrate
   ```

### In Your Lesson View

**Option 1: HLS Streaming (Recommended for most cases)**
```blade
<video controls width="100%">
    <source src="{{ $lesson->video_service->getHlsStreamingUrl($lesson->video_url) }}" type="application/x-mpegURL">
    Your browser doesn't support HTML5 video.
</video>
```

**Option 2: Optimized MP4**
```blade
<video controls width="100%">
    <source src="{{ $lesson->video_service->getOptimizedUrl($lesson->video_url) }}" type="video/mp4">
    Your browser doesn't support HTML5 video.
</video>
```

**Option 3: With Thumbnail Poster**
```blade
<video controls width="100%" poster="{{ $lesson->video_service->getThumbnail($lesson->video_url) }}">
    <source src="{{ $lesson->video_service->getHlsStreamingUrl($lesson->video_url) }}" type="application/x-mpegURL">
</video>
```

### Check Video Status
```php
// In admin or Filament resource
if ($lesson->video_status === 'ready') {
    // Safe to use
} elseif ($lesson->video_status === 'processing') {
    // Still transcoding
} else {
    // Failed or pending
}
```

## Benefits of This Approach

✅ **Performance**
- HLS/DASH provides adaptive bitrate streaming
- Multiple quality levels served automatically
- Global CDN caching reduces bandwidth

✅ **User Experience**
- No buffering with adaptive streaming
- Auto-quality based on device/connection
- Works on all modern browsers and devices

✅ **Security**
- Signed URLs prevent hotlinking
- Webhook validation ensures authenticity
- Server-side storage management

✅ **Scalability**
- Cloudinary handles transcoding
- No server CPU used for encoding
- Unlimited simultaneous streams

✅ **Reliability**
- Automatic webhook retries if they fail
- Processing status tracking
- Error notifications

## Webhook Status Updates

When a video is uploaded:

1. **Upload starts** → `video_status` = "pending"
2. **Upload completes** → Webhook received (upload_success)
3. **Transcoding starts** → `video_status` = "processing"
4. **Transcoding completes** → Webhook received (resource_ready) → `video_status` = "ready"
5. **Ready for playback** → Lesson shows video player

If transcoding fails → Webhook received (error) → `video_status` = "failed"

## Database Schema

```sql
ALTER TABLE lessons ADD COLUMN video_status VARCHAR(255) DEFAULT 'pending';
ALTER TABLE lessons ADD COLUMN video_processed_at TIMESTAMP NULL;
ALTER TABLE lessons ADD INDEX (video_type, video_status);
```

## API Methods Available

### VideoSigningService
- `getSignedUrl($publicId)` - Basic signed URL
- `getHlsStreamingUrl($publicId)` - HLS format
- `getDashStreamingUrl($publicId)` - DASH format
- `getOptimizedUrl($publicId)` - Auto-optimized MP4
- `getThumbnail($publicId)` - Thumbnail image
- `getMetadata($publicId)` - Video info (duration, resolution, etc)
- `delete($videoPath)` - Delete from Cloudinary

### CloudinaryUploadService
- `getUploadSignature($folder)` - Get upload token
- `getSignatureForLesson($subjectId, $topicId)` - Upload token with lesson context
- `validateWebhookSignature($data, $signature)` - Verify webhook authenticity

## Next Steps (Optional)

1. **Add analytics** - Track video views, watch time
2. **Add subtitles** - Upload SRT files to Cloudinary
3. **Add chapters** - Implement video chapters/timeline markers
4. **Add watermarks** - Add school logo to videos
5. **Add DRM** - Restrict video downloads (Cloudinary token auth already does this)

## Troubleshooting

**Videos not processing?**
- Check webhook is configured in Cloudinary dashboard
- Verify `CLOUDINARY_UPLOAD_PRESET` is set in .env
- Check Cloudinary API logs for errors

**Signed URLs failing?**
- Ensure video_status is "ready" before playback
- Check video was uploaded to correct folder
- Verify CLOUDINARY_API_SECRET is correct

**Webhook not triggering?**
- Test webhook in Cloudinary dashboard settings
- Check `video_processed_at` timestamp in database
- Review Laravel logs for webhook errors

