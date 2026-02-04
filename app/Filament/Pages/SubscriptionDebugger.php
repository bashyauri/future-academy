<?php

namespace App\Filament\Pages;

use App\Models\Subscription;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\BufferedOutput;
use UnitEnum;

class SubscriptionDebugger extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $title = 'Subscription Debugger';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.subscription-debugger';

    public function getTitle(): string
    {
        return 'Subscription Management & Debugging';
    }

    public function getHeading(): string
    {
        return 'Subscription Management';
    }

    public function getSubheading(): ?string
    {
        return 'View, sync, and manage all subscription data with advanced debugging tools.';
    }

    public ?string $filterType = 'all';
    public ?string $selectedCode = null;
    public ?string $debugOutput = null;
    public array $subscriptions = [];
    public array $stats = [];

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->hasRole('super-admin');
    }

    public function mount(): void
    {
        $this->loadSubscriptions();
    }

    public function loadSubscriptions(): void
    {
        $query = Subscription::with('user')->orderByDesc('created_at');

        // Apply filters
        if ($this->filterType === 'active') {
            $query->where('status', 'active')->where('is_active', true);
        } elseif ($this->filterType === 'inactive') {
            $query->where(function ($q) {
                $q->where('status', '!=', 'active')
                  ->orWhere('is_active', false);
            });
        }

        $this->subscriptions = $query->limit(50)->get()->toArray();

        // Calculate stats
        $all = Subscription::all();
        $this->stats = [
            'total' => $all->count(),
            'active' => $all->where('status', 'active')->where('is_active', true)->count(),
            'inactive' => $all->where('status', '!=', 'active')->count(),
            'with_student_id' => $all->whereNotNull('student_id')->count(),
            'without_student_id' => $all->whereNull('student_id')->count(),
        ];
    }

    public function updatedFilterType(): void
    {
        $this->loadSubscriptions();
    }

    public function syncSubscriptions(): void
    {
        if (!static::canAccess()) {
            abort(403);
        }

        try {
            $buffer = new BufferedOutput();
            Artisan::call('subscriptions:sync-codes', ['--force' => true], $buffer);
            $output = $buffer->fetch();

            $this->debugOutput = $output ?: 'Subscriptions synced successfully.';

            Notification::make()
                ->title('Sync Complete')
                ->body('Subscription codes have been synchronized.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->debugOutput = 'Error: ' . $e->getMessage();

            Notification::make()
                ->title('Sync Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->loadSubscriptions();
    }

    public function checkSubscription(string $code): void
    {
        $subscription = Subscription::where('subscription_code', $code)->first();

        if (!$subscription) {
            $this->debugOutput = "❌ Subscription not found with code: {$code}";
            return;
        }

        $studentName = $subscription->student?->name ?? ($subscription->student_id ? 'Unknown (ID: ' . $subscription->student_id . ')' : 'None');

        $this->debugOutput = json_encode([
            'id' => $subscription->id,
            'user' => $subscription->user?->email,
            'student' => $studentName,
            'code' => $subscription->subscription_code,
            'plan_code' => $subscription->plan_code,
            'status' => $subscription->status,
            'is_active' => $subscription->is_active,
            'type' => $subscription->type,
            'ends_at' => $subscription->ends_at?->toDateTimeString(),
            'cancelled_at' => $subscription->cancelled_at?->toDateTimeString(),
            'created_at' => $subscription->created_at?->toDateTimeString(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->selectedCode = $code;
    }

    public function activateSubscription(int $id): void
    {
        if (!static::canAccess()) {
            abort(403);
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::find($id);

        if (!$subscription) {
            Notification::make()
                ->title('Not Found')
                ->body('Subscription not found.')
                ->danger()
                ->send();
            return;
        }

        /** @phpstan-ignore-next-line */
        $subscription->update([
            'status' => 'active',
            'is_active' => true,
            'cancelled_at' => null,
        ]);

        Log::info('Subscription manually activated', ['id' => $id, 'code' => $subscription->subscription_code]);

        Notification::make()
            ->title('Activated')
            ->body('Subscription marked as active.')
            ->success()
            ->send();

        $this->loadSubscriptions();
    }

    public function deleteSubscription(int $id): void
    {
        if (!static::canAccess()) {
            abort(403);
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::find($id);

        if (!$subscription) {
            Notification::make()
                ->title('Not Found')
                ->body('Subscription not found.')
                ->danger()
                ->send();
            return;
        }

        $code = $subscription->subscription_code;
        /** @phpstan-ignore-next-line */
        $subscription->delete();

        Log::info('Subscription deleted', ['id' => $id, 'code' => $code]);

        Notification::make()
            ->title('Deleted')
            ->body('Subscription has been deleted.')
            ->success()
            ->send();

        $this->loadSubscriptions();
    }

    public function cancelSubscription(int $id): void
    {
        if (!static::canAccess()) {
            abort(403);
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::find($id);

        if (!$subscription) {
            Notification::make()
                ->title('Not Found')
                ->body('Subscription not found.')
                ->danger()
                ->send();
            return;
        }

        /** @phpstan-ignore-next-line */
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Log::info('Subscription cancelled', ['id' => $id, 'code' => $subscription->subscription_code]);

        Notification::make()
            ->title('Cancelled')
            ->body('Subscription has been cancelled.')
            ->warning()
            ->send();

        $this->loadSubscriptions();
    }
}
