<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Subscription;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SyncSubscriptionCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:sync-codes {--force : Skip confirmation}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Sync FA-xxx subscription codes to real SUB_xxx codes from Paystack API';

    /**
     * The payment service instance.
     */
    private PaymentService $paymentService;

    /**
     * Create a new command instance.
     */
    public function __construct(PaymentService $paymentService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting subscription code sync...');
        $this->newLine();

        // Find all subscriptions with FA-xxx codes
        $invalidSubscriptions = Subscription::where('subscription_code', 'LIKE', 'FA-%')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($invalidSubscriptions->isEmpty()) {
            $this->info('✅ No subscriptions with FA-xxx codes found. All codes are valid!');
            return 0;
        }

        $this->warn("Found {$invalidSubscriptions->count()} subscription(s) with FA-xxx codes:");
        $this->newLine();

        // Display found subscriptions
        $this->table(
            ['ID', 'User Email', 'FA Code', 'Reference', 'Created At'],
            $invalidSubscriptions->map(fn($sub) => [
                $sub->id,
                $sub->user?->email ?? 'N/A',
                $sub->subscription_code,
                $sub->reference,
                $sub->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
            ])->toArray()
        );

        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to sync these subscription codes from Paystack?')) {
                $this->info('Sync cancelled.');
                return 0;
            }
        }

        $this->newLine();
        $synced = 0;
        $failed = 0;
        $notFound = 0;

        // Progress bar
        $bar = $this->output->createProgressBar($invalidSubscriptions->count());
        $bar->start();

        foreach ($invalidSubscriptions as $subscription) {
            $bar->advance();

            // Get user
            $user = $subscription->user;
            if (!$user) {
                $this->newLine();
                $this->error("❌ User not found for subscription {$subscription->id}");
                $failed++;
                continue;
            }

            try {
                // Fetch subscription from Paystack by customer email
                $result = $this->paymentService->fetchActiveSubscriptionByEmail($user->email);

                if (!$result['success'] || !$result['data']) {
                    $this->newLine();
                    $this->warn("⚠️  No active subscription found on Paystack for {$user->email}");
                    $notFound++;
                    continue;
                }

                // Get the real subscription code from the subscription data
                $paystackSub = $result['data'];
                $realSubCode = $paystackSub['subscription_code'] ?? null;

                if (!$realSubCode) {
                    $this->newLine();
                    $this->warn("⚠️  Paystack subscription has no code for {$user->email}");
                    $failed++;
                    continue;
                }

                // Update subscription
                $subscription->update([
                    'subscription_code' => $realSubCode,
                ]);

                $this->newLine();
                $this->info("✅ Updated: {$subscription->subscription_code} → {$realSubCode}");
                $synced++;

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Error syncing {$user->email}: {$e->getMessage()}");
                $failed++;
            }
        }

        $bar->finish();
        $this->newLine();
        $this->newLine();

        // Summary
        $this->info('📊 Sync Summary:');
        $this->info("  ✅ Synced: {$synced}");
        $this->warn("  ⚠️  Not found on Paystack: {$notFound}");
        $this->error("  ❌ Failed: {$failed}");
        $this->newLine();

        if ($synced > 0) {
            $this->info("🎉 Successfully synced {$synced} subscription code(s)!");
        }

        if ($notFound > 0) {
            $this->warn("\n💡 Tip: Check Paystack dashboard to verify subscriptions exist for these users.");
        }

        return $synced > 0 ? 0 : 1;
    }
}
