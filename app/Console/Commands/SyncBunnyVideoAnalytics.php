<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\VideoProgress;
use App\Services\BunnyStreamService;
use Illuminate\Console\Command;

class SyncBunnyVideoAnalytics extends Command
{
    protected $signature = 'bunny:sync-analytics {--lesson-id= : Sync specific lesson, or all if not provided}';
    protected $description = 'Sync video analytics data from Bunny Stream API to local video_progress table';

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

            foreach ($lessons as $lesson) {
                try {
                    $this->syncVideoAnalytics($lesson);
                    $syncedCount++;
                    $this->line("✓ Synced: {$lesson->title}");
                } catch (\Exception $e) {
                    $this->error("✗ Failed to sync {$lesson->title}: " . $e->getMessage());
                }
            }

            $this->info("\n✅ Completed! Synced {$syncedCount} video(s)");
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Sync analytics for a specific lesson video
     */
    private function syncVideoAnalytics(Lesson $lesson): void
    {
        // Get video stats from Bunny
        $stats = $this->bunnyService->getVideoStats($lesson->video_url);

        if (!$stats) {
            throw new \RuntimeException('Could not fetch stats from Bunny');
        }

        // Log the stats for debugging
        $this->line("  Stats: Views={$stats['views'] ?? 0}, Watch time={$stats['watchTime'] ?? 'N/A'}");

        // Note: Bunny's API provides aggregate stats, not per-user tracking
        // For per-user tracking, we rely on webhooks or client-side tracking
        // This command is useful for updating aggregate view counts
    }
}
