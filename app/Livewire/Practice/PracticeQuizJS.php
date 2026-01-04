<?php

namespace App\Livewire\Practice;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PracticeQuizJS extends Component
{
    public function render()
    {
        return view('livewire.practice.practice-quiz-js');
    }
}
