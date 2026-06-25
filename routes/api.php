<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConfigurationController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\MockExamController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\SubjectDownloadController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('force.json')->group(function () {

    // Public routes (rate limited for production safety)
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Protected routes
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Configuration Routes (rate limited for production safety)
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/config/subjects', [ConfigurationController::class, 'subjects']);
            Route::get('/config/exam-types', [ConfigurationController::class, 'examTypes']);
            Route::get('/config/years', [ConfigurationController::class, 'years']);
            Route::get('/config/mock-formats', [ConfigurationController::class, 'mockFormats']);
        });

        // Analytics Routes (rate limited for production safety)
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
            Route::get('/analytics/subject-performance', [AnalyticsController::class, 'subjectPerformance']);
            Route::get('/analytics/quiz-history', [AnalyticsController::class, 'quizHistory']);
            Route::get('/analytics/study-streak', [AnalyticsController::class, 'studyStreak']);
        });

        // Lesson Routes (rate limited for production safety)
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/lessons', [LessonController::class, 'index']);
            Route::get('/lessons/{id}', [LessonController::class, 'show']);
            Route::post('/lessons/{id}/progress', [LessonController::class, 'updateProgress']);
            Route::post('/lessons/{id}/complete', [LessonController::class, 'complete']);
        });

        // Quiz Routes (rate limited for production safety)
        Route::middleware(['throttle:60,1', 'ensure.subscription.or.trial'])->group(function () {
            Route::get('/quizzes', [QuizController::class, 'index']);
            Route::get('/quizzes/{id}', [QuizController::class, 'show']);
            Route::post('/quizzes/{id}/start', [QuizController::class, 'start']);
            Route::post('/quiz-attempts/{id}/submit', [QuizController::class, 'submitAnswers']);
            Route::get('/quiz-attempts/{id}/results', [QuizController::class, 'results']);
        });

        // Question Pack Download Routes (rate limited for production safety)
        Route::middleware(['throttle:60,1', 'ensure.subscription.or.trial'])->group(function () {
            Route::get('/subjects/{id}/download', [SubjectDownloadController::class, 'downloadSubject']);
            Route::get('/jamb/download', [SubjectDownloadController::class, 'downloadJambPractice']);
        });

        // Sync Engine Routes (rate limited for production safety)
        Route::middleware('throttle:60,1')->group(function () {
            Route::post('/sync', [SyncController::class, 'sync']);
            Route::get('/sync/status', [SyncController::class, 'status']);
        });

        // Mock Exam Routes (rate limited for production safety)
        Route::middleware(['throttle:60,1', 'ensure.subscription.or.trial'])->group(function () {
            Route::get('/mock/groups', [MockExamController::class, 'index']);
            Route::get('/mock/groups/{batchNumber}', [MockExamController::class, 'show']);
            Route::get('/mock/groups/{batchNumber}/download', [MockExamController::class, 'download']);
            Route::post('/mock/sessions', [MockExamController::class, 'initializeSession']);
        });
    });
});
