<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\TranslationExportController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'message' => 'API is working.',
    ]);
});

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware(['auth:api', 'throttle:30,1'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('translations')->group(function () {
    // Public read/search endpoints
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/', [TranslationController::class, 'index']);           // search/list
        Route::get('/export', [TranslationExportController::class, 'export']); // JSON export
        Route::get('/{key}', [TranslationController::class, 'show']);       // single translation by key
    });

    // Protected write endpoints
    Route::middleware(['auth:api', 'throttle:30,1'])->group(function () {
        Route::post('/', [TranslationController::class, 'store']);
        Route::put('/{key}', [TranslationController::class, 'update']);
        Route::delete('/{key}', [TranslationController::class, 'destroy']);
    });
});
