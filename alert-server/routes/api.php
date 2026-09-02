<?php

use App\Http\Controllers\Api\HealthEventController;
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

});