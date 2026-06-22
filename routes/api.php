<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SubjectDownloadController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes (rate limited for production safety)
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Question Pack Download Routes (rate limited for production safety)
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/subjects/{id}/download', [SubjectDownloadController::class, 'downloadSubject']);
            Route::get('/jamb/download', [SubjectDownloadController::class, 'downloadJambPractice']);
        });

        // Sync Engine Routes (rate limited for production safety)
        Route::middleware('throttle:60,1')->group(function () {
            Route::post('/sync', [SyncController::class, 'sync']);
            Route::get('/sync/status', [SyncController::class, 'status']);
        });
    });
});
