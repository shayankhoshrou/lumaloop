<?php

use App\Http\Controllers\Api\HealthSyncController;
use App\Http\Controllers\Api\MobileTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/mobile/token', [MobileTokenController::class, 'store'])
    ->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/health-sync', [HealthSyncController::class, 'store']);
    Route::get('/health-summary', [HealthSyncController::class, 'summary']);
});

