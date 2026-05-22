<?php

namespace App\Livewire\Payment;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Pricing extends Component
{
    public $plan = 'monthly';

    public $type = 'one_time';

    public $error;

    public $student_id = null;

    public bool $isGuardian = false;

    public array $linkedStudents = [];

    public function mount(): void
    {
        $user = Auth::user();

        $this->student_id = request()->query('student_id');

        if (! $user) {
            return;
        }

        $this->isGuardian = $user->isParent();

        if (! $this->isGuardian) {
            return;
        }

        $this->linkedStudents = $user->children()
            ->get(['users.id', 'users.name'])
            ->map(fn ($student): array => [
                'id' => $student->id,
                'name' => $student->name,
            ])
            ->all();

        $linkedStudentIds = array_map(
            static fn (array $student): int => (int) $student['id'],
            $this->linkedStudents,
        );

        if (! in_array((int) $this->student_id, $linkedStudentIds, true)) {
            $this->student_id = null;
        }
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
