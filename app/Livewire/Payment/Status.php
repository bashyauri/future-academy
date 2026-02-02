<?php

namespace App\Livewire\Payment;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;


class Status extends Component
{
    public $onTrial = false;
    public $isSubscribed = false;
    public $isGuardian = false;
    public $trialDaysLeft = 0;
    public $subscriptionEndsAt = null;
    public $subscription = null;

    public function mount()
    {
        $user = Auth::user();
        if ($user) {
            $this->isGuardian = $user->isParent();
            $this->onTrial = $user->onTrial();
            $this->isSubscribed = $user->hasActiveSubscription();
            $this->subscription = $user->subscriptions()->active()->first();

            if ($this->onTrial && $user->trial_ends_at) {
                $daysLeft = now()->diffInDays($user->trial_ends_at, false);
                if ($daysLeft >= 1) {
                    $this->trialDaysLeft = $daysLeft;
                } else {
                    $hoursLeft = now()->diffInHours($user->trial_ends_at, false);
                    $this->trialDaysLeft = round($hoursLeft / 24, 2); // fraction of a day
                }
            }

            if ($this->isSubscribed && $this->subscription) {
                $endsAt = $this->subscription->ends_at;
                if ($endsAt) {
                    $endsAt = is_object($endsAt)
                        ? $endsAt
                        : \Illuminate\Support\Carbon::parse($endsAt);
                    $this->subscriptionEndsAt = $endsAt->format('M d, Y');
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.payment.status');
    }
}
