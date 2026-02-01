<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class CleanupInvalidSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:cleanup {--force : Skip confirmation}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Delete subscriptions with invalid FA-xxx codes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🗑️  Finding invalid subscriptions...');
        $this->newLine();

        // Find all subscriptions with FA-xxx codes
        $invalidSubscriptions = Subscription::where('subscription_code', 'LIKE', 'FA-%')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($invalidSubscriptions->isEmpty()) {
            $this->info('✅ No invalid subscriptions found. Database is clean!');
            return 0;
        }

        $this->error("Found {$invalidSubscriptions->count()} subscription(s) with invalid FA-xxx codes:");
        $this->newLine();

        // Display subscriptions to be deleted
        $this->table(
            ['ID', 'User Email', 'FA Code', 'Created At', 'Status'],
            $invalidSubscriptions->map(fn($sub) => [
                $sub->id,
                $sub->user?->email ?? 'N/A',
                $sub->subscription_code,
                $sub->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
                $sub->status,
            ])->toArray()
        );

        $this->newLine();
        $this->warn('⚠️  WARNING: This action will permanently delete these subscriptions!');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to delete these {$invalidSubscriptions->count()} subscription(s)?')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }

            if (!$this->confirm('Are you absolutely sure? This cannot be undone!')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }
        }

        // Delete subscriptions
        $deleted = 0;
        foreach ($invalidSubscriptions as $subscription) {
            try {
                $code = $subscription->subscription_code;
                $subscription->delete();
                $this->line("  ✅ Deleted: {$code}");
                $deleted++;
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to delete {$subscription->subscription_code}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("🎉 Successfully deleted {$deleted} subscription(s)!");

        return 0;
    }
}
