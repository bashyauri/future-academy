<?php

namespace App\Livewire\Subscription;

use Livewire\Component;

class Cancel extends Component
{
    public $successMessage;

    public $errorMessage;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function mount()
    {
        $this->successMessage = session('success');
        $this->errorMessage = session('errors') ? session('errors')->first('subscription') : null;
    }

    public function render()
    {
        return view('livewire.subscription.cancel');
    }
}
