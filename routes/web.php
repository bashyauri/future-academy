<?php

use Laravel\Fortify\Features;
use App\Livewire\Home\HomePage;
use App\Livewire\Dashboard\Index;
use App\Livewire\Dashboard\ParentIndex;
use App\Livewire\Quizzes\QuizList;
use App\Livewire\Quizzes\TakeQuiz;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Dashboard\Analytics;
use App\Livewire\Settings\Appearance;
use App\Livewire\Subscription\Manage; // ← Added
use App\Livewire\Payment\Pricing as PaymentPricing;
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

// Dashboard - Route to appropriate dashboard based on user role
// Uses smart routing: Parents → ParentIndex, Students/Teachers → Index
Route::get('dashboard', function () {
    $user = auth()->user();
    // Check Spatie role with fallback to account_type
    if ($user && ($user->hasRole('guardian') || $user->isParent())) {
        return redirect()->route('parent.dashboard');
    }
    return redirect()->route('student.dashboard');
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Student dashboard (default)
Route::get('/student-dashboard', \App\Livewire\Dashboard\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('student.dashboard');

// Alternative: Explicit parent-only route with strict role middleware
Route::get('/parent-dashboard', \App\Livewire\Dashboard\ParentIndex::class)
    ->middleware(['auth', 'verified', 'role:guardian'])
    ->name('parent.dashboard');

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

    // Use --force to skip interactive prompts (STDIN not available in web context)
    Artisan::call('subscriptions:sync-codes', ['--force' => true]);
    $output = Artisan::output();

    return response("<pre>{$output}</pre>")
        ->header('Content-Type', 'text/html');
})->name('sync.subscriptions');

// Debug subscriptions (for shared hosting debugging)
Route::get('/debug/subscriptions', function () {
    // Require authentication and admin role
    if (!auth()->check() || !auth()->user()->hasRole(['super-admin', 'admin'])) {
        abort(403, 'Unauthorized');
    }

    $subscriptions = \App\Models\Subscription::with('user')->orderByDesc('created_at')->limit(20)->get();

    $html = '<h1>Recent Subscriptions Debug</h1>';
    $html .= '<table border="1" cellpadding="10" style="border-collapse: collapse; font-size: 12px;">';
    $html .= '<tr><th>ID</th><th>User Email</th><th>Code</th><th>Plan Code</th><th>Auth Code</th><th>Type</th><th>Status</th><th>Active</th><th>Ends At</th><th>Cancelled At</th><th>Action</th></tr>';

    foreach ($subscriptions as $sub) {
        $checkUrl = route('debug.check-paystack-subscription', ['subCode' => $sub->subscription_code ?? 'none']);
        $html .= '<tr>';
        $html .= '<td>' . $sub->id . '</td>';
        $html .= '<td>' . ($sub->user?->email ?? 'N/A') . '</td>';
        $html .= '<td><code>' . ($sub->subscription_code ?? 'NULL') . '</code></td>';
        $html .= '<td><code>' . ($sub->plan_code ?? 'NULL') . '</code></td>';
        $html .= '<td><code>' . ($sub->authorization_code ? substr($sub->authorization_code, 0, 10) . '...' : 'NULL') . '</code></td>';
        $html .= '<td>' . $sub->type . '</td>';
        $html .= '<td>' . $sub->status . '</td>';
        $html .= '<td>' . ($sub->is_active ? '✅' : '❌') . '</td>';
        $html .= '<td>' . ($sub->ends_at?->format('Y-m-d H:i') ?? 'NULL') . '</td>';
        $html .= '<td>' . ($sub->cancelled_at?->format('Y-m-d H:i') ?? 'NULL') . '</td>';
        $html .= '<td><a href="' . $checkUrl . '" target="_blank" style="color: blue; text-decoration: underline;">Check</a></td>';
        $html .= '</tr>';
    }

    $html .= '</table>';
    $html .= '<p><small>Showing last 20 subscriptions. Check app logs for cancellation attempt details.</small></p>';

    return response($html)
        ->header('Content-Type', 'text/html; charset=utf-8');
})->name('debug.subscriptions');

// Check if a subscription exists on Paystack
Route::get('/debug/check-paystack/{subCode}', function ($subCode) {
    // Require authentication and admin role
    if (!auth()->check() || !auth()->user()->hasRole(['super-admin', 'admin'])) {
        abort(403, 'Unauthorized');
    }

    $html = '<h1>Paystack Subscription Verification: ' . htmlspecialchars($subCode) . '</h1>';
    $html .= '<p><a href="' . route('debug.subscriptions') . '">← Back to subscriptions</a></p>';

    $subscription = \App\Models\Subscription::where('subscription_code', $subCode)->first();
    if ($subscription && $subscription->user) {
        $html .= '<h3>Local Database Info</h3>';
        $html .= '<pre>' . htmlspecialchars(json_encode([
            'id' => $subscription->id,
            'user_email' => $subscription->user->email,
            'subscription_code' => $subscription->subscription_code,
            'plan_code' => $subscription->plan_code,
            'has_auth_code' => !empty($subscription->authorization_code),
            'status' => $subscription->status,
            'is_active' => $subscription->is_active,
            'created_at' => $subscription->created_at->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
        ], JSON_PRETTY_PRINT)) . '</pre>';

        // Check if status is out of sync
        if ($subscription->status === 'inactive' && $subscription->is_active === false) {
            $html .= '<h3 style="color: red;">⚠️ OUT OF SYNC - Subscription marked as inactive in local DB but may be active in Paystack</h3>';
            $html .= '<p>This usually happens when Paystack has a subscription but your local record wasn\'t updated properly.</p>';

            $html .= '<h4>Fix Options:</h4>';
            $html .= '<ol>';
            $html .= '<li><strong>Update to Active</strong> - Run this command (will mark as active in DB):<br>';
            $html .= '<form method="POST" action="' . route('debug.fix-subscription') . '" style="display: inline;">';
            $html .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
            $html .= '<input type="hidden" name="subscription_id" value="' . $subscription->id . '">';
            $html .= '<input type="hidden" name="action" value="activate">';
            $html .= '<button type="submit" style="padding: 8px 15px; background: green; color: white; border: none; cursor: pointer; border-radius: 3px;">Mark as Active</button>';
            $html .= '</form></li>';

            $html .= '<li><strong>Cancel on Paystack</strong> - First activate, then try cancelling again</li>';
            $html .= '<li><strong>Delete Locally</strong> - Remove this invalid subscription and re-subscribe:<br>';
            $html .= '<form method="POST" action="' . route('debug.fix-subscription') . '" style="display: inline;">';
            $html .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
            $html .= '<input type="hidden" name="subscription_id" value="' . $subscription->id . '">';
            $html .= '<input type="hidden" name="action" value="delete">';
            $html .= '<button type="submit" style="padding: 8px 15px; background: red; color: white; border: none; cursor: pointer; border-radius: 3px;" onclick="return confirm(\'Really delete? This cannot be undone.\');">Delete Subscription</button>';
            $html .= '</form></li>';
            $html .= '</ol>';
        } else {
            $html .= '<h3>Problem Analysis</h3>';
            $html .= '<p><strong>Status Code 404 from Paystack means:</strong></p>';
            $html .= '<ul>';
            $html .= '<li>❌ The subscription code <code>' . htmlspecialchars($subCode) . '</code> does NOT exist in Paystack</li>';
            $html .= '<li>❌ The subscription may have been deleted or cancelled manually</li>';
            $html .= '<li>❌ There\'s a mismatch between your local database and Paystack account</li>';
            $html .= '</ul>';

            $html .= '<h3>Solutions</h3>';
            $html .= '<ol>';
            $html .= '<li><strong>Option 1: Run Sync Command</strong> - Visit <code>/sync-subscriptions</code> to sync all FA-xxx codes</li>';
            $html .= '<li><strong>Option 2: Check Paystack Dashboard</strong> - Log into Paystack and verify the subscription exists with code: <code>' . htmlspecialchars($subCode) . '</code></li>';
            $html .= '<li><strong>Option 3: Delete Invalid Subscription</strong> - If the subscription is invalid, you may need to delete it locally or re-create it</li>';
            $html .= '</ol>';
        }
    } else {
        $html .= '<p style="color: red;"><strong>Subscription not found in local database with code: ' . htmlspecialchars($subCode) . '</strong></p>';
    }

    return response($html)
        ->header('Content-Type', 'text/html; charset=utf-8');
})->name('debug.check-paystack-subscription');

// Fix subscription sync issues
Route::post('/debug/fix-subscription', function (\Illuminate\Http\Request $request) {
    // Require authentication and admin role
    if (!auth()->check() || !auth()->user()->hasRole(['super-admin', 'admin'])) {
        abort(403, 'Unauthorized');
    }

    $subscriptionId = $request->input('subscription_id');
    $action = $request->input('action');

    $subscription = \App\Models\Subscription::find($subscriptionId);
    if (!$subscription) {
        return redirect()->route('debug.subscriptions')->with('error', 'Subscription not found');
    }

    if ($action === 'activate') {
        $subscription->update([
            'status' => 'active',
            'is_active' => true,
            'cancelled_at' => null,
        ]);
        \Log::info('Subscription manually activated', ['id' => $subscriptionId, 'code' => $subscription->subscription_code]);
        return redirect()->back()->with('success', 'Subscription marked as active. Now you can try cancelling it.');
    } elseif ($action === 'delete') {
        $code = $subscription->subscription_code;
        $subscription->delete();
        \Log::info('Subscription deleted', ['id' => $subscriptionId, 'code' => $code]);
        return redirect()->route('debug.subscriptions')->with('success', 'Subscription deleted. You can now create a new one.');
    }

    return redirect()->back()->with('error', 'Invalid action');
})->name('debug.fix-subscription');

// View app logs for debugging
Route::get('/debug/logs', function () {
    // Require authentication and admin role
    if (!auth()->check() || !auth()->user()->hasRole(['super-admin', 'admin'])) {
        abort(403, 'Unauthorized');
    }

    $logFile = storage_path('logs/laravel.log');

    if (!file_exists($logFile)) {
        return response('<h1>Log file not found</h1>')
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    $lines = (int) request('lines', 50);
    $allLines = file($logFile);
    $totalLines = count($allLines);

    // Show last N lines
    $displayLines = array_slice($allLines, -$lines);
    $content = implode('', $displayLines);

    $html = '<h1>Application Logs (Last ' . $lines . ' lines of ' . $totalLines . ' total)</h1>';
    $html .= '<form method="GET" style="margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px;">';
    $html .= '<label>Show last <input type="number" name="lines" value="' . $lines . '" style="width: 60px;"> lines </label>';
    $html .= '<button type="submit">Update</button>';
    $html .= ' | <a href="' . route('debug.subscriptions') . '">Back to subscriptions</a>';
    $html .= '</form>';

    $html .= '<pre style="background: #000; color: #0f0; padding: 15px; overflow-x: auto; max-height: 600px; overflow-y: auto; font-size: 11px; font-family: monospace;">';
    $html .= htmlspecialchars($content);
    $html .= '</pre>';

    return response($html)
        ->header('Content-Type', 'text/html; charset=utf-8');
})->name('debug.logs');

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
Route::get('payment/pricing', PaymentPricing::class)
    ->name('payment.pricing');
Route::get('pricing', PaymentPricing::class)
    ->name('pricing');
Route::post('payment/initialize', [\App\Http\Controllers\PaymentController::class, 'initialize'])->name('payment.initialize');
Route::get('payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('payment.callback');
