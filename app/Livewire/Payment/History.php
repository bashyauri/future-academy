<?php

namespace App\Livewire\Payment;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class History extends Component
{
    public $subscriptions = [];

    public function mount()
    {
        $user = Auth::user();
        $this->subscriptions = $user ? $user->subscriptions()->latest('created_at')->get() : collect();
    }

    public function render()
    {
        return view('livewire.payment.history');
    }
}
