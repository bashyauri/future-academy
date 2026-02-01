<?php

use Laravel\Fortify\Features;
use App\Livewire\Home\HomePage;
use App\Livewire\Dashboard\Index;
use App\Livewire\Quizzes\QuizList;
use App\Livewire\Quizzes\TakeQuiz;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Dashboard\Analytics;
use App\Livewire\Settings\Appearance;
use App\Livewire\Subscription\Manage; // ← Added
use App\Livewire\Onboarding\StudentOnboarding;
use App\Http\Controllers\PaymentController; // ← Added
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// Public home page
Route::get('/', HomePage::class)->name('home');

// Redirect after login
Route::get('/redirect-dashboard', function () {
    $user = auth()->user();
    if (!$user) {
        return redirect()->route('login');
    }

    if (!$user->hasVerifiedEmail()) {
        return redirect()->route('verification.notice');
    }

    if ($user->isStudent() && !$user->has_completed_onboarding) {
        return redirect()->route('onboarding');
    }

    return match ($user->account_type) {
        'super-admin', 'admin' => redirect('/admin'),
        'uploader'             => redirect('/staff'),
        'teacher'              => redirect()->route('dashboard'),
        'guardian'             => redirect()->route('dashboard'),
        'student'              => redirect()->route('dashboard'),
        default                => redirect()->route('dashboard'),
    };
})->middleware('auth')->name('redirect.dashboard');

// Onboarding
Route::get('/onboarding', StudentOnboarding::class)
    ->middleware(['auth', 'verified'])
    ->name('onboarding');

// Dashboard
Route::get('dashboard', Index::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Subscription management (Livewire)
Route::get('/subscription/manage', Manage::class)
    ->middleware(['auth', 'verified']) // or 'ensure.subscription.or.trial' if needed
    ->name('subscription.manage');

// Cancel subscription
Route::post('/subscription/cancel', [PaymentController::class, 'cancelSubscription'])
    ->middleware('auth')
    ->name('subscription.cancel');

// Protected routes
Route::middleware(['auth', 'ensure.subscription.or.trial'])->group(function () {
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

    // Quiz routes
    Route::get('quizzes', \App\Livewire\Quizzes\SubjectsList::class)->name('quizzes.index');
    Route::get('quizzes/all', QuizList::class)->name('quizzes.all');
    Route::get('quizzes/subject/{subject}', \App\Livewire\Quizzes\QuizzesBySubject::class)->name('quizzes.subject');
    Route::get('quiz/{id}', TakeQuiz::class)->name('quiz.take');

    // Mock exam
    Route::get('mock', \App\Livewire\Quizzes\MockSetup::class)->name('mock.setup');
    Route::get('mock/quiz', \App\Livewire\Quizzes\MockQuiz::class)->name('mock.quiz');
    Route::get('mock/groups', \App\Livewire\Quizzes\MockGroupSelection::class)->name('mock.group-selection');

    // Practice routes
    Route::get('practice', \App\Livewire\Practice\PracticeHome::class)->name('practice.home');
    Route::get('practice/quiz', \App\Livewire\Practice\PracticeQuiz::class)->name('practice.quiz');
    Route::get('practice/quiz-js', \App\Livewire\Practice\PracticeQuizJS::class)->name('practice.quiz.js');

    Route::post('quiz/autosave', [\App\Http\Controllers\Practice\PracticeQuizController::class, 'autosave']);

    Route::prefix('api/practice')->group(function () {
        Route::post('start', [\App\Http\Controllers\Practice\PracticeQuizApiController::class, 'startQuiz']);
        Route::get('load/{attempt}', [\App\Http\Controllers\Practice\PracticeQuizApiController::class, 'loadAttempt']);
        Route::post('load-batch', [\App\Http\Controllers\Practice\PracticeQuizApiController::class, 'loadBatch']);
        Route::post('save', [\App\Http\Controllers\Practice\PracticeQuizApiController::class, 'saveAnswers']);
        Route::post('submit', [\App\Http\Controllers\Practice\PracticeQuizApiController::class, 'submitQuiz']);
        Route::post('exit', [\App\Http\Controllers\Practice\PracticeQuizApiController::class, 'exitQuiz']);
    });

    Route::get('practice/jamb/setup', \App\Livewire\Practice\JambSetup::class)->name('practice.jamb.setup');
    Route::get('practice/jamb/quiz', \App\Livewire\Practice\JambQuiz::class)->name('practice.jamb.quiz');

    // Lessons
    Route::get('lessons', \App\Livewire\Lessons\SubjectsList::class)->name('lessons.subjects');
    Route::get('lessons/{subject}', \App\Livewire\Lessons\LessonsList::class)->name('lessons.list');
    Route::get('lesson/{id}', \App\Livewire\Lessons\LessonView::class)->name('lessons.view');

    // Analytics
    Route::get('analytics', Analytics::class)->name('analytics');
});

// Artisan clear route (fixed)
Route::get('/clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return 'Cleared!';
});

// Sync subscription codes (for shared hosting without CLI access)
Route::get('/sync-subscriptions', function () {
    // Require authentication and admin role
    if (!auth()->check() || !auth()->user()->hasRole(['super-admin', 'admin'])) {
        abort(403, 'Unauthorized');
    }

    Artisan::call('subscriptions:sync-codes');
    $output = Artisan::output();

    return response("<pre>{$output}</pre>")
        ->header('Content-Type', 'text/html');
})->name('sync.subscriptions');

// View webhook logs (for shared hosting debugging)
Route::get('/webhook-logs', function () {
    // Require authentication and admin role
    if (!auth()->check() || !auth()->user()->hasRole(['super-admin', 'admin'])) {
        abort(403, 'Unauthorized');
    }

    $date = request('date', date('Y-m-d'));
    $lines = (int) request('lines', 100);
    $logFile = storage_path("logs/webhook-{$date}.log");

    if (!file_exists($logFile)) {
        return view('webhook-logs', [
            'error' => "No webhook logs found for {$date}",
            'date' => $date,
            'availableDates' => collect(glob(storage_path('logs/webhook-*.log')))
                ->map(fn($f) => basename($f, '.log'))
                ->map(fn($f) => str_replace('webhook-', '', $f))
                ->sort()
                ->reverse()
                ->values()
                ->toArray(),
        ]);
    }

    $allLines = file($logFile);
    $totalLines = count($allLines);
    $displayLines = array_slice($allLines, -$lines);
    $content = implode('', $displayLines);

    // Parse statistics
    $stats = [
        'total' => substr_count($content, 'WEBHOOK RECEIVED'),
        'successful' => substr_count($content, 'Event processed successfully'),
        'errors' => substr_count($content, '❌'),
        'signature_failures' => substr_count($content, 'Invalid Paystack webhook signature'),
    ];

    // Extract recent subscription codes
    preg_match_all('/SUB_[a-z0-9]+/i', $content, $matches);
    $recentSubs = array_unique($matches[0]);

    return view('webhook-logs', [
        'content' => $content,
        'date' => $date,
        'lines' => $lines,
        'totalLines' => $totalLines,
        'stats' => $stats,
        'recentSubs' => $recentSubs,
        'availableDates' => collect(glob(storage_path('logs/webhook-*.log')))
            ->map(fn($f) => basename($f, '.log'))
            ->map(fn($f) => str_replace('webhook-', '', $f))
            ->sort()
            ->reverse()
            ->values()
            ->toArray(),
    ]);
})->name('webhook.logs');

// Webhooks
Route::post('/webhooks/paystack', [App\Http\Controllers\PaystackWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.paystack');

Route::post('/webhooks/cloudinary', [App\Http\Controllers\CloudinaryWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.cloudinary');

// Payment routes
use App\Livewire\Payment\Pricing as PaymentPricing;

Route::get('payment/pricing', PaymentPricing::class)->name('payment.pricing');
Route::post('payment/initialize', [\App\Http\Controllers\PaymentController::class, 'initialize'])->name('payment.initialize');
Route::get('payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('payment.callback');
