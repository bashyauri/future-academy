<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConfigurationController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\MockExamController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\ParentApiController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\SubjectDownloadController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Practice\PracticeQuizApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware('force.json')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Public Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
        });

        // Email verification link — no auth required, validated via signed URL.
        // This route is intentionally outside the force.json middleware because
        // it returns an HTML view displayed in the user's browser after clicking
        // the link in their email.
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware('throttle:6,1')
            ->name('api.email.verify');

        /*
        |--------------------------------------------------------------------------
        | Authenticated Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(['auth:sanctum', 'verified'])->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Authentication
            |--------------------------------------------------------------------------
            */
            /*
            |--------------------------------------------------------------------------
            | Configuration
            |--------------------------------------------------------------------------
            */
            Route::middleware('throttle:60,1')->group(function () {
                Route::get('/subjects', [ConfigurationController::class, 'enrolledSubjects']);
            });

            /*
            |--------------------------------------------------------------------------
            | Analytics
            |--------------------------------------------------------------------------
            */
            Route::prefix('analytics')
                ->middleware('throttle:60,1')
                ->group(function () {
                    Route::get('/overview', [AnalyticsController::class, 'overview']);
                    Route::get('/subject-performance', [AnalyticsController::class, 'subjectPerformance']);
                    Route::get('/quiz-history', [AnalyticsController::class, 'quizHistory']);
                    Route::get('/study-streak', [AnalyticsController::class, 'studyStreak']);
                });

            /*
            |--------------------------------------------------------------------------
            | Lessons
            |--------------------------------------------------------------------------
            */
            Route::prefix('lessons')
                ->middleware('throttle:60,1')
                ->group(function () {
                    Route::get('/', [LessonController::class, 'index']);
                    Route::get('/{id}', [LessonController::class, 'show']);
                    Route::post('/{id}/progress', [LessonController::class, 'updateProgress']);
                    Route::post('/{id}/complete', [LessonController::class, 'complete']);
                });

            /*
            |--------------------------------------------------------------------------
            | Downloads
            |--------------------------------------------------------------------------
            */
            Route::middleware('throttle:60,1')->group(function () {
                Route::get('/subjects/{id}/download', [SubjectDownloadController::class, 'downloadSubject']);
            });

            /*
            |--------------------------------------------------------------------------
            | Sync
            |--------------------------------------------------------------------------
            */
            Route::prefix('sync')
                ->middleware('throttle:60,1')
                ->group(function () {
                    Route::post('/', [SyncController::class, 'sync']);
                    Route::get('/status', [SyncController::class, 'status']);
                });

            /*
            |--------------------------------------------------------------------------
            | Parent Dashboard & Guardian Features
            |--------------------------------------------------------------------------
            */
            Route::prefix('parent')
                ->middleware('throttle:60,1')
                ->group(function () {
                    Route::get('/dashboard', [ParentApiController::class, 'dashboard']);
                    Route::post('/students/link', [ParentApiController::class, 'linkStudent']);
                    Route::post('/students/create', [ParentApiController::class, 'createStudent']);
                    Route::post('/students/{studentId}/resend-invitation', [ParentApiController::class, 'resendInvitation']);
                    Route::get('/students/{studentId}/analytics/overview', [ParentApiController::class, 'studentOverview']);
                    Route::get('/students/{studentId}/analytics/subject-performance', [ParentApiController::class, 'studentSubjectPerformance']);
                    Route::get('/students/{studentId}/analytics/quiz-history', [ParentApiController::class, 'studentQuizHistory']);
                    Route::get('/students/{studentId}/analytics/study-streak', [ParentApiController::class, 'studentStudyStreak']);
                    Route::get('/students/{studentId}/subjects', [ParentApiController::class, 'studentSubjects']);
                    Route::post('/students/{studentId}/enrollment', [ParentApiController::class, 'studentUpdateEnrollment']);
                });

            /*
            |--------------------------------------------------------------------------
            | Onboarding (accessible before subscription)
            |--------------------------------------------------------------------------
            */
            Route::post('/onboarding', [OnboardingController::class, 'complete'])
                ->middleware('throttle:60,1');

            /*
            |--------------------------------------------------------------------------
            | Premium Features (require active subscription or trial)
            |--------------------------------------------------------------------------
            */
            Route::middleware([
                'throttle:60,1',
                'ensure.subscription.or.trial',
            ])->group(function () {

                /*
                |--------------------------------------------------------------------------
                | Quizzes
                |--------------------------------------------------------------------------
                */
                Route::prefix('quizzes')->group(function () {
                    Route::get('/', [QuizController::class, 'index']);
                    Route::get('/{id}', [QuizController::class, 'show']);
                    Route::post('/{id}/start', [QuizController::class, 'start']);
                });

                /*
                |--------------------------------------------------------------------------
                | Quiz Attempts
                |--------------------------------------------------------------------------
                */
                Route::prefix('quiz-attempts')->group(function () {
                    Route::post('/{id}/submit', [QuizController::class, 'submitAnswers']);
                    Route::get('/{id}/results', [QuizController::class, 'results']);
                });

                /*
                |--------------------------------------------------------------------------
                | JAMB
                |--------------------------------------------------------------------------
                */
                Route::post('/jamb/sessions', [QuizController::class, 'initializeJambSession']);
                Route::post('/jamb/start', [QuizController::class, 'startJambSession']);
                Route::get('/jamb/load/{attempt}', [QuizController::class, 'loadJambAttempt']);
                Route::post('/jamb/submit', [QuizController::class, 'submitJambQuiz']);
                Route::post('/jamb/exit', [QuizController::class, 'exitJambQuiz']);
                Route::get('/jamb/download', [SubjectDownloadController::class, 'downloadJambPractice']);

                /*
                |--------------------------------------------------------------------------
                | Practice
                |--------------------------------------------------------------------------
                */
                Route::prefix('practice')->group(function () {
                    Route::post('/start', [PracticeQuizApiController::class, 'startQuiz']);
                    Route::get('/active-attempts', [PracticeQuizApiController::class, 'getActiveAttempts']);
                    Route::delete('/attempts/{attempt}', [PracticeQuizApiController::class, 'deleteAttempt']);
                    Route::get('/question-count', [PracticeQuizApiController::class, 'getQuestionCount']);
                    Route::get('/load/{attempt}', [PracticeQuizApiController::class, 'loadAttempt']);
                    Route::post('/load-batch', [PracticeQuizApiController::class, 'loadBatch']);
                    Route::post('/save', [PracticeQuizApiController::class, 'saveAnswers']);
                    Route::post('/submit', [PracticeQuizApiController::class, 'submitQuiz']);
                    Route::post('/exit', [PracticeQuizApiController::class, 'exitQuiz']);
                });

                /*
                |--------------------------------------------------------------------------
                | Mock Exams
                |--------------------------------------------------------------------------
                */
                Route::prefix('mock')->group(function () {
                    Route::get('/groups', [MockExamController::class, 'index']);
                    Route::get('/groups/{batchNumber}', [MockExamController::class, 'show']);
                    Route::get('/groups/{batchNumber}/download', [MockExamController::class, 'download']);
                    Route::post('/sessions', [MockExamController::class, 'initializeSession']);
                });
            });
        });

        Route::prefix('payment')
            ->middleware('throttle:60,1')
            ->group(function () {
                Route::get('/pricing', [PaymentApiController::class, 'pricing']);
            });

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/user', [AuthController::class, 'user']);

            Route::post('/logout', [AuthController::class, 'logout']);

            Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationNotification'])
                ->middleware('throttle:6,1');

            Route::prefix('payment')
                ->middleware('throttle:60,1')
                ->group(function () {
                    Route::post('/initialize', [PaymentApiController::class, 'initialize']);
                    Route::post('/verify', [PaymentApiController::class, 'verify']);
                });
        });

        /*
        |--------------------------------------------------------------------------
        | Public Configuration
        |--------------------------------------------------------------------------
        */
        Route::prefix('config')
            ->middleware('throttle:60,1')
            ->group(function () {
                Route::get('/streams', [ConfigurationController::class, 'streams']);
                Route::get('/subjects', [ConfigurationController::class, 'subjects']);
                Route::get('/exam-types', [ConfigurationController::class, 'examTypes']);
                Route::get('/years', [ConfigurationController::class, 'years']);
                Route::get('/mock-formats', [ConfigurationController::class, 'mockFormats']);
            });

        Route::prefix('onboarding')
            ->middleware('throttle:60,1')
            ->group(function () {
                Route::get('/bootstrap', [ConfigurationController::class, 'bootstrap']);
            });
    });

Route::get('/v1/payment/callback-redirect', [PaymentApiController::class, 'callbackRedirect']);
