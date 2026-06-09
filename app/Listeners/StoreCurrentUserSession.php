<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Schema;

class StoreCurrentUserSession
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if (! Schema::hasColumn('users', 'current_session_id')) {
            return;
        }

        $event->user->forceFill([
            'current_session_id' => session()->getId(),
        ])->save();
    }
}
