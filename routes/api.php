<?php

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MonitorController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationStreamController;
use App\Http\Controllers\Api\SecurityEventController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/notifications/stream', NotificationStreamController::class);

Route::middleware('admin.token')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/monitor/check', [MonitorController::class, 'check']);

    Route::get('/dashboard', DashboardController::class);

    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::get('/services/{service}', [ServiceController::class, 'show']);
    Route::put('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
    Route::get('/services/{service}/metrics', [ServiceController::class, 'metrics']);

    Route::get('/alerts', [AlertController::class, 'index']);
    Route::get('/security-events', [SecurityEventController::class, 'index']);
    Route::post('/simulate-login-failure', [SecurityEventController::class, 'simulateLoginFailure']);
    Route::post('/simulate-traffic-anomaly', [SecurityEventController::class, 'simulateTrafficAnomaly']);
    Route::post('/simulate-config-change', [SecurityEventController::class, 'simulateConfigChange']);
});
