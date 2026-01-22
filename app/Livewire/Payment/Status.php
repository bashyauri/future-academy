<?php

namespace App\Livewire\Payment;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Status extends Component
{
    public $onTrial = false;
    public $isSubscribed = false;
    public $trialDaysLeft = 0;
    public $subscriptionEndsAt = null;

    public function mount()
    {
        $user = Auth::user();
        if ($user) {
            $this->onTrial = $user->onTrial();
            $this->isSubscribed = $user->hasActiveSubscription();
            if ($this->onTrial && $user->trial_ends_at) {
                $daysLeft = now()->diffInDays($user->trial_ends_at, false);
                if ($daysLeft >= 1) {
                    $this->trialDaysLeft = $daysLeft;
                } else {
                    $hoursLeft = now()->diffInHours($user->trial_ends_at, false);
                    $this->trialDaysLeft = round($hoursLeft / 24, 2); // fraction of a day
                }
            }
            if ($this->isSubscribed && $user->currentSubscription) {
                $endsAt = $user->currentSubscription->ends_at;
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
