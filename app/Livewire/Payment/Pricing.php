<?php

namespace App\Livewire\Payment;

use Livewire\Component;

class Pricing extends Component
{
    public $plan = 'monthly';
    public $type = 'one_time';
    public $error;
    public $student_id = null;

    public function mount()
    {
        // Get student_id from query string if present
        $this->student_id = request()->query('student_id');
    }

    public function pay()
    {
        $this->resetErrorBag();
        $this->validate([
            'plan' => 'required|in:monthly,yearly',
            'type' => 'required|in:one_time,recurring',
        ]);

        // Will be submitted via wire:submit form
    }

    public function render()
    {
        return view('livewire.payment.pricing');
    }
}
