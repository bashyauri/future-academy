<?php
namespace App\Livewire\Subscription;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Subscription;

class Manage extends Component
{
    public $subscriptions = [];
    public $activeSubscription = null;
    public $onTrial = false;
    public $trialDaysLeft = 0;
    public $successMessage;
    public $errorMessage;
    public $isCancelling = false;
    public $availablePlans = [];
    public $planType = 'recurring';

    public function mount()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $this->refreshData();
        $this->successMessage = session('success');
        $this->errorMessage = session('errors')?->first('subscription');
        $this->planType = 'recurring';
        $this->availablePlans = $this->getAvailablePlans($this->planType);
    }

    public function getAvailablePlans($type = 'recurring')
    {
        if ($type === 'recurring') {
            return [
                [
                    'code'   => config('services.paystack.plans.monthly'),
                    'name'   => 'Monthly (Recurring)',
                    'amount' => 2000,
                    'type'   => 'recurring',
                ],
                [
                    'code'   => config('services.paystack.plans.yearly'),
                    'name'   => 'Yearly (Recurring)',
                    'amount' => 12000,
                    'type'   => 'recurring',
                ],
            ];
        }

        return [
            [
                'code'   => null,
                'name'   => 'Monthly (One-Time)',
                'amount' => 2500,
                'type'   => 'onetime',
            ],
            [
                'code'   => null,
                'name'   => 'Yearly (One-Time)',
                'amount' => 13000,
                'type'   => 'onetime',
            ],
        ];
    }

    public function setPlanType($type)
    {
        $this->planType = $type;
        $this->availablePlans = $this->getAvailablePlans($type);
    }

    // Removed Livewire redirect logic. Plan selection is now handled via POST form in Blade view.
    public function refreshData()
    {
        $user = Auth::user();

        $this->subscriptions = $user->subscriptions()->orderByDesc('created_at')->get();
        $this->activeSubscription = $user->subscriptions()
            ->where('is_active', true)
            ->where('ends_at', '>', now())
            ->latest()
            ->first();

        $this->onTrial = $user->trial_ends_at && $user->trial_ends_at->isFuture();
        $this->trialDaysLeft = $this->onTrial ? max(0, $user->trial_ends_at->diffInDays(now())) : 0;
    }

    public function cancelSubscription()
    {
        $this->isCancelling = true;

        $user = Auth::user();
        $subscription = $user->subscriptions()
            ->where('is_active', true)
            ->whereNotNull('subscription_code')
            ->latest()
            ->first();

        if (!$subscription) {
            $this->errorMessage = __('No active subscription found.');
            $this->isCancelling = false;
            return;
        }

        try {
            $result = app(\App\Services\PaymentService::class)
                ->cancelSubscription($subscription->subscription_code);

            if ($result['success']) {
                $subscription->update([
                    'status'     => 'cancelled',
                    'is_active'  => false,
                    'cancelled_at' => now(),
                ]);

                $this->successMessage = $result['message'] ?? __('Subscription cancelled successfully. It will remain active until the end of the current period.');
                $this->errorMessage = null;
            } else {
                $this->errorMessage = $result['message'] ?? __('Failed to cancel subscription.');
            }
        } catch (\Throwable $e) {
            $this->errorMessage = __('Error: ') . $e->getMessage();
            \Log::error('Subscription cancel failed', ['error' => $e->getMessage()]);
        }

        $this->isCancelling = false;
        $this->refreshData();
    }

    public function render()
    {
        return view('livewire.subscription.manage', [
            'subscriptions'      => $this->subscriptions,
            'activeSubscription' => $this->activeSubscription,
            'onTrial'            => $this->onTrial,
            'trialDaysLeft'      => $this->trialDaysLeft,
            'availablePlans'     => $this->availablePlans,
            'planType'           => $this->planType,
            'successMessage'     => $this->successMessage,
            'errorMessage'       => $this->errorMessage,
            'isCancelling'       => $this->isCancelling,
        ]);
    }
}
