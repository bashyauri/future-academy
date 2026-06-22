<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MockExamController;
use App\Http\Controllers\Api\SubjectDownloadController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes (rate limited for production safety)
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Protected routes
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);

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
