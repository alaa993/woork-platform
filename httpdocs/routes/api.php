<?php

use App\Http\Controllers\ApiAgentController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::prefix('agent')->group(function () {
    Route::post('/register', [ApiAgentController::class, 'register']);
    Route::get('/config', [ApiAgentController::class, 'config']);
    Route::post('/heartbeat', [ApiAgentController::class, 'heartbeat']);
    Route::post('/ingest', [ApiAgentController::class, 'ingest']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reports/daily', [ReportsController::class, 'daily']);
});
