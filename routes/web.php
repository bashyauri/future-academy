<?php

use App\Livewire\Dashboard\Analytics;
use App\Livewire\Dashboard\Index;
use App\Livewire\Quizzes\QuizList;
use App\Livewire\Quizzes\TakeQuiz;
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

Route::get('dashboard', Index::class)
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

    // Quiz routes
    Route::get('quizzes', \App\Livewire\Quizzes\SubjectsList::class)->name('quizzes.index');
    Route::get('quizzes/all', QuizList::class)->name('quizzes.all'); // Keep old list as "browse all"
    Route::get('quizzes/subject/{subject}', \App\Livewire\Quizzes\QuizzesBySubject::class)->name('quizzes.subject');
    Route::get('quiz/{id}', TakeQuiz::class)->name('quiz.take');

    // Lesson routes
    Route::get('lessons', \App\Livewire\Lessons\SubjectsList::class)->name('lessons.subjects');
    Route::get('lessons/{subject}', \App\Livewire\Lessons\LessonsList::class)->name('lessons.list');
    Route::get('lesson/{id}', \App\Livewire\Lessons\LessonView::class)->name('lessons.view');

    // Analytics route
    Route::get('analytics', Analytics::class)->name('analytics');
});
