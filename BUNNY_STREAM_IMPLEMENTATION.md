# Bunny Stream Implementation Summary

## What's Been Implemented

### ✅ Core Integration
- **BunnyStreamService** (`app/Services/BunnyStreamService.php`) - Complete API wrapper with all methods:
  - `createVideo()` - Create video object
  - `uploadVideo()` - Upload file to Bunny
  - `fetchVideoFromUrl()` - Import from remote URL (for migrations)
  - `getVideo()` - Retrieve metadata
  - `getEmbedUrl()` - Generate signed embed URL with token auth
  - `deleteVideo()` - Remove from Bunny

### ✅ Configuration
- Added Bunny configuration to `config/services.php`:
  - `BUNNY_STREAM_API_KEY`
  - `BUNNY_STREAM_LIBRARY_ID`
  - `BUNNY_STREAM_EMBED_URL`
  - `BUNNY_STREAM_EMBED_TOKEN_KEY`

### ✅ Filament Admin Interface
- Updated `LessonForm.php` with Bunny as default video upload platform
- Custom FileUpload handler that:
  - Creates video object on Bunny
  - Uploads file to Bunny
  - Returns videoId for storage
  - Sets `video_status` to 'processing'
  - Shows success/error notifications

### ✅ Model Integration
- Updated `Lesson.php`:
  - Added `BunnyStreamService` import
  - `getVideoEmbedUrl()` recognizes 'bunny' type
  - `getBunnyEmbedUrl()` generates signed URLs with SHA256 token authentication
  - Supports configurable expiration (default 24 hours)

### ✅ Video Playback
- Updated `lesson-view.blade.php` to display Bunny videos:
  - Detects `video_type === 'bunny'`
  - Renders iframe embed instead of HTML5 video tag
  - Supports token-based authentication

### ✅ Webhook Handling
- Created `BunnyWebhookController.php`:
  - Handles `VideoTranscodingComplete` event
  - Handles `VideoEncodingFailed` event
  - Updates lesson `video_status` accordingly
  - Dispatches Livewire events for real-time UI updates
- Added webhook route: `POST /webhooks/bunny`

### ✅ Console Commands
1. **SyncBunnyVideoStatus** - Fallback for missed webhooks
   - Checks all pending/processing videos on Bunny
   - Updates database when transcoding completes
   - Usage: `php artisan bunny:sync-video-status [--lesson-id=X]`

2. **MigrateCloudinaryToBunny** - Gradual migration tool
   - Migrates videos from Cloudinary to Bunny
   - Supports dry-run mode (no --confirm flag)
   - Optionally deletes from Cloudinary (--delete flag)
   - Usage: `php artisan bunny:migrate-from-cloudinary [--confirm] [--lesson-id=X] [--delete]`

### ✅ Documentation
- Comprehensive `BUNNY_STREAM_INTEGRATION.md` guide with:
  - Setup instructions
  - Architecture overview
  - Workflow diagrams (upload → processing → playback)
  - Security (token authentication)
  - Console command reference
  - Webhook configuration
  - Troubleshooting guide
  - Migration strategies
  - FAQ

## What's Ready to Test

### 1. Upload a Video (Admin Interface)
1. Go to `/admin/lessons` → Create Lesson
2. Upload video file through Filament form
3. Video type auto-selects "Bunny Stream"
4. Check database: `video_type = 'bunny'`, `video_url = {guid}`, `video_status = 'processing'`

### 2. Configure Webhook (Required for auto-status update)
1. Get Bunny API credentials (Library ID, API Key)
2. Set in `.env`:
   ```env
   BUNNY_STREAM_API_KEY=your-key
   BUNNY_STREAM_LIBRARY_ID=your-library-id
   ```
3. In Bunny Dashboard, configure webhook:
   - URL: `https://yourdomain.com/webhooks/bunny`
   - Events: "Video Encoding Completed", "Video Encoding Failed"
4. When video finishes processing, Bunny will POST to webhook
5. Lesson `video_status` auto-updates to 'ready'

### 3. Manual Status Sync (Fallback)
```bash
# Check all pending videos
php artisan bunny:sync-video-status

# Check one lesson
php artisan bunny:sync-video-status --lesson-id=42
```

### 4. View Video (Student Interface)
1. Student logs in, views lesson
2. `lesson-view.blade.php` detects `video_type = 'bunny'`
3. Calls `$lesson->getVideoEmbedUrl()` → `getBunnyEmbedUrl()`
4. Returns signed iframe URL with token (if enabled)
5. Student sees embedded Bunny player with:
   - Adaptive bitrate streaming
   - Theater mode, captions, etc.
   - Token expires after 24 hours

### 5. Migrate Videos from Cloudinary (Optional)
```bash
# Dry run (no changes)
php artisan bunny:migrate-from-cloudinary

# Actually migrate
php artisan bunny:migrate-from-cloudinary --confirm [--delete]
```

## Architecture Diagram

```
Upload Flow:
┌─────────────────────────────────────────────┐
│ Admin uploads video via Filament form       │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│ LessonForm FileUpload handler               │
│  1. Creates video object on Bunny           │
│  2. Uploads file to Bunny                   │
│  3. Returns videoId for storage             │
│  4. Sets video_status = 'processing'        │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│ Lesson saved in database:                   │
│  video_type = 'bunny'                       │
│  video_url = '{videoId}'                    │
│  video_status = 'processing'                │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│ Bunny Stream transcodes video (~15-30 min)  │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│ Bunny webhooks success to:                  │
│  POST /webhooks/bunny                       │
│  EventType = 'VideoTranscodingComplete'     │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│ BunnyWebhookController updates:             │
│  video_status = 'ready'                     │
│  video_processed_at = now()                 │
│  Dispatches 'video-ready' event             │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│ Student can now play video:                 │
│  Iframe → getVideoEmbedUrl()                │
│  → getBunnyEmbedUrl()                       │
│  → Service.getEmbedUrl() with token         │
│  → Returns signed URL                       │
│  → Bunny iframe renders player              │
└─────────────────────────────────────────────┘

Playback Flow:
┌─────────────────────────────────────────────┐
│ Student views lesson                        │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│ LessonView detects video_type = 'bunny'    │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│ Calls $lesson->getVideoEmbedUrl()           │
│  - Detects video_type = 'bunny'             │
│  - Calls getBunnyEmbedUrl()                 │
│  - Generates expiration timestamp           │
│  - Calls BunnyStreamService::getEmbedUrl()  │
│  - Creates token: SHA256(key+id+expires)    │
│  - Returns: iframe.mediadelivery.net/...    │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│ Template renders iframe with signed URL     │
│  <iframe src="{signedUrl}" ...></iframe>    │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│ Bunny iframe player loads:                  │
│  - Verifies token signature                 │
│  - Checks expiration                        │
│  - Loads HLS manifest                       │
│  - Streams adaptive bitrate video           │
└─────────────────────────────────────────────┘
```

## Files Created/Modified

### New Files
- `app/Services/BunnyStreamService.php` - API wrapper
- `app/Http/Controllers/BunnyWebhookController.php` - Webhook handler
- `app/Console/Commands/SyncBunnyVideoStatus.php` - Status sync command
- `app/Console/Commands/MigrateCloudinaryToBunny.php` - Migration command
- `BUNNY_STREAM_INTEGRATION.md` - Complete guide

### Modified Files
- `config/services.php` - Added Bunny configuration
- `app/Models/Lesson.php` - Added getBunnyEmbedUrl(), updated getVideoEmbedUrl()
- `app/Filament/Resources/LessonResource/Schemas/LessonForm.php` - Custom upload handler for Bunny
- `resources/views/livewire/lessons/lesson-view.blade.php` - Added Bunny iframe support
- `web.php` - Added POST /webhooks/bunny route

## Next Steps (Optional)

1. **Test Upload Flow**
   - Upload test video through Filament
   - Verify it appears on Bunny Dashboard
   - Check webhook fires when transcoding completes
   - Verify playback in student view

2. **Configure Webhook**
   - Set deployment URL so Bunny can POST to your server
   - Test webhook endpoint directly with curl
   - Monitor Laravel logs for webhook processing

3. **Enable Token Authentication** (for paid/protected content)
   - Generate secure random key for `BUNNY_STREAM_EMBED_TOKEN_KEY`
   - Verify signed URLs work correctly
   - Test token expiration

4. **Migrate Existing Videos** (optional, can happen gradually)
   ```bash
   php artisan bunny:migrate-from-cloudinary --confirm [--delete]
   ```

5. **Monitor Performance**
   - Check webhook response times
   - Monitor upload success rate
   - Track average transcoding time
   - Analyze CDN performance

## Configuration Checklist

- [ ] Set `BUNNY_STREAM_API_KEY` in `.env`
- [ ] Set `BUNNY_STREAM_LIBRARY_ID` in `.env`
- [ ] Set `BUNNY_STREAM_EMBED_URL` in `.env` (usually default)
- [ ] Set `BUNNY_STREAM_EMBED_TOKEN_KEY` in `.env` (optional for token auth)
- [ ] Configure webhook in Bunny Dashboard
- [ ] Test upload through Filament admin
- [ ] Monitor webhook processing via logs
- [ ] Verify playback in student view
- [ ] Optionally migrate existing Cloudinary videos

## Support & Troubleshooting

See `BUNNY_STREAM_INTEGRATION.md` for:
- Setup troubleshooting
- Webhook debugging
- Common error messages
- FAQ section
- API reference
