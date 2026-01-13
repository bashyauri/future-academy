<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Services\VideoSigningService;
use Illuminate\Console\Command;

class SyncCloudinaryVideoStatus extends Command
{
    protected $signature = 'cloudinary:sync-video-status {--lesson-id=}';

    protected $description = 'Sync video status from Cloudinary for lessons with pending/processing status';

    public function handle(VideoSigningService $videoService): int
    {
        $query = Lesson::where('video_type', 'local')
            ->whereNotNull('video_url')
            ->whereIn('video_status', ['pending', 'processing']);

        if ($this->option('lesson-id')) {
            $query->where('id', $this->option('lesson-id'));
        }

        $lessons = $query->get();

        if ($lessons->isEmpty()) {
            $this->info('No lessons with pending/processing videos found.');
            return self::SUCCESS;
        }

        $this->info("Found {$lessons->count()} lesson(s) to check...");

        foreach ($lessons as $lesson) {
            $this->line("Checking lesson #{$lesson->id}: {$lesson->title}");

            try {
                // Try to get metadata from Cloudinary
                $metadata = $videoService->getMetadata($lesson->video_url);

                if ($metadata) {
                    // Video exists in Cloudinary - mark as ready
                    $lesson->update([
                        'video_status' => 'ready',
                        'video_processed_at' => now(),
                    ]);

                    $this->info("  ✓ Updated to 'ready'");
                } else {
                    $this->warn("  ! Could not fetch metadata - video may still be processing");
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Error: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('Sync completed!');

        return self::SUCCESS;
    }
}
