<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SubjectDownloadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Question Pack Download Routes
        Route::get('/subjects/{id}/download', [SubjectDownloadController::class, 'downloadSubject']);
        Route::get('/jamb/download', [SubjectDownloadController::class, 'downloadJambPractice']);
    });
});
