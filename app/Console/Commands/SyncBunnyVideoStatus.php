<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Services\BunnyStreamService;
use Illuminate\Console\Command;

/**
 * Sync video status from Bunny Stream.
 *
 * This command checks Bunny Stream for videos with pending/processing status
 * and updates them if processing is complete. Useful as a fallback if webhooks
 * are missed or delayed.
 *
 * Usage:
 *   php artisan bunny:sync-video-status                  # Check all pending videos
 *   php artisan bunny:sync-video-status --lesson-id=123  # Check specific lesson
 */
class SyncBunnyVideoStatus extends Command
{
    protected $signature = 'bunny:sync-video-status {--lesson-id=}';

    protected $description = 'Sync video status from Bunny Stream for lessons with pending/processing videos';

    public function handle(BunnyStreamService $bunnyService): int
    {
        // Find all lessons with Bunny videos in pending/processing state
        $query = Lesson::where('video_type', 'bunny')
            ->whereNotNull('video_url')
            ->whereIn('video_status', ['pending', 'processing']);

        if ($this->option('lesson-id')) {
            $query->where('id', $this->option('lesson-id'));
        }

        $lessons = $query->get();

        if ($lessons->isEmpty()) {
            $this->info('No lessons with pending/processing Bunny videos found.');
            return self::SUCCESS;
        }

        $this->info("Found {$lessons->count()} lesson(s) to check...\n");

        $updated = 0;
        $failed = 0;
        $unchanged = 0;

        foreach ($lessons as $lesson) {
            $this->line("Checking lesson #{$lesson->id}: {$lesson->title}");

            try {
                // Fetch video metadata from Bunny API
                $videoData = $bunnyService->getVideo($lesson->video_url);

                if (!$videoData) {
                    $this->warn("  ! Video not found on Bunny Stream");
                    $failed++;
                    continue;
                }

                // Check transcoding status from Bunny response
                // Status field may vary based on Bunny API - check envelope and metadata
                $transcodingProgress = $videoData['transcodingProgress'] ?? 0;
                $status = $videoData['status'] ?? null;

                $this->line("  Status: {$status}, Transcoding: {$transcodingProgress}%");

                // Update status based on Bunny response
                if ($transcodingProgress === 100 || $transcodingProgress === null) {
                    // Video is transcoded and ready to play
                    $lesson->update([
                        'video_status' => 'ready',
                        'video_processed_at' => now(),
                    ]);

                    $this->info("  ✓ Updated to 'ready'");
                    $updated++;
                } else {
                    $this->line("  → Still processing ({$transcodingProgress}%)");
                    $unchanged++;
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Error: {$e->getMessage()}");
                $failed++;
            }
        }

        // Summary
        $this->newLine();
        $this->info('Sync completed!');
        $this->line("  Updated: {$updated}");
        $this->line("  Still processing: {$unchanged}");
        $this->line("  Errors: {$failed}");

        return self::SUCCESS;
    }
}
