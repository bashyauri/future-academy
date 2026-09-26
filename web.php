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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Public home page with role selection
Route::get('/', HomePage::class)->name('home');

// Redirect based on account type after login
Route::get('/redirect-dashboard', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    // Check if email is verified
    if (! $user->hasVerifiedEmail()) {
        return redirect()->route('verification.notice');
    }

    // Check if student needs onboarding
    if ($user->isStudent() && ! $user->has_completed_onboarding) {
        return redirect()->route('onboarding');
    }

    // Redirect based on role
    return match ($user->account_type) {
        'super-admin', 'admin' => redirect('/admin'),
        'uploader' => redirect('/staff'),
        'teacher' => redirect()->route('dashboard'), // Teachers use frontend
        'guardian' => redirect()->route('dashboard'), // Parents use frontend
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
// Settings routes - accessible to all authenticated users
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
                []
            )
        )
        ->name('two-factor.show');
});
Route::middleware(['auth', 'ensure.subscription.or.trial'])->group(function () {

    // Quiz routes
    Route::get('quizzes', SubjectsList::class)->name('quizzes.index');
    Route::get('quizzes/all', QuizList::class)->name('quizzes.all'); // Keep old list as "browse all"
    Route::get('quizzes/subject/{subject}', QuizzesBySubject::class)->name('quizzes.subject');
    Route::get('quiz/{id}', TakeQuiz::class)->name('quiz.take');

    // Mock exam flow
    Route::get('mock', MockSetup::class)->name('mock.setup');
    Route::get('mock/quiz', MockQuiz::class)->name('mock.quiz');
    Route::get('mock/groups', MockGroupSelection::class)->name('mock.group-selection');

    // Practice routes (by exam type, subject, and year)
    Route::get('practice', PracticeHome::class)->name('practice.home');
    Route::get('practice/quiz', PracticeQuiz::class)->name('practice.quiz');
    Route::get('practice/quiz-js', PracticeQuizJS::class)->name('practice.quiz.js');

    // Autosave endpoint for Alpine.js quiz interaction
    Route::post('quiz/autosave', [PracticeQuizController::class, 'autosave']);

    // Practice Quiz API endpoints (for JavaScript version)
    Route::prefix('api/practice')->group(function () {
        Route::post('start', [PracticeQuizApiController::class, 'startQuiz']);
        Route::get('load/{attempt}', [PracticeQuizApiController::class, 'loadAttempt']);
        Route::post('load-batch', [PracticeQuizApiController::class, 'loadBatch']);
        Route::post('save', [PracticeQuizApiController::class, 'saveAnswers']);
        Route::post('submit', [PracticeQuizApiController::class, 'submitQuiz']);
        Route::post('exit', [PracticeQuizApiController::class, 'exitQuiz']);
    });

    // JAMB Practice routes
    Route::get('practice/jamb/setup', JambSetup::class)->name('practice.jamb.setup');
    Route::get('practice/jamb/quiz', JambQuiz::class)->name('practice.jamb.quiz');

    // Lesson routes
    Route::get('lessons', App\Livewire\Lessons\SubjectsList::class)->name('lessons.subjects');
    Route::get('lessons/{subject}', LessonsList::class)->name('lessons.list');
    Route::get('lesson/{id}', LessonView::class)->name('lessons.view');

    // Analytics route
    Route::get('analytics', Analytics::class)->name('analytics');
});

// Artisan command execution via web (for shared hosting)
// Secured with token authentication
Route::get('/artisan/{command}', [ArtisanController::class, 'execute'])
    ->middleware('throttle:10,1')
    ->name('artisan.execute');

// Paystack webhook (no auth required - Paystack validates with signature)
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.paystack');

// Cloudinary webhooks (no auth required - Cloudinary validates with signature)
Route::post('/webhooks/cloudinary', [CloudinaryWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.cloudinary');

// Bunny Stream webhooks (no auth required - Bunny validates with signature when configured)
Route::post('/webhooks/bunny', [BunnyWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.bunny');

// Payment routes
use App\Http\Controllers\ArtisanController;
use App\Http\Controllers\BunnyWebhookController;
use App\Http\Controllers\CloudinaryWebhookController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\Practice\PracticeQuizApiController;
use App\Http\Controllers\Practice\PracticeQuizController;
use App\Livewire\Lessons\LessonsList;
use App\Livewire\Lessons\LessonView;
use App\Livewire\Payment\Pricing as PaymentPricing;
use App\Livewire\Practice\JambQuiz;
use App\Livewire\Practice\JambSetup;
use App\Livewire\Practice\PracticeHome;
use App\Livewire\Practice\PracticeQuiz;
use App\Livewire\Practice\PracticeQuizJS;
use App\Livewire\Quizzes\MockGroupSelection;
use App\Livewire\Quizzes\MockQuiz;
use App\Livewire\Quizzes\MockSetup;
use App\Livewire\Quizzes\QuizzesBySubject;
use App\Livewire\Quizzes\SubjectsList;

Route::get('payment/pricing', PaymentPricing::class)->name('payment.pricing');
Route::post('payment/initialize', [PaymentController::class, 'initialize'])->name('payment.initialize');
Route::get('payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');

// Subscription management
Route::post('/subscription/cancel', [PaymentController::class, 'cancelSubscription'])
    ->middleware('auth')
    ->name('subscription.cancel');

Route::get('/clear', function () {
    Artisan::call('optimize:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return 'Cleared';
});
