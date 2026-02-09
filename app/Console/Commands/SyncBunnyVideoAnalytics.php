<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\VideoAnalytics;
use App\Services\BunnyStreamService;
use Illuminate\Console\Command;

class SyncBunnyVideoAnalytics extends Command
{
    protected $signature = 'bunny:sync-analytics {--lesson-id= : Sync specific lesson, or all if not provided} {--force : Force sync even if recently synced}';
    protected $description = 'Sync video analytics data from Bunny Stream API to local video_analytics table';

    protected BunnyStreamService $bunnyService;

    public function __construct(BunnyStreamService $bunnyService)
    {
        parent::__construct();
        $this->bunnyService = $bunnyService;
    }

    public function handle(): int
    {
        $this->info('🎬 Syncing Bunny video analytics...');

        try {
            $lessonId = $this->option('lesson-id');
            $force = $this->option('force');

            // Get lessons with Bunny videos
            $query = Lesson::where('video_type', 'bunny')
                ->whereNotNull('video_url')
                ->where('video_status', 'ready'); // Only sync ready videos

            if ($lessonId) {
                $query->where('id', $lessonId);
            }

            $lessons = $query->get();

            if ($lessons->isEmpty()) {
                $this->warn('No Bunny videos found to sync');
                return 0;
            }

            $this->info("Found {$lessons->count()} Bunny video(s) to sync\n");

            $syncedCount = 0;
            $skippedCount = 0;

            foreach ($lessons as $lesson) {
                try {
                    // Check if recently synced (within 1 hour)
                    $existingAnalytics = VideoAnalytics::where('lesson_id', $lesson->id)
                        ->where('last_synced_at', '>', now()->subHour())
                        ->first();

                    if ($existingAnalytics && !$force) {
                        $this->line("⊘ Skipped (recently synced): {$lesson->title}");
                        $skippedCount++;
                        continue;
                    }

                    $this->syncVideoAnalytics($lesson);
                    $syncedCount++;
                    $this->line("✓ Synced: {$lesson->title}");
                } catch (\Exception $e) {
                    $this->error("✗ Failed to sync {$lesson->title}: " . $e->getMessage());
                }
            }

            $this->info("\n✅ Completed! Synced {$syncedCount} video(s), Skipped {$skippedCount}");
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Sync analytics for a specific lesson video from Bunny API
     */
    private function syncVideoAnalytics(Lesson $lesson): void
    {
        // Get video stats from Bunny using their API
        $stats = $this->bunnyService->getVideoStats($lesson->video_url);

        if (!$stats) {
            \Log::warning('Could not fetch Bunny stats', ['video_id' => $lesson->video_url]);
            return;
        }

        // Parse Bunny's response structure
        // Bunny returns stats with keys like: views, watchTime, averageWatchTime, etc.
        $totalViews = intval($stats['views'] ?? $stats['totalViews'] ?? 0);
        $totalWatchTime = intval($stats['watchTime'] ?? $stats['totalWatchTime'] ?? 0);
        $averageWatchTime = floatval($stats['averageWatchTime'] ?? 0);
        $uniqueViewers = intval($stats['uniqueViewers'] ?? 0);
        $completionRate = floatval($stats['completionRate'] ?? 0);
        $averageBitrate = intval($stats['averageBitrate'] ?? 0);
        $topCountry = $stats['topCountry'] ?? null;
        $topDevice = $stats['topDevice'] ?? null;

        // Update or create video analytics record
        VideoAnalytics::updateOrCreate(
            [
                'lesson_id' => $lesson->id,
                'bunny_video_id' => $lesson->video_url,
            ],
            [
                'total_views' => $totalViews,
                'total_watch_time' => $totalWatchTime,
                'average_watch_time' => $averageWatchTime,
                'unique_viewers' => $uniqueViewers,
                'completion_rate' => $completionRate,
                'average_bitrate' => $averageBitrate,
                'top_country' => $topCountry,
                'top_device' => $topDevice,
                'last_synced_at' => now(),
            ]
        );

        // Log details
        $this->line("  📊 Stats: Views=$totalViews, Viewers=$uniqueViewers, Completion={$completionRate}%");

        \Log::info('Synced Bunny video analytics', [
            'lesson_id' => $lesson->id,
            'total_views' => $totalViews,
            'completion_rate' => $completionRate,
        ]);
    }
}
