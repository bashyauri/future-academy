<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    // Single panel architecture: redirect authenticated panel users to /admin
    if (auth()->check()) {
        $user = auth()->user();
        // Any content or admin permission sends user to the unified admin panel
        $panelPermissions = [
            'manage users',
            'manage academics',
            'approve questions',
            'delete questions',
            'create questions',
            'manage questions',
            'upload questions',
            'import questions',
        ];
        if (collect($panelPermissions)->contains(fn($p) => $user->hasPermissionTo($p))) {
            return redirect('/admin');
        }
    }
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
