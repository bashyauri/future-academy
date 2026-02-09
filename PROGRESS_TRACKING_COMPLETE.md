# Progress Tracking System - Complete Setup Guide

## Overview
Your progress tracking system is now fully operational. Here's what's been set up:

---

## Database Tables Created

### 1. **user_progress** (14 records currently)
Tracks overall lesson and quiz completion per student.

**Columns:**
- `user_id` - Student ID
- `lesson_id` - Lesson reference
- `quiz_id` - Quiz reference
- `type` - Type: 'lesson' or 'quiz'
- `is_completed` - Boolean completion status
- `progress_percentage` - 0-100%
- `time_spent_seconds` - Total time spent
- `started_at` - When accessed
- `completed_at` - When finished
- `metadata` - JSON for extensibility

**Sample Data:**
```
User 1 started Lesson 1 on 2025-12-25 at 09:12
User 1 completed Lesson 1 (100% progress)
```

### 2. **video_progress** (Now ready for data)
Tracks individual video watching metrics.

**Columns:**
- `user_id` - Student ID
- `video_id` - Bunny Stream video ID
- `lesson_id` - Lesson reference
- `watch_time` - Seconds watched
- `percentage` - Video %age watched (0-100)
- `completed` - Marked completed (90%+ watched)

**How it works:**
- Records created when student marks lesson complete
- Calculates watch percentage based on time spent on page
- 5 minutes viewing = 100% completion (configurable)
- Auto-marks as completed at 90%+ watched

### 3. **video_analytics** (For future Bunny syncs)
For storing Bunny API analytics data (views, unique viewers, completion rates)

---

## Code Changes Made

### 1. **LessonView.php** - Fixed video ID tracking
**Before (BROKEN):**
```php
VideoProgress::updateOrCreate([
    'user_id' => auth()->id(),
    'video_id' => $this->lesson->id,  // ❌ Using lesson ID instead of video ID
]);
```

**After (FIXED):**
```php
VideoProgress::updateOrCreate([
    'user_id' => auth()->id(),
    'lesson_id' => $this->lesson->id,
    'video_id' => $this->lesson->video_url,  // ✅ Using Bunny video ID
]);
```

**Methods Updated:**
- `trackVideoProgress()` - Called when marking lesson complete
- `trackVideoWatch()` - Called periodically via JavaScript event

### 2. **VideoProgress Model** - Added lesson_id support
```php
protected $fillable = [
    'user_id',
    'video_id',
    'lesson_id',  // ✅ Added
    'watch_time',
    'percentage',
    'completed',
];

public function lesson(): BelongsTo
{
    return $this->belongsTo(Lesson::class);  // ✅ Added
}
```

---

## How Progress Tracking Works

### When Student Views a Lesson:
1. `LessonView::mount()` runs
2. Creates `UserProgress` record with `started_at = now()`
3. Stores `$this->startTime` for measuring duration

### When Student Marks Lesson Complete:
1. Checks if lesson has associated quiz → Forces completion if required
2. If lesson has Bunny video → `trackVideoProgress()` is called
3. Calculates watch time based on time since `$this->startTime`
4. Creates/updates `VideoProgress` record
5. Marks `UserProgress` as `is_completed = 1` and `completed_at = now()`

### Watch Percentage Calculation:
```
watchPercentage = min(100, (timeSpent / 300) * 100)
// 300 seconds (5 minutes) = 100%
// 150 seconds (2.5 minutes) = 50%
// 270+ seconds = 100%
```

---

## Testing the System

### 1. **Check User Progress Records:**
```sql
SELECT * FROM user_progress 
WHERE user_id = 1 
ORDER BY created_at DESC;
```

### 2. **Check Video Progress Records (New Data):**
```sql
SELECT * FROM video_progress 
WHERE user_id = 1 
ORDER BY created_at DESC;
```

### 3. **View Completion Data:**
```sql
SELECT 
    u.name,
    l.title,
    up.progress_percentage,
    up.is_completed,
    vp.percentage as video_watched,
    vp.completed as video_completed,
    up.completed_at
FROM user_progress up
LEFT JOIN video_progress vp ON up.user_id = vp.user_id AND up.lesson_id = vp.lesson_id
LEFT JOIN users u ON up.user_id = u.id
LEFT JOIN lessons l ON up.lesson_id = l.id
WHERE up.is_completed = 1;
```

---

## Analytics Dashboard Queries

### Student Lesson Completion Rate:
```php
$completionRate = UserProgress::where('user_id', $userId)
    ->where('type', 'lesson')
    ->where('is_completed', 1)
    ->count() / UserProgress::where('user_id', $userId)
    ->where('type', 'lesson')
    ->count() * 100;
```

### Average Video Watch Time:
```php
$avgWatchTime = VideoProgress::where('user_id', $userId)
    ->avg('watch_time'); // in seconds
```

### Video Completion Rate:
```php
$videoCompletionRate = VideoProgress::where('user_id', $userId)
    ->where('completed', 1)
    ->count() / VideoProgress::where('user_id', $userId)
    ->count() * 100;
```

### Top Lessons by Views:
```php
$topLessons = UserProgress::where('type', 'lesson')
    ->groupBy('lesson_id')
    ->selectRaw('lesson_id, COUNT(*) as view_count, 
                 SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completion_count')
    ->with('lesson')
    ->get();
```

---

## Configuration Options

### Adjust Video Watch Percentage Calculation:
In `LessonView.php`, modify the time calculation:
```php
// Current: 5 minutes = 100%
$watchPercentage = min(100, ($timeSpent / 300) * 100);

// Change to 10 minutes = 100%
$watchPercentage = min(100, ($timeSpent / 600) * 100);
```

### Change Completion Threshold:
In `VideoProgress Model`:
```php
// Consider 80% watched as completed instead of 90%
'completed' => $percentage >= 80,
```

---

## Key Features Implemented

✅ **Lesson Completion Tracking**
- Records when students start/complete lessons
- Tracks time spent on lesson pages
- Stores progress percentage

✅ **Video Watch Metrics**
- Tracks Bunny video ID (not lesson ID)
- Records watch time in seconds
- Calculates percentage watched
- Auto-completes at 90% watched

✅ **Quiz Integration**
- Lesson completion requires quiz completion first
- Quiz attempts tracked separately

✅ **Analytics Ready**
- Normalized schema for efficient queries
- Indexed on user_id, lesson_id for fast lookups
- Metadata field for storing custom tracking data

---

## Migration Status

All migrations completed:
```
✅ 2026_02_09_create_user_progress_table
✅ 2026_02_09_create_video_progress_table  
✅ 2026_02_09_create_video_analytics_table
```

Current data:
- **user_progress**: 14 records (lessons completed)
- **video_progress**: 0 records (will populate when students complete lessons)
- **video_analytics**: (ready for Bunny API syncs)

---

## Next Steps

### 1. **Create Analytics Dashboard**
Build Filament dashboard showing:
- Student completion rates
- Time-on-task metrics
- Video engagement stats
- Quiz performance

### 2. **Implement Bunny Analytics Sync**
Optional: Pull Bunny API data into `video_analytics` table:
```php
// Get Bunny analytics
$analytics = $bunnyService->getVideoStatistics($videoId);
// Store in video_analytics
```

### 3. **Add Real-Time Progress Updates**
If needed, enable JavaScript-based video progress tracking via Bunny's player API:
```javascript
// Track actual video playback percentage
const player = bunny.player(...);
player.on('timeupdate', (currentTime, duration) => {
    livewire.dispatch('track-video-watch', {
        watchPercentage: (currentTime / duration) * 100
    });
});
```

### 4. **Monitor Data Quality**
Run periodic checks:
```sql
-- Find students still viewing lessons after 1 hour
SELECT * FROM user_progress 
WHERE is_completed = 0 
AND started_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Find zero watch-time videos
SELECT * FROM video_progress 
WHERE watch_time = 0 
AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY);
```

---

## Support

**Issue**: Video Progress not updating
- Check if lesson has `video_url` field populated with Bunny video ID
- Verify video_type is set to 'bunny'
- Check server logs for errors in trackVideoProgress()

**Issue**: Time Spent showing 0
- Verify `$this->startTime` is set in LessonView::mount()
- Check if markComplete() is being called
- Ensure lesson completion quiz (if required) is marked complete first

**Issue**: Empty video_progress table
- First completion after this fix will populate data
- Check LessonView.php is using updated code
- Verify Bunny video IDs are stored in lesson.video_url (not file paths)

