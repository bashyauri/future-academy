<?php
namespace App\Livewire\Subscription;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Subscription;

class Manage extends Component
{
    public $subscriptions = [];
    public $activeSubscription = null;
    public $inactiveSubscription = null;
    public $onTrial = false;
    public $trialDaysLeft = 0;
    public $successMessage;
    public $errorMessage;
    public $isCancelling = false;
    public $isEnabling = false;
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
                'type'   => 'one_time',
            ],
            [
                'code'   => null,
                'name'   => 'Yearly (One-Time)',
                'amount' => 13000,
                'type'   => 'one_time',
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
        $this->activeSubscription = $user->subscriptions()->active()->first();
        $this->inactiveSubscription = $user->subscriptions()
            ->where('is_active', false)
            ->whereNull('cancelled_at')
            ->latest()
            ->first();

        $this->onTrial = $user->trial_ends_at && $user->trial_ends_at->isFuture();
        $this->trialDaysLeft = $this->onTrial ? max(0, $user->trial_ends_at->diffInDays(now())) : 0;
    }

    public function cancelSubscription()
    {
        $this->isCancelling = true;

        $user = Auth::user();
        $subscription = $user->subscriptions()->active()->first();

        if (!$subscription || !$subscription->canBeCancelled()) {
            $this->errorMessage = __('No active subscription found or subscription cannot be cancelled.');
            $this->isCancelling = false;
            return;
        }

        if (empty($subscription->subscription_code) || !Str::startsWith($subscription->subscription_code, 'SUB_')) {
            $lookup = app(\App\Services\PaymentService::class)
                ->fetchActiveSubscriptionByEmail($user->email);

            if (!($lookup['success'] ?? false) || empty($lookup['data']['subscription_code'])) {
                $this->errorMessage = __('Unable to sync subscription code from Paystack. Please contact support.');
                $this->isCancelling = false;
                return;
            }

            $subscription->update([
                'subscription_code' => $lookup['data']['subscription_code'],
            ]);
        }

        try {
            $result = app(\App\Services\PaymentService::class)
                ->cancelSubscription($subscription->subscription_code, $subscription->authorization_code);

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

    public function enableSubscription()
    {
        $this->isEnabling = true;

        $user = Auth::user();
        $subscription = $user->subscriptions()
            ->where('is_active', false)
            ->whereNull('cancelled_at')
            ->whereNotNull('subscription_code')
            ->whereNotNull('authorization_code')
            ->latest()
            ->first();

        if (!$subscription || !$subscription->canRenew()) {
            $this->errorMessage = __('No inactive subscription found to enable or it cannot be renewed.');
            $this->isEnabling = false;
            return;
        }

        try {
            $result = app(\App\Services\PaymentService::class)
                ->enableSubscription($subscription->subscription_code, $subscription->authorization_code);

            if ($result['success']) {
                $subscription->update([
                    'status'     => 'active',
                    'is_active'  => true,
                    'cancelled_at' => null,
                ]);

                $this->successMessage = $result['message'] ?? __('Subscription enabled successfully!');
                $this->errorMessage = null;
            } else {
                $this->errorMessage = $result['message'] ?? __('Failed to enable subscription.');
            }
        } catch (\Throwable $e) {
            $this->errorMessage = __('Error: ') . $e->getMessage();
            \Log::error('Subscription enable failed', ['error' => $e->getMessage()]);
        }

        $this->isEnabling = false;
        $this->refreshData();
    }

    public function render()
    {
        return view('livewire.subscription.manage', [
            'subscriptions'      => $this->subscriptions,
            'activeSubscription' => $this->activeSubscription,
            'inactiveSubscription' => $this->inactiveSubscription,
            'onTrial'            => $this->onTrial,
            'trialDaysLeft'      => $this->trialDaysLeft,
            'availablePlans'     => $this->availablePlans,
            'planType'           => $this->planType,
            'successMessage'     => $this->successMessage,
            'errorMessage'       => $this->errorMessage,
            'isCancelling'       => $this->isCancelling,
            'isEnabling'         => $this->isEnabling,
        ]);
    }
}
