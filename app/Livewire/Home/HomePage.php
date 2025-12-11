<?php

namespace App\Livewire\Home;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class HomePage extends Component
{
    public function render()
    {
        return view('livewire.home.home-page');
    }
}
