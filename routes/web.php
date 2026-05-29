<?php

use App\Http\Controllers\Admin\VideoUploadController;
use App\Http\Controllers\CloudinaryWebhookController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\McpController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\Practice\JambQuizController;
use App\Http\Controllers\Practice\PracticeQuizApiController;
use App\Http\Controllers\Practice\PracticeQuizController;
use App\Http\Controllers\VideoProgressController;
use App\Livewire\Dashboard\Analytics;
use App\Livewire\Dashboard\Index; // ← Added
use App\Livewire\Dashboard\ParentIndex;
use App\Livewire\Home\HomePage;
use App\Livewire\Lessons\LessonsList; // ← Added
use App\Livewire\Lessons\LessonView;
use App\Livewire\Onboarding\StudentOnboarding;
use App\Livewire\Payment\Pricing as PaymentPricing;
use App\Livewire\Practice\JambQuiz;
use App\Livewire\Practice\JambSetup;
use App\Livewire\Practice\PracticeHome;
use App\Livewire\Practice\PracticeQuiz;
use App\Livewire\Practice\PracticeQuizJS;
use App\Livewire\Quizzes\MockGroupSelection;
use App\Livewire\Quizzes\MockQuiz;
use App\Livewire\Quizzes\MockSetup;
use App\Livewire\Quizzes\QuizList;
use App\Livewire\Quizzes\QuizzesBySubject;
use App\Livewire\Quizzes\SubjectsList;
use App\Livewire\Quizzes\TakeQuiz;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Subscription\Manage;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Public home page
Route::get('/', HomePage::class)->name('home');

// Redirect after login
Route::get('/redirect-dashboard', function () {
    $user = auth()->user();
    if (! $user) {
        return redirect()->route('login');
    }

    if (! $user->hasVerifiedEmail()) {
        return redirect()->route('verification.notice');
    }

    $activeContext = $user->resolveActiveRoleContext();

    if (! session()->has('active_role_context') && in_array($activeContext, ['guardian', 'student'], true)) {
        session(['active_role_context' => $activeContext]);
    }

    if ($activeContext === 'student' && ! $user->has_completed_onboarding) {
        return redirect()->route('onboarding');
    }

    return match ($user->account_type) {
        'super-admin', 'admin' => redirect('/admin'),
        'uploader' => redirect('/staff'),
        'teacher' => redirect()->route('dashboard'),
        'guardian' => redirect()->route('dashboard'),
        'student' => redirect()->route('dashboard'),
        default => redirect()->route('dashboard'),
    };
})->middleware('auth')->name('redirect.dashboard');

// Onboarding
Route::get('/onboarding', StudentOnboarding::class)
    ->middleware(['auth', 'verified', 'ensure.student'])
    ->name('onboarding');

// Dashboard - Route to appropriate dashboard based on user role
// Uses smart routing: Parents → ParentIndex, Students/Teachers → Index
Route::get('dashboard', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    if ($user->hasAnyRole(['admin', 'super-admin']) && ! session('impersonator_id')) {
        return redirect('/admin');
    }

    $activeContext = $user->resolveActiveRoleContext();

    if (! session()->has('active_role_context') && in_array($activeContext, ['guardian', 'student'], true)) {
        session(['active_role_context' => $activeContext]);
    }

    if ($activeContext === 'guardian') {
        return redirect()->route('parent.dashboard');
    }

    return redirect()->route('student.dashboard');
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::post('/role-context/switch', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    $context = request()->string('context')->toString();

    $allowedContexts = [
        'guardian' => $user->canUseGuardianContext(),
        'student' => $user->canUseStudentContext(),
    ];

    if (! array_key_exists($context, $allowedContexts) || ! $allowedContexts[$context]) {
        return redirect()->back()->with('error', __('You cannot switch to that role context.'));
    }

    session(['active_role_context' => $context]);

    return redirect()->route('dashboard');
})->middleware(['auth', 'verified'])->name('role-context.switch');

Route::post('/impersonate/stop', function () {
    $impersonatorId = session('impersonator_id');
    $impersonator = $impersonatorId ? User::find($impersonatorId) : null;

    if (! $impersonator || ! $impersonator->hasAnyRole(['admin', 'super-admin'])) {
        abort(403);
    }

    session()->forget(['impersonator_id', 'impersonated_user_id', 'impersonated_user_email', 'impersonated_user_name']);

    return redirect()->route('dashboard')->with('success', __('Stopped impersonation and returned to admin.'));
})->middleware('auth')->name('impersonate.stop');

// Student dashboard (default)
Route::get('/student-dashboard', Index::class)
    ->middleware(['auth', 'verified', 'role:student|teacher|uploader|admin|super-admin|school|community'])
    ->name('student.dashboard');

// Guardian dashboard
Route::get('/parent-dashboard', ParentIndex::class)
    ->middleware(['auth', 'verified', 'role:guardian|admin|super-admin|school|community'])
    ->name('parent.dashboard');

// Subscription management (Livewire)
Route::get('/subscription/manage', Manage::class)
    ->middleware(['auth', 'verified']) // or 'ensure.subscription.or.trial' if needed
    ->name('subscription.manage');

// Cancel subscription
Route::post('/subscription/cancel', [PaymentController::class, 'cancelSubscription'])
    ->middleware('auth')
    ->name('subscription.cancel');

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

// Protected routes
Route::middleware(['auth', 'ensure.subscription.or.trial'])->group(function () {
    // Quiz routes
    Route::get('quizzes', SubjectsList::class)->name('quizzes.index');
    Route::get('quizzes/all', QuizList::class)->name('quizzes.all');
    Route::get('quizzes/subject/{subject}', QuizzesBySubject::class)->name('quizzes.subject');
    Route::get('quiz/{id}', TakeQuiz::class)->name('quiz.take');

    // Mock exam
    Route::get('mock', MockSetup::class)->name('mock.setup');
    Route::get('mock/quiz', MockQuiz::class)->name('mock.quiz');
    Route::get('mock/groups', MockGroupSelection::class)->name('mock.group-selection');

    // Practice routes
    Route::get('practice', PracticeHome::class)->name('practice.home');
    Route::get('practice/quiz', PracticeQuiz::class)->name('practice.quiz');
    Route::get('practice/quiz-js', PracticeQuizJS::class)->name('practice.quiz.js');

    Route::post('quiz/autosave', [PracticeQuizController::class, 'autosave']);
    Route::post('jamb/autosave', [JambQuizController::class, 'autosave']);

    Route::prefix('api/practice')->group(function () {
        Route::post('start', [PracticeQuizApiController::class, 'startQuiz']);
        Route::get('load/{attempt}', [PracticeQuizApiController::class, 'loadAttempt']);
        Route::post('load-batch', [PracticeQuizApiController::class, 'loadBatch']);
        Route::post('save', [PracticeQuizApiController::class, 'saveAnswers']);
        Route::post('submit', [PracticeQuizApiController::class, 'submitQuiz']);
        Route::post('exit', [PracticeQuizApiController::class, 'exitQuiz']);
    });

    Route::get('practice/jamb/setup', JambSetup::class)->name('practice.jamb.setup');
    Route::get('practice/jamb/quiz', JambQuiz::class)->name('practice.jamb.quiz');

    // Lessons
    Route::get('lessons', App\Livewire\Lessons\SubjectsList::class)->name('lessons.subjects');
    Route::get('lessons/{subject}', LessonsList::class)->name('lessons.list');
    Route::get('lesson/{id}', LessonView::class)->name('lessons.view');

    // Video progress tracking (Alpine + fetch)
    Route::post('video-progress', [VideoProgressController::class, 'storeProgress'])
        ->name('video.progress.store');
    Route::post('video-completion', [VideoProgressController::class, 'markCompletion'])
        ->name('video.progress.complete');

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

// Admin video upload (for Filament lesson form)
Route::middleware(['auth'])->prefix('admin/video')->group(function () {
    Route::post('/validate', [VideoUploadController::class, 'validate'])
        ->name('admin.video.validate');
    Route::post('/create', [VideoUploadController::class, 'create'])
        ->name('admin.video.create');
    Route::post('/upload-chunk', [VideoUploadController::class, 'uploadChunk'])
        ->name('admin.video.upload-chunk');
});

// Webhooks
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.paystack');

Route::post('/webhooks/cloudinary', [CloudinaryWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.cloudinary');

// MCP Server Routes (Development Only)
// Only available when MCP_SERVER_ENABLED=true
if (config('mcp-server.enabled')) {
    Route::prefix('mcp')->group(function () {
        Route::post('/initialize', [McpController::class, 'initialize']);
        Route::post('/call-tool', [McpController::class, 'callTool']);
        Route::post('/list-resources', [McpController::class, 'listResources']);
        Route::post('/read-resource', [McpController::class, 'readResource']);
        Route::get('/server-info', [McpController::class, 'serverInfo']);
    })->middleware('mcp.auth');
}

// Integration Routes
Route::prefix('integration')->group(function () {
    Route::get('/health', [IntegrationController::class, 'health'])->name('integration.health');
    Route::get('/stats', [IntegrationController::class, 'stats'])->name('integration.stats');
    Route::get('/recommendations', [IntegrationController::class, 'recommendations'])->name('integration.recommendations');
})->middleware('auth');

// Payment routes
Route::get('payment/pricing', PaymentPricing::class)
    ->name('payment.pricing');
Route::get('pricing', PaymentPricing::class)
    ->name('pricing');
Route::post('payment/initialize', [PaymentController::class, 'initialize'])->name('payment.initialize');
Route::get('payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
