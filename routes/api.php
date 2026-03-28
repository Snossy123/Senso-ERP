<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum', 'tenant'])->prefix('dashboard')->group(function () {
    Route::get('/widgets/{widget}', [DashboardController::class, 'widget']);
    Route::post('/widgets/refresh', [DashboardController::class, 'refresh']);
});
