<?php

use App\Livewire\Dashboard\Analytics;
use App\Livewire\Dashboard\Index;
use App\Livewire\Home\HomePage;
use App\Livewire\Onboarding\StudentOnboarding;
use App\Livewire\Quizzes\QuizList;
use App\Livewire\Quizzes\TakeQuiz;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Public home page with role selection
Route::get('/', HomePage::class)->name('home');

// Redirect based on account type after login
Route::get('/redirect-dashboard', function () {
    $user = auth()->user();

    if (!$user) {
        return redirect()->route('login');
    }

    // Check if student needs onboarding
    if ($user->isStudent() && !$user->has_completed_onboarding) {
        return redirect()->route('onboarding');
    }

    // Redirect based on role
    return match($user->account_type) {
        'super-admin', 'admin' => redirect('/admin'),
        'teacher', 'uploader' => redirect('/teacher'),
        'guardian' => redirect('/parent'),
        'student' => redirect()->route('dashboard'),
        default => redirect()->route('dashboard'),
    };
})->middleware('auth')->name('redirect.dashboard');

// Student onboarding
Route::get('/onboarding', StudentOnboarding::class)
    ->middleware(['auth', 'verified'])
    ->name('onboarding');

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

    // Mock exam flow
    Route::get('mock', \App\Livewire\Quizzes\MockSetup::class)->name('mock.setup');
    Route::get('mock/quiz', \App\Livewire\Quizzes\MockQuiz::class)->name('mock.quiz');

    // Practice routes (by exam type, subject, and year)
    Route::get('practice', \App\Livewire\Practice\PracticeHome::class)->name('practice.home');
    Route::get('practice/quiz', \App\Livewire\Practice\PracticeQuiz::class)->name('practice.quiz');

    // JAMB Practice routes
    Route::get('practice/jamb/setup', \App\Livewire\Practice\JambSetup::class)->name('practice.jamb.setup');
    Route::get('practice/jamb/quiz', \App\Livewire\Practice\JambQuiz::class)->name('practice.jamb.quiz');

    // Lesson routes
    Route::get('lessons', \App\Livewire\Lessons\SubjectsList::class)->name('lessons.subjects');
    Route::get('lessons/{subject}', \App\Livewire\Lessons\LessonsList::class)->name('lessons.list');
    Route::get('lesson/{id}', \App\Livewire\Lessons\LessonView::class)->name('lessons.view');

    // Analytics route
    Route::get('analytics', Analytics::class)->name('analytics');
});
