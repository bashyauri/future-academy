<?php

namespace App\Livewire\Payment;

use Livewire\Component;

class Pricing extends Component
{
    public $plan = 'monthly';
    public $type = 'one_time';
    public $error;

    public function pay()
    {
        $this->resetErrorBag();
        $this->validate([
            'plan' => 'required|in:monthly,yearly',
            'type' => 'required|in:one_time,recurring',
        ]);
        // Redirect to payment initialization route
        return redirect()->route('payment.initialize', [
            'plan' => $this->plan,
            'type' => $this->type,
        ]);
    }

    public function render()
    {
        return view('livewire.payment.pricing');
    }
}
