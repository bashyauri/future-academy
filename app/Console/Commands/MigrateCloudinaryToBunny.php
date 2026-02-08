<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Services\BunnyStreamService;
use App\Services\VideoSigningService;
use Illuminate\Console\Command;

/**
 * Migrate videos from Cloudinary to Bunny Stream.
 *
 * This command:
 * 1. Finds all lessons with local (Cloudinary) videos
 * 2. Generates the Cloudinary URL (original, non-HLS)
 * 3. Uploads to Bunny Stream using remote URL fetch
 * 4. Updates the lesson to use Bunny as video_type
 * 5. Optionally deletes from Cloudinary
 *
 * Usage:
 *   php artisan bunny:migrate-from-cloudinary                    # Dry run shows what would migrate
 *   php artisan bunny:migrate-from-cloudinary --confirm           # Actually migrate all videos
 *   php artisan bunny:migrate-from-cloudinary --lesson-id=123     # Migrate one lesson
 *   php artisan bunny:migrate-from-cloudinary --confirm --delete  # Migrate and delete from Cloudinary
 */
class MigrateCloudinaryToBunny extends Command
{
    protected $signature = 'bunny:migrate-from-cloudinary {--confirm} {--lesson-id=} {--delete}';

    protected $description = 'Migrate videos from Cloudinary to Bunny Stream';

    public function handle(BunnyStreamService $bunnyService, VideoSigningService $videoService): int
    {
        $confirm = $this->option('confirm');
        $deleteFromCloudinary = $this->option('delete');
        $lessonId = $this->option('lesson-id');

        if (!$confirm) {
            $this->warn('DRY RUN MODE: No changes will be made. Use --confirm to actually migrate.');
            $this->newLine();
        }

        // Find all Cloudinary lessons (video_type = 'local')
        $query = Lesson::where('video_type', 'local')
            ->whereNotNull('video_url');

        if ($lessonId) {
            $query->where('id', $lessonId);
        }

        $lessons = $query->get();

        if ($lessons->isEmpty()) {
            $this->info('No Cloudinary videos found to migrate.');
            return self::SUCCESS;
        }

        $this->info("Found {$lessons->count()} video(s) to migrate");
        if (!$confirm) {
            $this->newLine();
        }

        $migrated = 0;
        $failed = 0;

        foreach ($lessons as $lesson) {
            $this->line("\nLesson #{$lesson->id}: {$lesson->title}");
            $this->line("  Cloudinary URL: {$lesson->video_url}");

            try {
                // Get the optimized/original Cloudinary URL (not HLS)
                $cloudinaryUrl = $videoService->getOptimizedUrl($lesson->video_url);
                $this->line("  Original URL: {$cloudinaryUrl}");

                if (!$confirm) {
                    $this->line('  [DRY RUN] Would upload to Bunny...');
                    $migrated++;
                    continue;
                }

                // Create video object on Bunny
                $this->line('  Creating video object on Bunny...');
                $videoData = $bunnyService->createVideo($lesson->title);
                $videoId = $videoData['guid'] ?? $videoData['videoId'] ?? $videoData['id'] ?? null;

                if (!$videoId) {
                    throw new \Exception('No video ID returned from Bunny');
                }

                $this->line("  Video created: {$videoId}");

                // Upload video from Cloudinary URL to Bunny
                $this->line('  Uploading video to Bunny (from Cloudinary URL)...');
                try {
                    // Use Bunny's remote fetch API to import from Cloudinary
                    $fetchResult = $bunnyService->fetchVideoFromUrl($cloudinaryUrl, $lesson->title);
                    $this->line('  Upload initiated on Bunny (processing in background)');
                } catch (\Exception $uploadEx) {
                    // If fetch fails, throw error
                    throw new \Exception('Upload to Bunny failed: ' . $uploadEx->getMessage());
                }

                // Update lesson to use Bunny
                $lesson->update([
                    'video_type' => 'bunny',
                    'video_url' => $videoId,
                    'video_status' => 'processing',
                    'video_processed_at' => null,
                ]);

                $this->info("  ✓ Updated lesson to use Bunny");

                // Optionally delete from Cloudinary
                if ($deleteFromCloudinary) {
                    try {
                        $videoService->delete($lesson->video_url);
                        $this->info('  ✓ Deleted from Cloudinary');
                    } catch (\Exception $deleteEx) {
                        $this->warn('  ! Could not delete from Cloudinary: ' . $deleteEx->getMessage());
                    }
                }

                $migrated++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
                $failed++;
            }
        }

        // Summary
        $this->newLine();
        $this->info('Migration completed!');
        $this->line("  Migrated: {$migrated}");
        $this->line("  Failed: {$failed}");

        if (!$confirm) {
            $this->warn('\n\nRun with --confirm flag to actually perform the migration.');
        }

        return self::SUCCESS;
    }
}
