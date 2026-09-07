<?php

use App\Http\Controllers\Api\HealthEventController;
use App\Http\Controllers\Api\HealthEventStreamController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post(
        '/health-events',
        [HealthEventController::class, 'store']
    )->middleware('health.auth');

    Route::get(
        
        '/services/{service}/status',
        [HealthEventController::class, 'status']
    );

    Route::get(
        '/events/stream',
        [HealthEventStreamController::class, 'stream']
    );
});