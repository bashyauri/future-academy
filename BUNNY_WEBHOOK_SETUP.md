# Bunny Stream Webhook Integration Guide

## Overview

Your Future Academy platform now has **dual video progress tracking**:

1. **Time-Based Tracking** (Client-side) - Auto-tracks every 30 seconds
2. **Webhook-Based Tracking** (Server-side) - Real-time updates from Bunny

---

## How It Works

### Time-Based Tracking (Always Active)
```
User watches video on lesson page
    ↓
Every 30 seconds: Livewire calls trackVideoWatch()
    ↓
Calculates: (timeSpent / 300) * 100 = watch percentage
    ↓
Updates: video_progress table
    ↓
On page exit: Final recording of total watch time
```

**Formula:** 5 minutes of viewing = 100% watched

### Webhook-Based Tracking (From Production Server)
```
User watches video on your hosted app
    ↓
Bunny detects playback event
    ↓
Bunny sends: POST /webhooks/bunny (with analytics data)
    ↓
BunnyWebhookController processes event
    ↓
Updates: video_progress table with real watch data
```

---

## Bunny Dashboard Setup ⚙️

1. **Login to Bunny Dashboard** → bunny.net
2. Go to **Video Library** → **Settings** → **Webhooks**
3. Add webhook URL:
   ```
   https://yourdomain.com/webhooks/bunny
   ```
4. Select events to receive:
   - ✅ `VideoTranscodingComplete` (when video is ready)
   - ✅ `VideoEncodingFailed` (when encoding fails)
   - ✅ `VideoAnalyticsEvent` (when users watch)
   - ✅ `ViewEvent` (view tracking)

5. (Optional) Enable webhook signing for security:
   - Get signing key from Bunny
   - Add to `.env` → `BUNNY_WEBHOOK_SECRET=your_key`

---

## .env Configuration

Ensure you have these variables set:

```env
# Bunny Stream API
BUNNY_STREAM_API_KEY=your_api_key
BUNNY_STREAM_LIBRARY_ID=your_library_id
BUNNY_STREAM_EMBED_URL=https://iframe.mediadelivery.net/embed
BUNNY_STREAM_EMBED_TOKEN_KEY=your_token_key

# Optional: Webhook signing key
BUNNY_WEBHOOK_SECRET=your_webhook_signing_key
```

---

## What Gets Tracked

### In `video_progress` Table:

| Field | Source | Updates |
|-------|--------|---------|
| `user_id` | Livewire or Webhook | Real-time |
| `video_id` | Lesson ID | Once |
| `watch_time` | Time spent on page (seconds) | Every 30 sec + on exit |
| `percentage` | Time-based calculation | Every 30 sec + webhook |
| `completed` | 90%+ watched | When reached |
| `created_at` | First view | Once |
| `updated_at` | After any update | Every update |

---

## Event Types Handled

### 1. `VideoTranscodingComplete`
- **When:** Bunny finishes encoding video
- **Action:** Updates `lessons.video_status = 'ready'`
- **Trigger:** Makes video available for students

### 2. `VideoEncodingFailed`
- **When:** Bunny encounters encoding error
- **Action:** Updates `lessons.video_status = 'failed'`
- **Alert:** Notifies admin to re-upload

### 3. `VideoAnalyticsEvent` / `ViewEvent`
- **When:** User watches video
- **Action:** Creates/updates `video_progress` record
- **Data:** Watch time, percentage, completed status

---

## API Methods Available

### BunnyStreamService

```php
// Get video information
$video = $service->getVideo($videoId);

// Get video statistics
$stats = $service->getVideoStats($videoId);
// Returns: ['views' => 100, 'watchTime' => 86400, ...]

// Get view count
$views = $service->getVideoViewCount($videoId);
```

### Console Commands

```bash
# Sync video analytics from Bunny API
php artisan bunny:sync-analytics

# Sync specific lesson
php artisan bunny:sync-analytics --lesson-id=5

# Sync video status (checks if encoding complete)
php artisan bunny:sync-video-status
```

---

## Database Query Examples

### Get video progress for student

```php
$progress = VideoProgress::where('user_id', $studentId)
    ->where('video_id', $lessonId)
    ->first();

echo $progress->percentage . '%'; // 75%
echo $progress->watch_time; // 450 (seconds)
echo $progress->completed; // true/false
```

### Get all students watching a video

```php
$watchers = VideoProgress::where('video_id', $lessonId)
    ->where('percentage', '>=', 50)
    ->with('user')
    ->get();
```

### Average watch percentage for a lesson

```php
$avgWatch = VideoProgress::where('video_id', $lessonId)
    ->avg('percentage');
// 67.5 (67.5% average)
```

---

## Troubleshooting

### Webhook Not Receiving Events

1. **Check webhook URL is public:**
   ```bash
   curl https://yourdomain.com/webhooks/bunny
   # Should respond, not 404
   ```

2. **Check logs:**
   ```bash
   tail -50 storage/logs/laravel.log | grep -i bunny
   ```

3. **Test webhook manually:**
   ```php
   // In routes/web.php (temporary)
   Route::post('/test-bunny-webhook', function(Request $request) {
       return app(BunnyWebhookController::class)->handle($request);
   });
   ```

4. **Verify Bunny has correct URL:**
   - Bunny Dashboard → Settings → Webhooks
   - Test button to send sample event

### Progress Not Updating

1. **Check video_progress table exists:**
   ```bash
   php artisan migrate
   ```

2. **Verify Bunny video_type = 'bunny':**
   ```php
   $lesson = Lesson::find(1);
   echo $lesson->video_type; // Should be 'bunny'
   echo $lesson->video_url;  // Should be the Bunny GUID
   ```

3. **Check time-based tracking is working:**
   - Watch video for 2+ minutes
   - Check database for new record in `video_progress`

---

## Testing Locally

Since Bunny cannot reach localhost webhooks, use time-based tracking for testing:

```bash
# Watch video for 2+ minutes locally
# Then check database
SELECT * FROM video_progress WHERE user_id = 1;
```

For webhook testing on localhost:
1. Use ngrok: `ngrok http 8000`
2. Configure Bunny webhook to ngrok URL
3. Webhooks will flow through to your local app

---

## Performance Tips

### 1. Index video_progress queries
```sql
ALTER TABLE video_progress ADD INDEX idx_user_video (user_id, video_id);
ALTER TABLE video_progress ADD INDEX idx_video_percentage (video_id, percentage);
```

### 2. Clean old records (optional)
```bash
# Archive records older than 6 months
DELETE FROM video_progress WHERE updated_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

### 3. Cache video stats
```php
$stats = Cache::remember("video_stats:{$videoId}", 3600, function() {
    return BunnyStreamService::getVideoStats($videoId);
});
```

---

## Security

### Webhook Signature Validation (Optional)

1. **Get signing key from Bunny:**
   - Bunny Dashboard → Settings → Webhooks → Get Key

2. **Store in .env:**
   ```env
   BUNNY_WEBHOOK_SECRET=abc123...
   ```

3. **Implement validation in controller:**
   ```php
   private function validateWebhookSignature(Request $request): bool
   {
       $signature = $request->header('X-Bunny-Webhook-Signature');
       $secret = config('services.bunny.webhook_secret');
       $expected = hash_hmac('sha256', $request->getContent(), $secret);
       
       return hash_equals($expected, $signature);
   }
   ```

---

## Next Steps

1. ✅ Configure Bunny webhooks in Dashboard
2. ✅ Test with TestvVideo
3. ✅ Monitor `storage/logs/laravel.log` for webhook events
4. ✅ Verify `video_progress` table updates
5. ✅ Create admin dashboard to view video analytics

---

## Support

For issues, check:
- `/webhooks/bunny` endpoint logs
- `video_progress` table for records
- Bunny Dashboard event logs
- Laravel log: `storage/logs/laravel.log`

